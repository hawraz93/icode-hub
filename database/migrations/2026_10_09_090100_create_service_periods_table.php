<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * History of a service's periods: [starts_on, expires_on). Renewing adds a row and keeps the old one.
 * subscriptions.start_date / expiry_date stay as a mirror of the current period for older screens.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('service_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->constrained('subscriptions')->cascadeOnDelete();
            $table->date('starts_on');
            $table->date('expires_on');
            $table->string('status', 20)->default('active'); // active, superseded, cancelled
            $table->foreignId('renewed_from_id')->nullable()->constrained('service_periods')->nullOnDelete();
            $table->string('billing_unit', 10)->default('year'); // month, year
            $table->unsignedSmallInteger('billing_count')->default(1);
            $table->decimal('price', 12, 2)->default(0); // agreed price for the whole period
            $table->string('currency', 10)->default('USD');
            $table->string('origin', 20)->default('initial'); // initial, renewal, import, invoice
            $table->string('idempotency_key', 120)->nullable()->unique();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['subscription_id', 'status']);
            $table->index('expires_on');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_periods');
    }
};
