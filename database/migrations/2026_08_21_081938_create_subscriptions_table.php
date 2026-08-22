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
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $table->foreignId('server_id')->nullable()->constrained('servers')->nullOnDelete();
            $table->string('name');
            $table->enum('type', ['domain', 'hosting', 'email', 'vps', 'license', 'maintenance', 'other'])->default('domain');
            $table->string('domain_name')->nullable();
            $table->string('provider')->nullable(); // Namecheap, GoDaddy, Hetzner, Cloudflare, etc.
            $table->decimal('cost_price', 10, 2)->default(0.00); // What Hawraz pays
            $table->decimal('selling_price', 10, 2)->default(0.00); // What client pays
            $table->string('currency', 10)->default('USD');
            $table->enum('billing_cycle', ['monthly', 'quarterly', 'semi_annual', 'annual', 'biennial'])->default('annual');
            $table->date('start_date');
            $table->date('expiry_date');
            $table->boolean('auto_renew')->default(false);
            $table->enum('status', ['active', 'expired', 'grace_period', 'cancelled'])->default('active');
            $table->integer('reminder_days_before')->default(30);
            $table->timestamp('last_reminded_at')->nullable();
            $table->text('credentials_note')->nullable(); // FTP/cPanel/DNS notes
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
