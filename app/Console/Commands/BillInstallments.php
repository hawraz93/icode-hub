<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use Illuminate\Console\Command;

class BillInstallments extends Command
{
    protected $signature = 'renewals:bill-installments';

    protected $description = 'Create this month\'s invoice for services the client pays monthly';

    public function handle(): int
    {
        $created = Subscription::where('payment_plan', 'monthly')
            ->where('status', '!=', 'cancelled')
            ->get()
            ->map(fn (Subscription $sub) => $sub->billMonthlyInstallment())
            ->filter();

        foreach ($created as $invoice) {
            $this->line("{$invoice->invoice_number} · {$invoice->total} {$invoice->currency} · due {$invoice->due_date->format('Y-m-d')}");
        }
        $this->info($created->count() . ' installment invoice(s) created.');

        return self::SUCCESS;
    }
}
