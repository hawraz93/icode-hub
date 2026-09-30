<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            // 0 = not contacted, 1 = client notified, 2 = client paid (provider renewal pending)
            $table->unsignedTinyInteger('renewal_stage')->default(0)->after('status');
            $table->timestamp('stage_updated_at')->nullable()->after('renewal_stage');
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn(['renewal_stage', 'stage_updated_at']);
        });
    }
};
