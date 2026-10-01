<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            // Simple flag: has the client paid for the current period?
            $table->boolean('is_paid')->default(true)->after('status');
            $table->timestamp('paid_at')->nullable()->after('is_paid');
        });

        Schema::table('servers', function (Blueprint $table) {
            // The "servers" table now holds everything you buy for yourself: server, domain, email, other.
            $table->string('kind', 20)->default('server')->after('name');
            $table->string('billing_cycle', 20)->default('monthly')->change();
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn(['is_paid', 'paid_at']);
        });
        Schema::table('servers', function (Blueprint $table) {
            $table->dropColumn('kind');
        });
    }
};
