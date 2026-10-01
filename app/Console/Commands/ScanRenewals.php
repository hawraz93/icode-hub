<?php

namespace App\Console\Commands;

use App\Models\ActivityReminder;
use App\Models\Invoice;
use App\Models\Server;
use App\Models\Subscription;
use App\Services\RenewalBot;
use App\Services\TelegramNotifier;
use App\Support\Money;
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

    public function handle(TelegramNotifier $telegram, RenewalBot $bot): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $expiredCount = $dryRun ? 0 : $this->markExpired();

        $subscriptions = $this->dueSubscriptions();
        $invoices = $this->dueInvoices();
        // Once a week, one card per unpaid client with a "paid" button
        $unpaidCards = Carbon::today()->isMonday() ? Subscription::with('client')->unpaid()->get() : collect();
        $servers = $this->dueServers();

        if ($expiredCount) {
            $this->info("{$expiredCount} subscription(s) marked as expired.");
        }

        if ($subscriptions->isEmpty() && $servers->isEmpty() && $invoices->isEmpty() && $unpaidCards->isEmpty()) {
            $this->info('Nothing needs a reminder today.');

            return self::SUCCESS;
        }

        $message = $this->buildMessage($subscriptions, $servers, $invoices);

        if ($dryRun) {
            $this->line(html_entity_decode(strip_tags($message)));
            foreach ($subscriptions as $sub) {
                $this->line('');
                $this->line(html_entity_decode(strip_tags($bot->card($sub)[0])));
            }
            foreach ($invoices as $inv) {
                $this->line('');
                $this->line(html_entity_decode(strip_tags($bot->invoiceCard($inv)[0])));
            }

            return self::SUCCESS;
        }

        if (! $telegram->isConfigured()) {
            $this->warn('Telegram is not configured (TELEGRAM_BOT_TOKEN / TELEGRAM_CHAT_ID). Run `php artisan telegram:test`.');
        }

        $sent = $telegram->send($message);
        if ($sent) {
            foreach ($subscriptions as $sub) {
                $bot->sendCard($sub);
            }
            foreach ($invoices as $inv) {
                $bot->sendInvoiceCard($inv);
            }
            $unpaidCards->each(fn ($sub) => $bot->sendUnpaidCard($sub));
        }

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

        foreach ($invoices as $inv) {
            ActivityReminder::create([
                'client_id' => $inv->client_id,
                'subscription_id' => $inv->subscription_id,
                'invoice_id' => $inv->id,
                'type' => 'invoice_due',
                'channel' => 'telegram',
                'recipient' => $telegram->chatId(),
                'message' => "{$inv->invoice_number}: {$inv->remaining_balance} {$inv->currency}",
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

    /**
     * Payments due in 3 days, today, or overdue (daily for 30 days, then Mondays), not already sent today.
     */
    private function dueInvoices(): Collection
    {
        $force = (bool) $this->option('force');
        $today = Carbon::today();

        return Invoice::with(['client', 'subscription'])
            ->whereIn('status', ['sent', 'partial', 'overdue'])
            ->whereColumn('paid_amount', '<', 'total')
            ->whereDate('due_date', '<=', $today->copy()->addDays(3)->toDateString())
            ->orderBy('due_date')
            ->get()
            ->filter(function (Invoice $inv) use ($force, $today) {
                if (! $force && ActivityReminder::where('invoice_id', $inv->id)->where('type', 'invoice_due')->whereDate('created_at', $today)->exists()) {
                    return false;
                }
                $days = (int) $today->diffInDays($inv->due_date, false);

                return in_array($days, [3, 0], true) || ($days < 0 && ($days >= -30 || $today->isMonday()));
            })
            ->values();
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
                    || $days === (int) $sub->reminder_days_before
                    // Long-lapsed items stay in the radar; nudge about them once a week so they get renewed or cancelled.
                    || ($days < -self::OVERDUE_WINDOW && Carbon::today()->isMonday());
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

    private function buildMessage(Collection $subscriptions, Collection $servers, ?Collection $invoices = null): string
    {
        $e = fn (?string $s) => TelegramNotifier::escape($s);

        $lines = ['🔔 <b>نوێکردنەوەکانی ئەمڕۆ</b> · ' . Carbon::today()->format('Y-m-d'), ''];

        $counts = array_filter([
            '🔴 بەسەرچووە' => $subscriptions->filter(fn ($s) => $s->days_until_expiry < 0)->count(),
            '🟠 ئەم هەفتەیە' => $subscriptions->filter(fn ($s) => $s->days_until_expiry >= 0 && $s->days_until_expiry <= 7)->count(),
            '🟡 نزیکە' => $subscriptions->filter(fn ($s) => $s->days_until_expiry > 7)->count(),
        ]);
        if ($counts) {
            $lines[] = collect($counts)->map(fn ($n, $label) => "{$label}: <b>{$n}</b>")->implode(' · ');
            $lines[] = 'هەر یەکەیان لە خوارەوە بە دوگمەوە دێت 👇';
            $lines[] = '';
        }
        if ($servers->isNotEmpty()) {
            $lines[] = '🖥 <b>خزمەتگوزارییەکانی خۆت</b>';
            foreach ($servers as $server) {
                $auto = $server->auto_renew ? ' · خۆکار' : '';
                $lines[] = '• ' . $e($server->name) . ' (' . $e($server->kind_label) . ') · ' . $e($server->renewal_status_text) . ' · ' . $e($server->cost_label) . $auto;
            }
            $lines[] = '';
        }

        $cur = fn ($x) => $x->currency;
        if ($invoices && $invoices->isNotEmpty()) {
            $lines[] = '💳 <b>وەسڵی کاتی دان هاتوو:</b> ' . $invoices->count() . ' · ' . $e(Money::formatTotals(Money::totals($invoices, fn ($i) => $i->remaining_balance, $cur)));
        }
        $unpaid = Subscription::unpaid()->get();
        if ($unpaid->isNotEmpty()) {
            $lines[] = '🔴 <b>پارەیان نەداوە:</b> ' . $unpaid->count() . ' · ' . $e(Money::formatTotals(Money::totals($unpaid, fn ($x) => $x->selling_price, $cur)));
        }
        $toCollect = $subscriptions->where('renewal_stage', '<', Subscription::STAGE_PAID);
        $lines[] = '💵 وەرگرتن لە کڕیاران: <b>' . $e(Money::formatTotals(Money::totals($toCollect, fn ($x) => $x->selling_price, $cur))) . '</b>';
        $lines[] = '🏷 پارەدان بە دابینکەر: <b>' . $e(Money::formatTotals(Money::totals($subscriptions, fn ($x) => $x->cost_price, $cur))) . '</b>';
        $lines[] = '👉 ' . $e(route('admin.renewals'));

        return implode("\n", $lines);
    }
}
