<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * An invoice can bill several services, so each line links to its own service and period
 * (the invoice-level subscription_id is not enough). Issued invoices keep a snapshot of the client.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('invoice_items', function (Blueprint $table) {
            $table->foreignId('subscription_id')->nullable()->constrained('subscriptions')->nullOnDelete();
            $table->foreignId('service_period_id')->nullable()->constrained('service_periods')->nullOnDelete();
            $table->boolean('custom_period')->default(false); // dates typed by hand instead of start + duration
            $table->string('period_note')->nullable();       // why the period is custom
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->json('client_snapshot')->nullable(); // name/phone/address as printed when issued
        });
    }

    public function down(): void
    {
        Schema::table('invoice_items', function (Blueprint $table) {
            $table->dropForeign(['subscription_id']);
            $table->dropForeign(['service_period_id']);
            $table->dropColumn(['subscription_id', 'service_period_id', 'custom_period', 'period_note']);
        });
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('client_snapshot');
        });
    }
};
