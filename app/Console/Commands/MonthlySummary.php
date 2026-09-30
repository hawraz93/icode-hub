<?php

namespace App\Console\Commands;

use App\Models\Server;
use App\Models\Subscription;
use App\Services\TelegramNotifier;
use Carbon\Carbon;
use Illuminate\Console\Command;

class MonthlySummary extends Command
{
    protected $signature = 'renewals:monthly {--dry-run : Print instead of sending}';

    protected $description = 'Send a Telegram summary of this month\'s renewals, income and costs';

    private const MONTHS = [1 => 'کانوونی دووەم', 'شوبات', 'ئازار', 'نیسان', 'ئایار', 'حوزەیران', 'تەمموز', 'ئاب', 'ئەیلوول', 'تشرینی یەکەم', 'تشرینی دووەم', 'کانوونی یەکەم'];

    public function handle(TelegramNotifier $telegram): int
    {
        $start = Carbon::today()->startOfMonth();
        $end = Carbon::today()->endOfMonth();
        $e = fn ($s) => TelegramNotifier::escape($s);

        $month = Subscription::with('client')
            ->whereIn('status', ['active', 'grace_period', 'expired'])
            ->whereBetween('expiry_date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('expiry_date')
            ->get();
        $overdue = Subscription::whereIn('status', ['active', 'grace_period', 'expired'])
            ->whereDate('expiry_date', '<', $start->toDateString())
            ->get();
        $serverCost = Server::where('status', 'active')->get()->sum(fn ($s) => match ($s->billing_cycle) {
            'annual' => $s->cost / 12,
            'semi_annual' => $s->cost / 6,
            'quarterly' => $s->cost / 3,
            default => (float) $s->cost,
        });

        $income = $month->sum('selling_usd');
        $cost = $month->sum('cost_usd');

        $lines = [
            '📅 <b>پوختەی ' . self::MONTHS[$start->month] . ' ' . $start->year . '</b>',
            '',
            "🔁 نوێکردنەوەکانی ئەم مانگە: <b>{$month->count()}</b>",
            '💵 وەرگرتن: <b>$' . number_format($income) . '</b>',
            '🏷 پارەدان بە دابینکەران: <b>$' . number_format($cost) . '</b>',
            '🖥 سێرڤەرەکانی خۆت: <b>$' . number_format($serverCost) . '</b>',
            '📈 قازانج: <b>$' . number_format($income - $cost - $serverCost) . '</b>',
        ];
        if ($overdue->isNotEmpty()) {
            $lines[] = '';
            $lines[] = "🔴 هێشتا {$overdue->count()} بەسەرچووی مانگەکانی پێشوو ماوە ($" . number_format($overdue->sum('selling_usd')) . ')';
        }
        if ($month->isNotEmpty()) {
            $lines[] = '';
            $lines[] = '<b>گەورەترینەکان:</b>';
            foreach ($month->sortByDesc('selling_usd')->take(5) as $sub) {
                $lines[] = '• ' . $sub->expiry_date->format('m-d') . ' <code>' . $e($sub->domain_name ?: $sub->name) . '</code> · ' . $e($sub->selling_label);
            }
        }
        $lines[] = '';
        $lines[] = '👉 ' . $e(route('admin.renewals'));

        $message = implode("\n", $lines);

        if ($this->option('dry-run')) {
            $this->line(html_entity_decode(strip_tags($message)));

            return self::SUCCESS;
        }

        $sent = $telegram->send($message);
        $sent ? $this->info('Monthly summary sent.') : $this->error('Sending failed: ' . ($telegram->lastError ?? 'Telegram not configured'));

        return $sent ? self::SUCCESS : self::FAILURE;
    }
}
