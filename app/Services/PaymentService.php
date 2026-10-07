<?php

namespace App\Services;

use App\Exceptions\FinanceException;
use App\Models\ActivityReminder;
use App\Models\Invoice;
use App\Models\Payment;
use App\Support\Decimal;
use App\Support\Money;
use Brick\Math\BigDecimal;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * The only writer of payments and of invoices.paid_amount / status / paid_at.
 * Web, Telegram and the renewal radar all call this, so "paid in full" means the same everywhere.
 */
class PaymentService
{
    /**
     * Record money received. Options: paid_on, method, reference, notes, source, idempotency_key,
     * currency (must equal the invoice currency), recorded_by.
     *
     * @param  array<string, mixed>  $opts
     */
    public function record(Invoice $invoice, mixed $amount, array $opts = []): Payment
    {
        return DB::transaction(function () use ($invoice, $amount, $opts) {
            if ($existing = $this->existing($opts['idempotency_key'] ?? null, $invoice)) {
                return $existing;
            }

            $locked = Invoice::whereKey($invoice->getKey())->lockForUpdate()->firstOrFail();

            return $this->insert($locked, Decimal::of($amount), $opts);
        });
    }

    /**
     * "Paid in full": records exactly the remaining balance. Returns null when nothing is owed,
     * so a second click or a repeated Telegram callback never creates another payment.
     *
     * @param  array<string, mixed>  $opts
     */
    public function payRemaining(Invoice $invoice, array $opts = []): ?Payment
    {
        return DB::transaction(function () use ($invoice, $opts) {
            if ($existing = $this->existing($opts['idempotency_key'] ?? null, $invoice)) {
                return $existing;
            }

            $locked = Invoice::whereKey($invoice->getKey())->lockForUpdate()->firstOrFail();
            $this->importOpeningBalance($locked);
            $balance = $this->balance($locked);
            if ($balance->isLessThanOrEqualTo(0)) {
                return null;
            }

            return $this->insert($locked, $balance, $opts);
        });
    }

    /**
     * Undo a payment by adding a negative row; the original stays in the history.
     *
     * @param  array<string, mixed>  $opts
     */
    public function reverse(Payment $payment, string $reason, array $opts = []): Payment
    {
        $reason = trim($reason);
        if ($reason === '') {
            throw new FinanceException('هۆکاری گەڕاندنەوەی پارەدان بنووسە.');
        }

        return DB::transaction(function () use ($payment, $reason, $opts) {
            $invoice = Invoice::whereKey($payment->invoice_id)->lockForUpdate()->firstOrFail();
            $payment = Payment::whereKey($payment->getKey())->lockForUpdate()->firstOrFail();

            if ($payment->type !== Payment::TYPE_PAYMENT) {
                throw new FinanceException('تەنها پارەدانی ئاسایی دەگەڕێندرێتەوە.');
            }
            if (Payment::where('reverses_payment_id', $payment->id)->exists()) {
                throw new FinanceException('ئەم پارەدانە پێشتر گەڕێندراوەتەوە.');
            }

            $reversal = Payment::create([
                'invoice_id' => $invoice->id,
                'type' => Payment::TYPE_REVERSAL,
                'reverses_payment_id' => $payment->id,
                'amount' => Decimal::str(Decimal::of($payment->amount)->negated()),
                'currency' => $payment->currency,
                'paid_on' => Carbon::parse($opts['paid_on'] ?? today())->toDateString(),
                'method' => $payment->method,
                'notes' => $reason,
                'recorded_by' => $opts['recorded_by'] ?? auth()->id(),
                'source' => $opts['source'] ?? 'manual',
                'idempotency_key' => $opts['idempotency_key'] ?? null,
            ]);

            $this->sync($invoice);
            $this->log($invoice, 'payment_reversed', 'پارەدانی ' . Money::format((float) $payment->amount, $payment->currency) . " گەڕێندرایەوە: {$reason}");

            return $reversal;
        });
    }

    /**
     * Recompute the cached paid_amount, status and paid_at from the payment rows.
     */
    public function refresh(Invoice $invoice): Invoice
    {
        return DB::transaction(function () use ($invoice) {
            $locked = Invoice::whereKey($invoice->getKey())->lockForUpdate()->firstOrFail();
            $this->importOpeningBalance($locked);
            $this->sync($locked);

            return $invoice->refresh();
        });
    }

    public function netPaid(Invoice $invoice): BigDecimal
    {
        return Decimal::sum(Payment::where('invoice_id', $invoice->id)->pluck('amount'));
    }

    public function balance(Invoice $invoice): BigDecimal
    {
        return Decimal::of($invoice->total)->minus($this->netPaid($invoice));
    }

    /**
     * An invoice that predates the payments table keeps its recorded paid_amount as one imported
     * payment. No date is invented: without paid_at the row has paid_on = null and needs review.
     * Idempotent (keyed per invoice). Returns the created row, or null when nothing was imported.
     */
    public function importOpeningBalance(Invoice $invoice): ?Payment
    {
        $plan = self::openingBalancePlan($invoice);
        if (! $plan) {
            return null;
        }

        return Payment::firstOrCreate(['idempotency_key' => $plan['idempotency_key']], $plan);
    }

    /**
     * What importOpeningBalance would create, without writing. Used by finance:backfill --dry-run.
     *
     * @return array<string, mixed>|null
     */
    public static function openingBalancePlan(Invoice $invoice): ?array
    {
        if (Payment::where('invoice_id', $invoice->id)->exists()) {
            return null;
        }

        $cached = Decimal::of($invoice->paid_amount);
        $total = Decimal::of($invoice->total);
        $notes = 'گواستراوە لە paid_amountی کۆنی وەسڵ.';
        $review = $invoice->paid_at === null;

        if ($invoice->status === 'paid' && $cached->isLessThan($total)) {
            // Marked paid by hand without an amount: the old status is the only evidence. Keep it, flag it.
            $amount = $total;
            $review = true;
            $notes = "دۆخی کۆن «دراوە» بوو بەڵام paid_amount = {$cached}. بڕی تەواو هاوردەکرا؛ پشتڕاستی بکەرەوە.";
        } elseif ($cached->isGreaterThan(0)) {
            $amount = $cached;
            if ($cached->isGreaterThan($total)) {
                $review = true;
                $notes .= ' بڕی دراو لە کۆی وەسڵ زیاترە.';
            }
        } else {
            return null;
        }

        if ($amount->isLessThanOrEqualTo(0)) {
            return null;
        }

        return [
            'invoice_id' => $invoice->id,
            'type' => Payment::TYPE_PAYMENT,
            'amount' => Decimal::str($amount),
            'currency' => $invoice->currency ?: 'USD',
            'paid_on' => $invoice->paid_at?->toDateString(),
            'method' => $invoice->payment_method,
            'notes' => $notes . ($invoice->paid_at ? '' : ' بەرواری پارەدان نەزانراوە.'),
            'source' => 'legacy_import',
            'idempotency_key' => "legacy:invoice:{$invoice->id}",
            'needs_review' => $review,
        ];
    }

    /** Payments are refused on these; cancelling needs net payments of zero. */
    public static function assertCanCancel(Invoice $invoice): void
    {
        if (Decimal::sum(Payment::where('invoice_id', $invoice->id)->pluck('amount'))->isGreaterThan(0)
            || ($invoice->status === 'paid' && ! Payment::where('invoice_id', $invoice->id)->exists() && Decimal::of($invoice->paid_amount)->isGreaterThan(0))) {
            throw new FinanceException('ئەم وەسڵە پارەی لەسەر تۆمارکراوە. سەرەتا پارەدانەکان بگەڕێنەوە، پاشان هەڵیبوەشێنەوە.');
        }
    }

    // ----------------------------------------------------------------- internals

    private function existing(?string $key, Invoice $invoice): ?Payment
    {
        if (! $key) {
            return null;
        }
        $payment = Payment::where('idempotency_key', $key)->first();
        if ($payment && $payment->invoice_id !== $invoice->id) {
            throw new FinanceException('ئەم داواکارییە پێشتر بۆ وەسڵێکی تر بەکارهاتووە.');
        }

        return $payment;
    }

    /**
     * @param  array<string, mixed>  $opts
     */
    private function insert(Invoice $invoice, BigDecimal $amount, array $opts): Payment
    {
        if ($invoice->status === 'draft') {
            throw new FinanceException('وەسڵی ڕەشنووس هێشتا دەرنەچووە؛ سەرەتا دۆخەکەی بکە بە «نێردراوە».');
        }
        if ($invoice->status === 'cancelled') {
            throw new FinanceException('وەسڵی هەڵوەشاوە پارەی بۆ تۆمار ناکرێت.');
        }
        if ($amount->isLessThanOrEqualTo(0)) {
            throw new FinanceException('بڕی پارەدان دەبێت لە سفر زیاتر بێت.');
        }
        $currency = strtoupper((string) ($invoice->currency ?: 'USD'));
        if (isset($opts['currency']) && strtoupper((string) $opts['currency']) !== $currency) {
            throw new FinanceException("پارەدان دەبێت بە هەمان دراوی وەسڵ ({$currency}) بێت؛ گۆڕینەوەی دراو هێشتا پشتگیری ناکرێت.");
        }

        $this->importOpeningBalance($invoice);
        $balance = $this->balance($invoice);
        if ($amount->isGreaterThan($balance)) {
            throw new FinanceException('بڕەکە لە قەرزی ماوە زیاترە. ماوە: ' . Money::format($balance->toFloat(), $currency) . '.');
        }

        $paidOn = Carbon::parse($opts['paid_on'] ?? today())->startOfDay();
        $payment = Payment::create([
            'invoice_id' => $invoice->id,
            'type' => Payment::TYPE_PAYMENT,
            'amount' => Decimal::str($amount),
            'currency' => $currency,
            'paid_on' => $paidOn->toDateString(),
            'method' => $opts['method'] ?? null,
            'reference' => $opts['reference'] ?? null,
            'notes' => $opts['notes'] ?? null,
            'recorded_by' => $opts['recorded_by'] ?? auth()->id(),
            'source' => $opts['source'] ?? 'manual',
            'idempotency_key' => $opts['idempotency_key'] ?? null,
        ]);

        $this->sync($invoice);
        $this->log($invoice, 'invoice_paid', 'پارەی ' . Money::format($amount->toFloat(), $currency) . " بۆ وەسڵی {$invoice->invoice_number} وەرگیرا");

        return $payment;
    }

    private function sync(Invoice $invoice): void
    {
        $payments = Payment::where('invoice_id', $invoice->id)->get(['amount', 'paid_on', 'type']);
        $net = Decimal::sum($payments->pluck('amount'));
        $total = Decimal::of($invoice->total);

        $status = $invoice->status;
        if (! in_array($status, ['draft', 'cancelled'], true)) {
            $status = match (true) {
                $net->isGreaterThanOrEqualTo($total) && $net->isGreaterThan(0) => 'paid',
                $net->isGreaterThan(0) => 'partial',
                default => 'sent', // "overdue" is derived from due_date, not stored
            };
        }

        $lastPaidOn = $payments->where('type', Payment::TYPE_PAYMENT)->pluck('paid_on')->filter()->max();

        $invoice->forceFill([
            'paid_amount' => Decimal::str($net),
            'status' => $status,
            'paid_at' => $status === 'paid' ? ($lastPaidOn ? Carbon::parse($lastPaidOn) : ($invoice->paid_at ?? null)) : null,
        ])->save();
    }

    private function log(Invoice $invoice, string $type, string $message): void
    {
        ActivityReminder::create([
            'client_id' => $invoice->client_id,
            'subscription_id' => $invoice->subscription_id,
            'invoice_id' => $invoice->id,
            'type' => $type,
            'channel' => 'system',
            'message' => $message,
            'status' => 'sent',
            'sent_at' => now(),
        ]);
    }
}
