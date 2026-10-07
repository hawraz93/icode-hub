<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('invoice_items', function (Blueprint $table) {
            $table->string('billing_cycle')->default('one_time');
            $table->date('start_date')->nullable();
            $table->date('expiry_date')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('invoice_items', fn (Blueprint $table) => $table->dropColumn(['billing_cycle', 'start_date', 'expiry_date']));
    }
};
