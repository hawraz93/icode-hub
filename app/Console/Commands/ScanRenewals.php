<?php

namespace App\Console\Commands;

use App\Models\ActivityReminder;
use App\Models\Server;
use App\Models\Subscription;
use App\Services\TelegramNotifier;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class ScanRenewals extends Command
{
    protected $signature = 'renewals:scan
                            {--dry-run : Print the message without sending or recording anything}
                            {--force : Include items already reminded today}';

    protected $description = 'Mark expired subscriptions and send the daily renewal digest to Telegram';

    /** Days before expiry that always trigger a reminder (plus each subscription\'s own reminder_days_before). */
    private const MILESTONES = [14];

    /** Inside this many days, remind every day. */
    private const DAILY_WINDOW = 7;

    /** Keep nagging this many days after expiry. */
    private const OVERDUE_WINDOW = 30;

    public function handle(TelegramNotifier $telegram): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $expiredCount = $dryRun ? 0 : $this->markExpired();

        $subscriptions = $this->dueSubscriptions();
        $servers = $this->dueServers();

        if ($expiredCount) {
            $this->info("{$expiredCount} subscription(s) marked as expired.");
        }

        if ($subscriptions->isEmpty() && $servers->isEmpty()) {
            $this->info('Nothing needs a reminder today.');

            return self::SUCCESS;
        }

        $message = $this->buildMessage($subscriptions, $servers);

        if ($dryRun) {
            $this->line(strip_tags($message));

            return self::SUCCESS;
        }

        if (! $telegram->isConfigured()) {
            $this->warn('Telegram is not configured (TELEGRAM_BOT_TOKEN / TELEGRAM_CHAT_ID). Run `php artisan telegram:test`.');
        }

        $sent = $telegram->send($message);

        foreach ($subscriptions as $sub) {
            ActivityReminder::create([
                'client_id' => $sub->client_id,
                'subscription_id' => $sub->id,
                'type' => 'subscription_renewal',
                'channel' => 'telegram',
                'recipient' => $telegram->chatId(),
                'message' => "{$sub->name}: {$sub->expiry_status_text}",
                'status' => $sent ? 'sent' : 'failed',
                'sent_at' => $sent ? now() : null,
            ]);
        }

        foreach ($servers as $server) {
            ActivityReminder::create([
                'server_id' => $server->id,
                'type' => 'server_renewal',
                'channel' => 'telegram',
                'recipient' => $telegram->chatId(),
                'message' => "{$server->name}: {$server->renewal_status_text}",
                'status' => $sent ? 'sent' : 'failed',
                'sent_at' => $sent ? now() : null,
            ]);
        }

        if ($sent) {
            Subscription::whereKey($subscriptions->modelKeys())->update(['last_reminded_at' => now()]);
            $this->info("Digest sent: {$subscriptions->count()} subscription(s), {$servers->count()} server(s).");

            return self::SUCCESS;
        }

        $this->error('Digest was not delivered; recorded as failed in activity_reminders.');

        return self::FAILURE;
    }

    private function markExpired(): int
    {
        return Subscription::where('status', 'active')
            ->whereDate('expiry_date', '<', Carbon::today()->toDateString())
            ->update(['status' => 'expired']);
    }

    private function dueSubscriptions(): Collection
    {
        $force = (bool) $this->option('force');

        return Subscription::with('client')
            ->openRenewals(90)
            ->orderBy('expiry_date')
            ->get()
            ->filter(function (Subscription $sub) use ($force) {
                if (! $force && $sub->last_reminded_at?->isToday()) {
                    return false;
                }

                $days = $sub->days_until_expiry;

                return ($days <= self::DAILY_WINDOW && $days >= -self::OVERDUE_WINDOW)
                    || in_array($days, self::MILESTONES, true)
                    || $days === (int) $sub->reminder_days_before;
            })
            ->values();
    }

    private function dueServers(): Collection
    {
        return Server::where('status', 'active')
            ->orderBy('renewal_date')
            ->get()
            ->filter(function (Server $server) {
                $days = $server->days_until_renewal;

                return ($days <= self::DAILY_WINDOW && $days >= -self::OVERDUE_WINDOW)
                    || in_array($days, self::MILESTONES, true);
            })
            ->values();
    }

    private function buildMessage(Collection $subscriptions, Collection $servers): string
    {
        $e = fn (?string $s) => TelegramNotifier::escape($s);

        $lines = ['🔔 <b>نوێکردنەوەکانی ئەمڕۆ</b> · ' . Carbon::today()->format('Y-m-d'), ''];

        $groups = [
            '🔴 <b>بەسەرچووە</b>' => $subscriptions->filter(fn ($s) => $s->days_until_expiry < 0),
            '🟠 <b>ئەم هەفتەیە</b>' => $subscriptions->filter(fn ($s) => $s->days_until_expiry >= 0 && $s->days_until_expiry <= 7),
            '🟡 <b>نزیکە</b>' => $subscriptions->filter(fn ($s) => $s->days_until_expiry > 7),
        ];

        foreach ($groups as $title => $items) {
            if ($items->isEmpty()) {
                continue;
            }
            $lines[] = $title;
            foreach ($items as $sub) {
                $client = $sub->client?->business_name ?: $sub->client?->name;
                $stage = Subscription::STAGE_LABELS[$sub->renewal_stage] ?? '';
                $lines[] = '• <code>' . $e($sub->domain_name ?: $sub->name) . '</code> · ' . $e($this->typeShort($sub->type));
                $lines[] = '   ' . $e($client) . ' · ' . $e($sub->expiry_status_text) . ' · $' . number_format((float) $sub->selling_price, 0);
                $lines[] = '   ↳ ' . $e($stage);
            }
            $lines[] = '';
        }

        if ($servers->isNotEmpty()) {
            $lines[] = '🖥 <b>سێرڤەرەکانی خۆت</b>';
            foreach ($servers as $server) {
                $auto = $server->auto_renew ? ' · خۆکار' : '';
                $lines[] = '• ' . $e($server->name) . ' (' . $e($server->provider) . ') · ' . $e($server->renewal_status_text) . ' · $' . number_format((float) $server->cost, 0) . $auto;
            }
            $lines[] = '';
        }

        $unpaid = $subscriptions->where('renewal_stage', '<', Subscription::STAGE_PAID)->sum('selling_price');
        $cost = $subscriptions->sum('cost_price');
        $lines[] = '💵 وەرگرتن لە کڕیاران: <b>$' . number_format((float) $unpaid, 0) . '</b> · پارەدان بە دابینکەر: <b>$' . number_format((float) $cost, 0) . '</b>';
        $lines[] = '👉 ' . $e(route('admin.renewals'));

        return implode("\n", $lines);
    }

    private function typeShort(string $type): string
    {
        return match ($type) {
            'domain' => 'دۆمەین',
            'hosting' => 'هۆستینگ',
            'bundle' => 'دۆمەین + هۆستینگ',
            'email' => 'ئیمەیڵی بزنس',
            'vps' => 'VPS',
            'license' => 'مۆڵەت',
            'maintenance' => 'پشتگیری',
            default => 'خزمەتگوزاری',
        };
    }
}
