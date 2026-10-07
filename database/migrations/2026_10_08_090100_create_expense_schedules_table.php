<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Recurring expense plans (VPS, AI subscriptions, internet...): price, cycle and next due date.
 * A schedule is a plan, never a payment; money actually paid lives in `expenses`.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('expense_schedules', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('category')->default('infrastructure');
            $table->string('vendor')->nullable();
            $table->string('currency', 10)->default('USD');
            $table->decimal('amount_per_cycle', 12, 2); // price of one whole cycle (a 2-year cycle = the 2-year price)
            $table->string('cycle_unit', 10)->default('month'); // month, year
            $table->unsignedSmallInteger('cycle_count')->default(1);
            $table->date('starts_on')->nullable();
            $table->date('next_due_on');
            $table->date('ends_on')->nullable();
            $table->string('status', 20)->default('active'); // active, paused, ended
            $table->boolean('auto_renew')->default(false);
            $table->string('payment_method')->nullable();
            $table->foreignId('server_id')->nullable()->unique()->constrained('servers')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->string('legacy_source', 30)->nullable();
            $table->unsignedBigInteger('legacy_id')->nullable();
            $table->timestamps();

            $table->unique(['legacy_source', 'legacy_id']);
            $table->index(['status', 'next_due_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expense_schedules');
    }
};
