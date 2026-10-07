<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Projects become the hub of client work; services can belong to a project.
 * Add-only. The single data step keeps today's behaviour: every existing project was shown on the
 * public portfolio, so existing rows get is_public = true; new projects are private by default.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->foreignId('project_id')->nullable()->after('client_id')->constrained('projects')->nullOnDelete();
            // shared_infrastructure (on our own VPS), direct_purchase (bought for this client), unknown
            $table->string('cost_basis', 30)->default('unknown')->after('cost_price');
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->date('start_date')->nullable();
            $table->boolean('is_public')->default(false); // portfolio publication, separate from work status
            $table->timestamp('archived_at')->nullable();
            $table->text('internal_notes')->nullable();
        });

        DB::table('projects')->update(['is_public' => true]);
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropForeign(['project_id']);
            $table->dropColumn(['project_id', 'cost_basis']);
        });
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn(['start_date', 'is_public', 'archived_at', 'internal_notes']);
        });
    }
};
