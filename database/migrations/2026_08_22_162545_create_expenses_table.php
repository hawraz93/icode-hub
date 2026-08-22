<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('category')->default('other'); // infrastructure, software_ai, telecom, transport, office, marketing, other
            $table->decimal('amount', 12, 2);
            $table->string('currency', 10)->default('USD'); // USD or IQD
            $table->string('billing_cycle', 20)->default('one_time'); // one_time, monthly, annual
            $table->date('expense_date');
            $table->string('payment_method')->default('cash'); // cash, fastpay, fib, zaincash, card
            $table->string('vendor')->nullable(); // OpenAI, Anthropic, AsiaCell, Korek, Zain...
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
