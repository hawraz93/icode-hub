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
    }
};
