<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            // Expiry date as reported by the registry over RDAP (null when the TLD has no RDAP service)
            $table->date('registry_expiry_date')->nullable()->after('expiry_date');
            $table->timestamp('registry_checked_at')->nullable()->after('registry_expiry_date');
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn(['registry_expiry_date', 'registry_checked_at']);
        });
    }
};
