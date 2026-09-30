<?php

use App\Models\Subscription;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            // Which service an invoice (or outstanding debt) belongs to
            $table->foreignId('subscription_id')->nullable()->after('client_id')->constrained('subscriptions')->nullOnDelete();
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            // upfront = client pays the whole cycle price; monthly = client pays an installment every month
            $table->string('payment_plan', 20)->default('upfront')->after('billing_cycle');
            $table->decimal('installment_amount', 10, 2)->nullable()->after('payment_plan');
            $table->unsignedTinyInteger('payment_day')->default(1)->after('installment_amount');
        });

        // Clean domains typed as URLs ("https://finance.icodegroup.net/") and fix names that embedded them.
        DB::table('subscriptions')->whereNotNull('domain_name')->orderBy('id')->each(function ($row) {
            $clean = Subscription::normalizeDomain($row->domain_name);
            if ($clean !== $row->domain_name) {
                DB::table('subscriptions')->where('id', $row->id)->update([
                    'domain_name' => $clean,
                    'name' => str_replace([$row->domain_name, rtrim($row->domain_name, '/')], $clean, $row->name),
                ]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropConstrainedForeignId('subscription_id');
        });
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn(['payment_plan', 'installment_amount', 'payment_day']);
        });
    }
};
