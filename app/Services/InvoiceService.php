<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Contract;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Project;
use App\Models\Subscription;
use App\Support\Decimal;
use App\Support\Period;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Creates and edits invoices with the same rules for every caller (web form, imports, bots):
 * ownership of project/contract/services, service periods computed from start + duration,
 * exact decimal totals, client snapshot on issue, and the payment-derived status.
 */
class InvoiceService
{
    /** Line types that describe a client service and can create one. */
    public const SERVICE_TYPES = ['hosting', 'domain', 'email', 'vps', 'maintenance', 'license'];

    public function __construct(private PaymentService $payments)
    {
    }

    /**
     * @param  array<string, mixed>  $header  client_id, project_id, contract_id, invoice_number, issue_date, due_date,
     *                                        discount, tax, currency, status, payment_method, notes, terms,
     *                                        allow_due_before_issue (imports of old invoices only)
     * @param  array<int, array<string, mixed>>  $items  description, quantity, unit_price, service_type, billing_cycle,
     *                                        start_date, expiry_date, custom_period, period_note, subscription_id,
     *                                        service_period_id, create_service
     */
    public function save(?Invoice $existing, array $header, array $items): Invoice
    {
        $items = $this->normalizeItems($items);
        $this->assertHeader($header);
        $this->assertOwnership($header, $items);

        $subtotal = Decimal::sum(array_map(fn ($i) => Decimal::mul($i['unit_price'], $i['quantity']), $items));
        $total = $subtotal->minus(Decimal::of($header['discount'] ?? 0))->plus(Decimal::of($header['tax'] ?? 0));
        if ($total->isLessThan(0)) {
            $total = Decimal::of(0);
        }

        if ($existing) {
            $this->assertEditable($existing, $header, $total);
        }

        $data = [
            'client_id' => $header['client_id'],
            'contract_id' => $header['contract_id'] ?: null,
            'project_id' => $header['project_id'] ?: null,
            'invoice_number' => $header['invoice_number'],
            'issue_date' => $header['issue_date'],
            'due_date' => $header['due_date'],
            'subtotal' => Decimal::str($subtotal),
            'discount' => Decimal::str(Decimal::of($header['discount'] ?? 0)),
            'tax' => Decimal::str(Decimal::of($header['tax'] ?? 0)),
            'total' => Decimal::str($total),
            'currency' => $header['currency'],
            'status' => $header['status'],
            'payment_method' => $header['payment_method'] ?? null,
            'notes' => $header['notes'] ?? null,
            'terms' => $header['terms'] ?? null,
        ];

        return DB::transaction(function () use ($existing, $data, $items) {
            if ($existing) {
                $existing->update($data);
                $invoice = $existing;
                $invoice->items()->delete();
            } else {
                $invoice = Invoice::createNumbered($data + ['paid_amount' => 0]);
            }

            foreach ($items as $item) {
                $this->createItem($invoice, $item);
            }

            if ($invoice->status !== 'draft' && ! $invoice->client_snapshot) {
                $invoice->forceFill(['client_snapshot' => self::clientSnapshot($invoice->client)])->save();
            }

            $this->payments->refresh($invoice);

            return $invoice->fresh(['items']);
        });
    }

    /**
     * Recurring lines: whole months/years, start required, expiry = start + duration unless the
     * period is marked custom with a reason. One-time lines carry no dates.
     *
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, array<string, mixed>>
     */
    public function normalizeItems(array $items): array
    {
        $errors = [];
        foreach (array_values($items) as $i => $item) {
            $cycle = $item['billing_cycle'] ?? 'one_time';
            $item['custom_period'] = (bool) ($item['custom_period'] ?? false);
            $item['create_service'] = (bool) ($item['create_service'] ?? false);
            $item['subscription_id'] = ($item['subscription_id'] ?? null) ?: null;
            $item['service_period_id'] = ($item['service_period_id'] ?? null) ?: null;

            if (Decimal::of($item['quantity'] ?? 0)->isLessThanOrEqualTo(0)) {
                $errors["items.{$i}.quantity"] = 'ژمارە دەبێت لە سفر زیاتر بێت.';
            }
            if (Decimal::of($item['unit_price'] ?? 0)->isLessThan(0)) {
                $errors["items.{$i}.unit_price"] = 'نرخ نابێت نەرێنی بێت.';
            }

            if ($cycle === 'one_time') {
                $item['start_date'] = $item['expiry_date'] = null;
                $item['custom_period'] = false;
                $item['period_note'] = null;
                $item['create_service'] = false;
                $items[$i] = $item;
                continue;
            }

            $unit = $cycle === 'monthly' ? 'month' : 'year';
            $qty = (float) ($item['quantity'] ?? 0);
            if ($qty < 1 || $qty != (int) $qty) {
                $errors["items.{$i}.quantity"] = $unit === 'year' ? 'ژمارەی ساڵ دەبێت ژمارەیەکی تەواو بێت (١، ٢، ...).' : 'ژمارەی مانگ دەبێت ژمارەیەکی تەواو بێت.';
                $items[$i] = $item;
                continue;
            }
            if (empty($item['start_date'])) {
                $errors["items.{$i}.start_date"] = 'ڕۆژی دەستپێکی خزمەتگوزاری پێویستە.';
                $items[$i] = $item;
                continue;
            }

            $start = Carbon::parse($item['start_date'])->startOfDay();
            $computed = Period::add($start, $unit, (int) $qty);
            if ($item['custom_period']) {
                if (empty($item['expiry_date']) || Carbon::parse($item['expiry_date'])->lte($start)) {
                    $errors["items.{$i}.expiry_date"] = 'بەرواری بەسەرچوون دەبێت دوای دەستپێک بێت.';
                }
                if (trim((string) ($item['period_note'] ?? '')) === '') {
                    $errors["items.{$i}.period_note"] = 'بۆ ماوەی دەستی هۆکار بنووسە.';
                }
            } else {
                // Computed here, so a tampered payload or an import cannot disagree with duration × price.
                $item['expiry_date'] = $computed->toDateString();
                $item['period_note'] = null;
            }
            $item['start_date'] = $start->toDateString();
            $item['quantity'] = (int) $qty;
            $items[$i] = $item;
        }

        if ($errors) {
            throw ValidationException::withMessages($errors);
        }

        return array_values($items);
    }

    /**
     * Project, contract and services on an invoice must all belong to the invoice's client.
     *
     * @param  array<string, mixed>  $header
     * @param  array<int, array<string, mixed>>  $items
     */
    public function assertOwnership(array $header, array $items): void
    {
        $clientId = (int) $header['client_id'];
        $errors = [];

        $project = ! empty($header['project_id']) ? Project::find($header['project_id']) : null;
        if (! empty($header['project_id']) && (! $project || (int) $project->client_id !== $clientId)) {
            $errors['project_id'] = 'ئەم پڕۆژەیە هی ئەم کڕیارە نییە.';
        }
        if ($project?->archived_at) {
            $errors['project_id'] = 'پڕۆژەکە ئەرشیف کراوە.';
        }

        if (! empty($header['contract_id'])) {
            $contract = Contract::find($header['contract_id']);
            if (! $contract || (int) $contract->client_id !== $clientId) {
                $errors['contract_id'] = 'ئەم گرێبەستە هی ئەم کڕیارە نییە.';
            } elseif ($contract->project_id && $project && (int) $contract->project_id !== (int) $project->id) {
                $errors['contract_id'] = 'ئەم گرێبەستە هی پڕۆژەیەکی ترە.';
            }
        }

        foreach ($items as $i => $item) {
            if (! $item['subscription_id']) {
                continue;
            }
            $sub = Subscription::find($item['subscription_id']);
            if (! $sub || (int) $sub->client_id !== $clientId) {
                $errors["items.{$i}.subscription_id"] = 'ئەم خزمەتگوزارییە هی ئەم کڕیارە نییە.';
            } elseif ($project && $sub->project_id && (int) $sub->project_id !== (int) $project->id) {
                $errors["items.{$i}.subscription_id"] = 'ئەم خزمەتگوزارییە هی پڕۆژەیەکی ترە.';
            } elseif (strtoupper((string) $sub->currency) !== strtoupper((string) $header['currency'])) {
                $errors["items.{$i}.subscription_id"] = 'دراوی خزمەتگوزاری و وەسڵ جیاوازن.';
            }
            if ($item['service_period_id'] && $sub && ! $sub->periods()->whereKey($item['service_period_id'])->exists()) {
                $errors["items.{$i}.subscription_id"] = 'ماوەکە هی ئەم خزمەتگوزارییە نییە.';
            }
        }

        if ($errors) {
            throw ValidationException::withMessages($errors);
        }
    }

    /**
     * @param  array<string, mixed>  $header
     */
    private function assertHeader(array $header): void
    {
        if (! Client::whereKey($header['client_id'] ?? 0)->exists()) {
            throw ValidationException::withMessages(['client_id' => 'کڕیار هەڵبژێرە.']);
        }
        if (! in_array($header['currency'] ?? null, ['USD', 'IQD'], true)) {
            throw ValidationException::withMessages(['currency' => 'دراو دەبێت USD یان IQD بێت.']);
        }
        if (empty($header['allow_due_before_issue']) && Carbon::parse($header['due_date'])->lt(Carbon::parse($header['issue_date']))) {
            throw ValidationException::withMessages(['due_date' => 'بەرواری پارەدان نابێت پێش بەرواری دەرچوون بێت.']);
        }
    }

    /**
     * Rules for changing an invoice that already exists.
     *
     * @param  array<string, mixed>  $header
     */
    private function assertEditable(Invoice $existing, array $header, \Brick\Math\BigDecimal $total): void
    {
        $netPaid = $this->payments->netPaid($existing);
        $paidSoFar = $netPaid->isGreaterThan(0) ? $netPaid : Decimal::of($existing->paid_amount);
        $hasMoney = $existing->payments()->exists() || Decimal::of($existing->paid_amount)->isGreaterThan(0);

        if ($hasMoney && $existing->currency !== $header['currency']) {
            throw ValidationException::withMessages(['currency' => 'دراوی وەسڵێک کە پارەی لەسەر تۆمارکراوە ناگۆڕدرێت.']);
        }
        if ($existing->status === 'paid' && ! $total->isEqualTo(Decimal::of($existing->total))) {
            throw ValidationException::withMessages(['discount' => 'نرخی وەسڵی پاکتاوکراو ناگۆڕدرێت. بۆ ڕاستکردنەوە پارەدانێک بگەڕێنەوە یان وەسڵێکی تر دەربکە.']);
        }
        if ($total->isLessThan($paidSoFar)) {
            throw ValidationException::withMessages(['discount' => 'کۆی نوێی وەسڵ لە بڕی دراو کەمترە. سەرەتا پارەدانێک بگەڕێنەوە.']);
        }
        if (($header['status'] ?? null) === 'cancelled' && $existing->status !== 'cancelled') {
            try {
                PaymentService::assertCanCancel($existing);
            } catch (\App\Exceptions\FinanceException $e) {
                throw ValidationException::withMessages(['status' => $e->getMessage()]);
            }
        }
        if (($header['status'] ?? null) === 'draft' && $existing->status !== 'draft' && $hasMoney) {
            throw ValidationException::withMessages(['status' => 'وەسڵێک کە پارەی لەسەرە ناگەڕێتەوە ڕەشنووس.']);
        }
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function createItem(Invoice $invoice, array $item): InvoiceItem
    {
        $lineTotal = Decimal::mul($item['unit_price'], $item['quantity']);
        $subscriptionId = $item['subscription_id'];
        $periodId = $item['service_period_id'];

        if (! $subscriptionId && $item['create_service'] && in_array($item['service_type'], self::SERVICE_TYPES, true)) {
            [$subscriptionId, $periodId] = $this->createServiceFromItem($invoice, $item, $lineTotal);
        } elseif ($subscriptionId && ! $periodId && $item['start_date']) {
            // Link to an existing period with exactly these dates; never invent one here.
            $periodId = \App\Models\ServicePeriod::where('subscription_id', $subscriptionId)
                ->whereDate('starts_on', $item['start_date'])->whereDate('expires_on', $item['expiry_date'])
                ->value('id');
        }

        return InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'description' => $item['description'],
            'quantity' => $item['quantity'],
            'unit_price' => Decimal::str(Decimal::of($item['unit_price'])),
            'total_price' => Decimal::str($lineTotal),
            'service_type' => $item['service_type'] ?? 'development',
            'billing_cycle' => $item['billing_cycle'] ?? 'one_time',
            'start_date' => $item['start_date'],
            'expiry_date' => $item['expiry_date'],
            'custom_period' => $item['custom_period'],
            'period_note' => $item['period_note'] ?? null,
            'subscription_id' => $subscriptionId,
            'service_period_id' => $periodId,
        ]);
    }

    /**
     * "Hosting 100/year × 2 from 2026-10-07" on a new project becomes a service with its first
     * period (2026-10-07 → 2028-10-07, agreed price 200) and a renewal price of 100/year.
     *
     * @param  array<string, mixed>  $item
     * @return array{0: int, 1: int}
     */
    private function createServiceFromItem(Invoice $invoice, array $item, \Brick\Math\BigDecimal $lineTotal): array
    {
        $type = $item['service_type'];
        $sub = Subscription::create([
            'client_id' => $invoice->client_id,
            'project_id' => $invoice->project_id,
            'name' => $item['description'],
            'type' => $type,
            'selling_price' => Decimal::str(Decimal::of($item['unit_price'])), // renewal price per month/year
            'cost_price' => 0,
            // "Shared VPS" needs the hosting server chosen; until someone picks it the cost is unknown.
            'cost_basis' => 'unknown',
            'currency' => $invoice->currency,
            'billing_cycle' => $item['billing_cycle'] === 'monthly' ? 'monthly' : 'annual',
            'start_date' => $item['start_date'],
            'expiry_date' => $item['expiry_date'],
            'status' => Carbon::parse($item['expiry_date'])->isPast() ? 'expired' : 'active',
            // Money owed is tracked on this invoice; the simple per-service flag would count it twice.
            'is_paid' => true,
            'reminder_days_before' => 30,
            'notes' => "لە وەسڵی {$invoice->invoice_number} دروستکرا.",
        ]);

        $period = $sub->currentPeriod()->firstOrFail();
        $period->update([
            'price' => Decimal::str($lineTotal),
            'origin' => 'invoice',
            'notes' => $item['custom_period'] ? $item['period_note'] : null,
        ]);

        return [$sub->id, $period->id];
    }

    /**
     * @return array<string, string|null>
     */
    public static function clientSnapshot(?Client $client): array
    {
        return [
            'name' => $client?->name,
            'business_name' => $client?->business_name,
            'phone' => $client?->phone,
            'email' => $client?->email,
            'city' => $client?->city,
            'address' => $client?->address,
        ];
    }
}
