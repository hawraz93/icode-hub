<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Expense;
use App\Models\ExpenseSchedule;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Data written the way the app stored it before the ledger existed (raw inserts, no model hooks).
 */
class FinanceBackfillTest extends TestCase
{
    use RefreshDatabase;

    private function seedLegacyData(): void
    {
        $client = Client::create(['name' => 'Old client']);
        $now = now();
        DB::table('servers')->insert([
            ['name' => 'Contabo VPS', 'kind' => 'server', 'provider' => 'Contabo', 'cost' => 8, 'currency' => 'USD', 'billing_cycle' => 'monthly', 'renewal_date' => '2026-10-20', 'status' => 'active', 'auto_renew' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'icodegroup.net', 'kind' => 'domain', 'provider' => null, 'cost' => 30, 'currency' => 'USD', 'billing_cycle' => 'biennial', 'renewal_date' => '2027-03-01', 'status' => 'active', 'auto_renew' => 0, 'created_at' => $now, 'updated_at' => $now],
        ]);
        $invoice = fn (string $n, array $a) => DB::table('invoices')->insert(array_merge([
            'invoice_number' => $n, 'client_id' => $client->id, 'issue_date' => '2026-05-01', 'due_date' => '2026-05-15',
            'subtotal' => 100, 'total' => 100, 'paid_amount' => 0, 'currency' => 'USD', 'status' => 'sent', 'created_at' => $now, 'updated_at' => $now,
        ], $a));
        $invoice('OLD-1', ['paid_amount' => 100, 'status' => 'paid', 'paid_at' => '2026-05-10 12:00:00']); // known date
        $invoice('OLD-2', ['paid_amount' => 40, 'status' => 'partial']);                                    // date unknown
        $invoice('OLD-3', ['status' => 'paid']);                                                              // "paid" without amount
        $invoice('OLD-4', ['currency' => 'IQD', 'total' => 50000, 'subtotal' => 50000]);                     // nothing paid
        DB::table('expenses')->insert([
            ['title' => 'Contabo VPS', 'category' => 'infrastructure', 'amount' => 8, 'currency' => 'USD', 'billing_cycle' => 'monthly', 'expense_date' => '2026-09-20', 'payment_method' => 'card', 'vendor' => 'Contabo', 'created_at' => $now, 'updated_at' => $now],
            ['title' => 'Fuel', 'category' => 'transport', 'amount' => 10000, 'currency' => 'IQD', 'billing_cycle' => 'one_time', 'expense_date' => '2026-09-21', 'payment_method' => 'cash', 'vendor' => null, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function test_dry_run_reports_and_writes_nothing(): void
    {
        $this->seedLegacyData();

        $this->artisan('finance:backfill', ['--dry-run' => true])
            ->expectsOutputToContain('DRY RUN')
            ->expectsOutputToContain('Servers without an expense schedule: 2')
            ->expectsOutputToContain('imported payment: 3 (unknown date: 2, needs review: 2)')
            ->expectsOutputToContain('Possible duplicate VPS expenses (report only, nothing deleted): 1')
            ->assertSuccessful();

        $this->assertSame(0, Payment::count());
        $this->assertSame(0, ExpenseSchedule::count());
        $this->assertSame(0, Expense::where('needs_review', true)->count());
    }

    public function test_backfill_imports_without_inventing_money_or_dates_and_is_idempotent(): void
    {
        $this->seedLegacyData();
        $totalsBefore = Invoice::orderBy('id')->pluck('total', 'invoice_number')->all();

        $this->artisan('finance:backfill')->assertSuccessful();

        // One schedule per server; no historical expenses created from Server.cost / renewal_date.
        $this->assertSame(2, ExpenseSchedule::count());
        $this->assertSame(2, Expense::count());
        $domain = ExpenseSchedule::where('title', 'icodegroup.net')->sole();
        $this->assertSame(['year', 2, '30.00'], [$domain->cycle_unit, $domain->cycle_count, (string) $domain->amount_per_cycle]);

        // Payments: known date kept, unknown date stays null, nothing for the unpaid invoice.
        $byInvoice = Payment::with('invoice')->get()->keyBy(fn ($p) => $p->invoice->invoice_number);
        $this->assertSame('2026-05-10', $byInvoice['OLD-1']->paid_on->toDateString());
        $this->assertNull($byInvoice['OLD-2']->paid_on);
        $this->assertTrue($byInvoice['OLD-2']->needs_review);
        $this->assertTrue($byInvoice['OLD-3']->needs_review);
        $this->assertArrayNotHasKey('OLD-4', $byInvoice->all());
        $this->assertSame('partial', Invoice::where('invoice_number', 'OLD-2')->value('status'));

        // Ambiguous old "monthly" expense is flagged, not converted.
        $this->assertTrue(Expense::where('title', 'Contabo VPS')->value('needs_review'));
        $this->assertFalse((bool) Expense::where('title', 'Fuel')->value('needs_review'));

        // Invoice numbers and totals unchanged.
        $this->assertSame($totalsBefore, Invoice::orderBy('id')->pluck('total', 'invoice_number')->all());

        // Second run changes nothing.
        $counts = [Payment::count(), ExpenseSchedule::count(), Expense::count(), Expense::where('needs_review', true)->count()];
        $this->artisan('finance:backfill')->expectsOutputToContain('Servers without an expense schedule: 0')->assertSuccessful();
        $this->assertSame($counts, [Payment::count(), ExpenseSchedule::count(), Expense::count(), Expense::where('needs_review', true)->count()]);
    }

    public function test_dashboard_shows_unmigrated_servers_until_backfill_without_double_counting(): void
    {
        $this->actingAs(\App\Models\User::factory()->create());
        $this->seedLegacyData();

        $before = \Livewire\Livewire::test(\App\Livewire\Admin\Dashboard::class)->viewData('annualCosts');
        $this->artisan('finance:backfill')->assertSuccessful();
        $after = \Livewire\Livewire::test(\App\Livewire\Admin\Dashboard::class)->viewData('annualCosts');

        $this->assertSame(['USD' => 111.0], $before); // 8 x 12 + 30 / 2
        $this->assertSame($before, $after);
    }
}
