<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `expenses` becomes the ledger of money actually paid. Old columns (billing_cycle) stay for
 * compatibility; rows whose meaning is unclear are flagged needs_review by finance:backfill.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->foreignId('expense_schedule_id')->nullable()->constrained('expense_schedules')->nullOnDelete();
            $table->foreignId('server_id')->nullable()->constrained('servers')->nullOnDelete();
            $table->foreignId('project_id')->nullable()->constrained('projects')->nullOnDelete();
            $table->date('period_start')->nullable();
            $table->date('period_end')->nullable();
            $table->string('reference')->nullable();
            $table->string('status', 20)->default('posted'); // posted, void
            $table->string('void_reason')->nullable();
            $table->timestamp('voided_at')->nullable();
            $table->string('idempotency_key', 120)->nullable()->unique();
            $table->boolean('needs_review')->default(false);
            $table->string('review_note')->nullable();

            $table->index(['status', 'expense_date']);
        });
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropForeign(['expense_schedule_id']);
            $table->dropForeign(['server_id']);
            $table->dropForeign(['project_id']);
            $table->dropIndex(['status', 'expense_date']);
            $table->dropUnique(['idempotency_key']);
            $table->dropColumn([
                'expense_schedule_id', 'server_id', 'project_id', 'period_start', 'period_end', 'reference',
                'status', 'void_reason', 'voided_at', 'idempotency_key', 'needs_review', 'review_note',
            ]);
        });
    }
};
