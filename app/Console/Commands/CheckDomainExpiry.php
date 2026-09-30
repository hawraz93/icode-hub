<?php

namespace App\Console\Commands;

use App\Models\ActivityReminder;
use App\Models\Subscription;
use App\Services\DomainExpiryLookup;
use App\Services\TelegramNotifier;
use Illuminate\Console\Command;

class CheckDomainExpiry extends Command
{
    protected $signature = 'domains:check-expiry
                            {--force : Re-check domains already checked in the last 20 hours}
                            {--quiet-telegram : Do not send mismatches to Telegram}';

    protected $description = 'Compare recorded domain expiry dates with the registry (RDAP) and report mismatches';

    public function handle(DomainExpiryLookup $rdap, TelegramNotifier $telegram): int
    {
        $domains = Subscription::with('client')
            ->whereIn('type', ['domain', 'bundle'])
            ->where('status', '!=', 'cancelled')
            ->whereNotNull('domain_name')
            ->when(! $this->option('force'), fn ($q) => $q->where(fn ($w) => $w
                ->whereNull('registry_checked_at')
                ->orWhere('registry_checked_at', '<', now()->subHours(20))))
            ->get();

        $mismatches = [];
        $autoRenewed = [];
        $rows = [];

        foreach ($domains as $sub) {
            try {
                $result = $rdap->lookup($sub->domain_name);
            } catch (\Throwable $e) {
                $rows[] = [$sub->domain_name, $sub->expiry_date->format('Y-m-d'), 'error: ' . str($e->getMessage())->limit(40)];
                continue;
            }

            $sub->update([
                'registry_expiry_date' => $result['expiry']?->format('Y-m-d'),
                'registry_checked_at' => now(),
            ]);

            $registry = $result['expiry']?->format('Y-m-d') ?? ($result['supported'] ? 'not found' : 'no RDAP');
            $rows[] = [$sub->domain_name, $sub->expiry_date->format('Y-m-d'), $registry];

            if (! $sub->has_registry_mismatch) {
                continue;
            }

            // Registry is later and in the future: the domain was renewed (auto-renew or manually), so follow it.
            if ($sub->registry_expiry_date->gt($sub->expiry_date) && $sub->registry_expiry_date->isFuture()) {
                $wasPaid = $sub->renewal_stage >= Subscription::STAGE_PAID;
                $old = $sub->expiry_date->format('Y-m-d');
                $sub->update([
                    'expiry_date' => $sub->registry_expiry_date->format('Y-m-d'),
                    'status' => 'active',
                    'renewal_stage' => Subscription::STAGE_NONE,
                    'stage_updated_at' => now(),
                    'last_reminded_at' => null,
                ]);
                ActivityReminder::create([
                    'client_id' => $sub->client_id,
                    'subscription_id' => $sub->id,
                    'type' => 'subscription_renewed',
                    'channel' => 'system',
                    'message' => "خۆکار لە تۆمارگە: {$old} ← {$sub->expiry_date->format('Y-m-d')}",
                    'status' => 'sent',
                    'sent_at' => now(),
                ]);
                $autoRenewed[] = [$sub, $wasPaid];
            } else {
                $mismatches[] = $sub;
            }
        }

        $this->table(['Domain', 'Recorded', 'Registry'], $rows);

        $e = fn ($s) => TelegramNotifier::escape($s);

        if ($autoRenewed && ! $this->option('quiet-telegram')) {
            $lines = ['🔄 <b>ئەم دۆمەینانە لە تۆمارگە نوێکراونەتەوە</b>', 'بەرواری سیستەم خۆکار نوێکرایەوە:', ''];
            foreach ($autoRenewed as [$sub, $wasPaid]) {
                $lines[] = '✅ <code>' . $e($sub->domain_name) . '</code> تا <b>' . $sub->expiry_date->format('Y-m-d') . '</b> · ' . $e($sub->client?->business_name ?: $sub->client?->name);
                if (! $wasPaid) {
                    $lines[] = '   ⚠️ پارەی کڕیار تۆمار نەکرابوو، بزانە وەرتگرتووە';
                }
            }
            $telegram->send(implode("\n", $lines));
        }

        if ($mismatches && ! $this->option('quiet-telegram')) {
            $lines = ['🌐 <b>بەرواری دۆمەین جیاوازە لەگەڵ تۆمارگە</b>', ''];
            foreach ($mismatches as $sub) {
                $earlier = $sub->registry_expiry_date->lt($sub->expiry_date);
                $lines[] = ($earlier ? '🔴 ' : '🔵 ') . '<code>' . $e($sub->domain_name) . '</code> · ' . $e($sub->client?->business_name ?: $sub->client?->name);
                $lines[] = '   سیستەم: ' . $sub->expiry_date->format('Y-m-d') . ' · تۆمارگە: <b>' . $sub->registry_expiry_date->format('Y-m-d') . '</b>';
                $lines[] = '   ↳ ' . ($earlier ? 'زووتر بەسەردەچێت لەوەی نووسیوتە!' : 'نوێکراوەتەوە، بەرواری سیستەم نوێ بکەرەوە');
            }
            $telegram->send(implode("\n", $lines));
        }

        $this->info(count($domains) . ' checked, ' . count($autoRenewed) . ' auto-renewed, ' . count($mismatches) . ' mismatch(es).');

        return self::SUCCESS;
    }
}
