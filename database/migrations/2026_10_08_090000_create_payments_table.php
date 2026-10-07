<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Payments become the source of truth for money received against an invoice.
 * invoices.paid_amount stays as a cached sum that only PaymentService writes.
 * Add-only: existing invoice data is untouched; `php artisan finance:backfill` imports it.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            // Financial history must survive: an invoice with payments cannot be hard-deleted.
            $table->foreignId('invoice_id')->constrained('invoices')->restrictOnDelete();
            // payment = money in; reversal = negative correction of an earlier row (the original is never deleted)
            $table->string('type', 20)->default('payment');
            $table->foreignId('reverses_payment_id')->nullable()->constrained('payments')->restrictOnDelete();
            $table->decimal('amount', 12, 2); // signed: reversals are negative
            $table->string('currency', 10);
            $table->date('paid_on')->nullable(); // null only for imported history with an unknown date
            $table->string('method')->nullable();
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('source', 30)->default('manual'); // manual, telegram, radar, legacy_import
            $table->string('idempotency_key', 120)->nullable()->unique();
            $table->boolean('needs_review')->default(false);
            $table->timestamps();

            $table->index(['invoice_id', 'type']);
            $table->index('paid_on');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
