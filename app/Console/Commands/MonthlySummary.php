<?php

namespace App\Console\Commands;

use App\Models\Expense;
use App\Models\ExpenseSchedule;
use App\Models\Payment;
use App\Models\Subscription;
use App\Services\TelegramNotifier;
use App\Support\Money;
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
        $cur = fn ($x) => $x->currency;
        $income = Money::totals($month, fn ($s) => $s->selling_price, $cur);
        $providerCost = Money::totals($month, fn ($s) => $s->cost_price, $cur);
        // Forecast of this month's share of recurring plans (each VPS once); not money already paid.
        $ownCost = Money::totals(ExpenseSchedule::active()->get(), fn ($s) => $s->annual_forecast / 12, $cur);
        $paidExpenses = Money::totals(Expense::posted()->whereBetween('expense_date', [$start->toDateString(), $end->toDateString()])->get(), fn ($x) => $x->amount, $cur);
        $received = Money::totals(Payment::receivedBetween($start->toDateString(), $end->toDateString())->get(), fn ($x) => $x->amount, $cur);
        $profit = Money::subtract(Money::subtract($income, $providerCost), $ownCost);
        $unpaid = Subscription::unpaid()->get();
        $fmt = fn (array $t) => '<b>' . $e(Money::formatTotals($t)) . '</b>';

        $lines = [
            '📅 <b>پوختەی ' . self::MONTHS[$start->month] . ' ' . $start->year . '</b>',
            '',
            "🔁 نوێکردنەوەکانی ئەم مانگە: <b>{$month->count()}</b>",
            '💵 نرخی نوێکردنەوەکان (پێشبینی): ' . $fmt($income),
            '🏷 پارەدان بە دابینکەران (پێشبینی): ' . $fmt($providerCost),
            '🖥 بەشی مانگانەی پلانی خەرجی: ' . $fmt($ownCost),
            '📈 جیاوازی پێشبینی: ' . $fmt($profit),
            '',
            '💰 پارەی وەرگیراو تا ئێستا: ' . $fmt($received),
            '💸 خەرجی دراو تا ئێستا: ' . $fmt($paidExpenses),
        ];
        if ($unpaid->isNotEmpty()) {
            $lines[] = '';
            $lines[] = "🔴 {$unpaid->count()} کڕیار پارەیان نەداوە: " . $fmt(Money::totals($unpaid, fn ($s) => $s->selling_price, $cur));
        }
        if ($overdue->isNotEmpty()) {
            $lines[] = "⏰ {$overdue->count()} بەسەرچووی مانگەکانی پێشوو هێشتا نوێ نەکراونەتەوە";
        }
        if ($month->isNotEmpty()) {
            $lines[] = '';
            $lines[] = '<b>ئەم مانگە:</b>';
            foreach ($month->take(8) as $sub) {
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
