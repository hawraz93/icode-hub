<?php

namespace App\Services;

use App\Exceptions\FinanceException;
use App\Models\ActivityReminder;
use App\Models\Expense;
use App\Models\ExpenseSchedule;
use App\Models\Server;
use App\Support\Decimal;
use App\Support\Money;
use App\Support\Period;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Company expenses. Two separate things:
 *  - ExpenseSchedule: the plan (price, cycle, next due date) used for forecasts;
 *  - Expense rows: money actually paid, counted once on expense_date.
 * A shared VPS is one schedule; linking client services to it never creates another expense.
 */
class ExpenseService
{
    /**
     * Keep the server's schedule in step with the server form. Server.cost / billing_cycle /
     * renewal_date are a mirror for older screens; forecasts read the schedule.
     */
    public function syncServerSchedule(Server $server): ExpenseSchedule
    {
        [$unit, $count] = Period::fromLegacyCycle($server->billing_cycle);

        $schedule = ExpenseSchedule::firstOrNew(['server_id' => $server->id]);
        if (! $schedule->exists) {
            $schedule->legacy_source = 'servers';
            $schedule->legacy_id = $server->id;
            $schedule->starts_on = $server->purchase_date;
        }

        $schedule->fill([
            'title' => $server->name,
            'category' => 'infrastructure',
            'vendor' => $server->provider,
            'currency' => strtoupper($server->currency ?: 'USD'),
            'amount_per_cycle' => Decimal::str(Decimal::of($server->cost)),
            'cycle_unit' => $unit,
            'cycle_count' => $count,
            'next_due_on' => Carbon::parse($server->renewal_date)->toDateString(),
            // A freshly created model lacks DB defaults, so a missing status means the default "active".
            'status' => ($server->status ?? 'active') === 'active' ? 'active' : 'ended',
            'auto_renew' => (bool) $server->auto_renew,
        ])->save();

        return $schedule;
    }

    /**
     * "The VPS (or any planned expense) was paid": one Expense row for one period, then the
     * schedule moves forward by exactly one cycle. Paying the same period twice returns the
     * existing row. Several missed periods are never filled in automatically: the user picks
     * which period this money covers via $periodStart (default: the next due period).
     *
     * @param  array<string, mixed>  $opts  amount, expense_date, payment_method, reference, notes, period_start
     */
    public function paySchedule(ExpenseSchedule $schedule, array $opts = []): Expense
    {
        return DB::transaction(function () use ($schedule, $opts) {
            $schedule = ExpenseSchedule::whereKey($schedule->getKey())->lockForUpdate()->firstOrFail();
            if ($schedule->status === 'ended') {
                throw new FinanceException('ئەم پلانی خەرجییە کۆتایی هاتووە.');
            }

            $start = Carbon::parse($opts['period_start'] ?? $schedule->next_due_on)->startOfDay();
            $end = $schedule->periodEndFrom($start);
            $key = "schedule:{$schedule->id}:{$start->toDateString()}";

            if ($existing = Expense::where('idempotency_key', $key)->first()) {
                return $existing;
            }

            $amount = Decimal::of($opts['amount'] ?? $schedule->amount_per_cycle);
            if ($amount->isLessThanOrEqualTo(0)) {
                throw new FinanceException('بڕی خەرجی دەبێت لە سفر زیاتر بێت.');
            }

            $expense = Expense::create([
                'title' => $schedule->title,
                'category' => $schedule->category,
                'amount' => Decimal::str($amount),
                'currency' => $schedule->currency,
                'billing_cycle' => 'one_time', // a ledger row is one payment, whatever the plan's cycle
                'expense_date' => Carbon::parse($opts['expense_date'] ?? today())->toDateString(),
                'payment_method' => $opts['payment_method'] ?? ($schedule->payment_method ?: 'card'),
                'vendor' => $schedule->vendor,
                'reference' => $opts['reference'] ?? null,
                'notes' => $opts['notes'] ?? null,
                'expense_schedule_id' => $schedule->id,
                'server_id' => $schedule->server_id,
                'period_start' => $start->toDateString(),
                'period_end' => $end->toDateString(),
                'status' => 'posted',
                'idempotency_key' => $key,
            ]);

            // Only paying the due period moves the plan; paying an older gap leaves it where it is.
            if ($start->equalTo($schedule->next_due_on->copy()->startOfDay())) {
                $schedule->update(['next_due_on' => $end->toDateString(), 'status' => 'active']);
                $schedule->server?->forceFill(['renewal_date' => $end->toDateString(), 'status' => 'active'])->saveQuietly();
            }

            ActivityReminder::create([
                'server_id' => $schedule->server_id,
                'type' => 'expense_paid',
                'channel' => 'system',
                'message' => "خەرجی «{$schedule->title}» درا: " . Money::format($amount->toFloat(), $schedule->currency)
                    . " ({$start->toDateString()} → {$end->toDateString()})",
                'status' => 'sent',
                'sent_at' => now(),
            ]);

            return $expense;
        });
    }

    /** Void instead of delete: the row stays visible with its reason and stops counting. */
    public function void(Expense $expense, string $reason): Expense
    {
        $reason = trim($reason);
        if ($reason === '') {
            throw new FinanceException('هۆکاری هەڵوەشاندنەوە بنووسە.');
        }

        return DB::transaction(function () use ($expense, $reason) {
            $expense = Expense::whereKey($expense->getKey())->lockForUpdate()->firstOrFail();
            if ($expense->status === 'void') {
                return $expense;
            }

            $expense->update([
                'status' => 'void',
                'void_reason' => $reason,
                'voided_at' => now(),
                // Free the key so the same period can be paid again correctly.
                'idempotency_key' => $expense->idempotency_key ? $expense->idempotency_key . ':void:' . $expense->id : null,
            ]);

            return $expense;
        });
    }

    /**
     * Resolve a legacy expense whose billing_cycle said "monthly/annual" (meaning unclear):
     * keep it as one real payment and, if asked, create the recurring plan it described.
     */
    public function resolveLegacy(Expense $expense, bool $createSchedule): ?ExpenseSchedule
    {
        return DB::transaction(function () use ($expense, $createSchedule) {
            $expense = Expense::whereKey($expense->getKey())->lockForUpdate()->firstOrFail();
            $schedule = null;

            if ($createSchedule) {
                [$unit, $count] = Period::fromLegacyCycle($expense->billing_cycle);
                $schedule = ExpenseSchedule::firstOrCreate(
                    ['legacy_source' => 'expenses', 'legacy_id' => $expense->id],
                    [
                        'title' => $expense->title,
                        'category' => $expense->category,
                        'vendor' => $expense->vendor,
                        'currency' => $expense->currency,
                        'amount_per_cycle' => $expense->amount,
                        'cycle_unit' => $unit,
                        'cycle_count' => $count,
                        'starts_on' => $expense->expense_date,
                        'next_due_on' => Period::add($expense->expense_date, $unit, $count)->toDateString(),
                        'status' => 'active',
                        'payment_method' => $expense->payment_method,
                    ],
                );
                $expense->fill([
                    'expense_schedule_id' => $schedule->id,
                    'period_start' => $expense->expense_date->toDateString(),
                    'period_end' => Period::add($expense->expense_date, $unit, $count)->toDateString(),
                ]);
            }

            $expense->fill(['needs_review' => false, 'review_note' => $createSchedule ? 'کرا بە پلانی دووبارە' : 'پارەدانی یەکجار'])->save();

            return $schedule;
        });
    }
}
