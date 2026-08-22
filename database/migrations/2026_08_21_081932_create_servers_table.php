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
        Schema::create('servers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('provider')->nullable(); // Hetzner, DigitalOcean, Contabo, AWS, Hostinger
            $table->string('ip_address')->nullable();
            $table->string('location')->nullable();
            $table->string('specs')->nullable(); // CPU, RAM, Disk
            $table->decimal('cost', 10, 2)->default(0.00);
            $table->string('currency', 10)->default('USD');
            $table->enum('billing_cycle', ['monthly', 'quarterly', 'semi_annual', 'annual'])->default('monthly');
            $table->date('purchase_date')->nullable();
            $table->date('renewal_date');
            $table->enum('status', ['active', 'suspended', 'terminated'])->default('active');
            $table->boolean('auto_renew')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('servers');
    }
};
