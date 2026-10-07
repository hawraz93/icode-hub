<?php

namespace Tests\Feature;

use App\Exceptions\FinanceException;
use App\Livewire\Admin\Dashboard;
use App\Livewire\Admin\ExpensesManager;
use App\Livewire\Admin\ServersManager;
use App\Models\Client;
use App\Models\Expense;
use App\Models\ExpenseSchedule;
use App\Models\Server;
use App\Models\Subscription;
use App\Models\User;
use App\Services\ExpenseService;
use App\Support\Period;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ExpenseLedgerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
        $this->travelTo(Carbon::parse('2026-10-07 10:00'));
    }

    private function vps(array $attrs = []): Server
    {
        return Server::create(array_merge([
            'name' => 'Hetzner VPS', 'kind' => 'server', 'provider' => 'Hetzner', 'cost' => 20, 'currency' => 'USD',
            'billing_cycle' => 'monthly', 'renewal_date' => '2026-10-10',
        ], $attrs));
    }

    public function test_shared_vps_is_one_expense_however_many_projects_it_hosts(): void
    {
        $vps = $this->vps();
        $client = Client::create(['name' => 'Many sites']);
        foreach (range(1, 5) as $i) {
            Subscription::create([
                'client_id' => $client->id, 'server_id' => $vps->id, 'name' => "Hosting {$i}", 'type' => 'hosting',
                'domain_name' => "site{$i}.com", 'selling_price' => 100, 'cost_price' => 0, 'billing_cycle' => 'annual',
                'start_date' => '2026-01-01', 'expiry_date' => '2027-01-01',
            ]);
        }

        $dashboard = Livewire::test(Dashboard::class);
        $this->assertSame(['USD' => 500.0], $dashboard->viewData('annualRevenue'));
        $this->assertSame(['USD' => 240.0], $dashboard->viewData('annualCosts'));
        $this->assertSame([], $dashboard->viewData('spentThisMonth')); // a plan is not money paid
        $this->assertSame(0, Expense::count());                        // linking services created no expense

        Livewire::test(ServersManager::class)->call('renewServer', $vps->id, '2026-10-10');
        Livewire::test(ServersManager::class)->call('renewServer', $vps->id, '2026-10-10'); // double click

        $this->assertSame(1, Expense::count());
        $this->assertSame(['USD' => 20.0], Livewire::test(Dashboard::class)->viewData('spentThisMonth'));
        $this->assertSame(['USD' => 240.0], Livewire::test(Dashboard::class)->viewData('annualCosts'));
        $this->assertSame('2026-11-10', $vps->fresh()->renewal_date->toDateString());
        $this->assertSame('2026-11-10', $vps->schedule->fresh()->next_due_on->toDateString());
    }

    public function test_a_long_overdue_plan_records_only_the_period_the_user_pays(): void
    {
        $vps = $this->vps(['renewal_date' => '2026-07-10']); // three periods behind
        $service = app(ExpenseService::class);

        $expense = $service->paySchedule($vps->schedule);

        $this->assertSame(1, Expense::count());
        $this->assertSame('2026-07-10', $expense->period_start->toDateString());
        $this->assertSame('2026-08-10', $expense->period_end->toDateString());
        $this->assertSame('2026-08-10', $vps->schedule->fresh()->next_due_on->toDateString());
    }

    public function test_monthly_plan_forecasts_times_twelve_but_twelve_payments_are_just_summed(): void
    {
        $schedule = ExpenseSchedule::create([
            'title' => 'ChatGPT', 'category' => 'software_ai', 'currency' => 'USD', 'amount_per_cycle' => 20,
            'cycle_unit' => 'month', 'cycle_count' => 1, 'next_due_on' => '2026-01-15',
        ]);
        $service = app(ExpenseService::class);
        for ($i = 0; $i < 12; $i++) {
            $service->paySchedule($schedule->fresh(), ['expense_date' => '2026-' . str_pad($i + 1, 2, '0', STR_PAD_LEFT) . '-15']);
        }

        $page = Livewire::test(ExpensesManager::class);
        $this->assertSame(240.0, $schedule->fresh()->annual_forecast);
        $this->assertSame(['USD' => 240.0], $page->viewData('paidThisYear'));
        $this->assertSame('2027-01-15', $schedule->fresh()->next_due_on->toDateString());
    }

    public function test_biennial_price_is_the_full_two_year_price(): void
    {
        $domain = Server::create(['name' => 'icodegroup.net', 'kind' => 'domain', 'cost' => 30, 'currency' => 'USD', 'billing_cycle' => 'biennial', 'renewal_date' => '2027-03-01']);

        $schedule = $domain->schedule;
        $this->assertSame(['year', 2], [$schedule->cycle_unit, $schedule->cycle_count]);
        $this->assertSame(15.0, $schedule->annual_forecast);
        $this->assertSame('2029-03-01', $schedule->periodEndFrom($schedule->next_due_on)->toDateString());
    }

    public function test_void_keeps_the_row_and_lets_the_period_be_paid_again(): void
    {
        $vps = $this->vps();
        $service = app(ExpenseService::class);
        $wrong = $service->paySchedule($vps->schedule, ['amount' => 200]);

        Livewire::test(ExpensesManager::class)->call('askVoid', $wrong->id)->set('voidReason', 'بڕی هەڵە')->call('confirmVoid');
        $this->assertSame('void', $wrong->fresh()->status);

        $right = $service->paySchedule($vps->schedule->fresh(), ['period_start' => '2026-10-10', 'amount' => 20]);
        $this->assertNotSame($wrong->id, $right->id);
        $this->assertSame(['USD' => 20.0], Livewire::test(ExpensesManager::class)->viewData('paidThisMonth'));
        $this->assertSame(2, Expense::count());
    }

    public function test_recorded_amount_cannot_be_edited_in_place(): void
    {
        $expense = Expense::create(['title' => 'Fuel', 'category' => 'transport', 'amount' => 10, 'currency' => 'USD', 'expense_date' => '2026-10-01', 'payment_method' => 'cash']);

        Livewire::test(ExpensesManager::class)->call('edit', $expense->id)->set('amount', 99)->set('vendor', 'Station')->call('save')->assertHasNoErrors();

        $this->assertSame('10.00', (string) $expense->fresh()->amount);
        $this->assertSame('Station', $expense->fresh()->vendor);
    }

    public function test_legacy_monthly_expense_is_resolved_by_the_user_not_guessed(): void
    {
        $legacy = Expense::create(['title' => 'Internet', 'category' => 'telecom', 'amount' => 30000, 'currency' => 'IQD', 'billing_cycle' => 'monthly', 'expense_date' => '2026-09-01', 'payment_method' => 'cash', 'needs_review' => true]);

        Livewire::test(ExpensesManager::class)->call('resolveLegacy', $legacy->id, true);

        $schedule = ExpenseSchedule::where('legacy_source', 'expenses')->sole();
        $this->assertSame('2026-10-01', $schedule->next_due_on->toDateString());
        $this->assertFalse($legacy->fresh()->needs_review);
        $this->assertSame($schedule->id, $legacy->fresh()->expense_schedule_id);
        $this->assertSame(1, Expense::count()); // still one real payment
    }

    public function test_server_with_paid_history_is_retired_not_deleted(): void
    {
        $vps = $this->vps();
        app(ExpenseService::class)->paySchedule($vps->schedule);

        Livewire::test(ServersManager::class)->call('delete', $vps->id);

        $this->assertSame('terminated', $vps->fresh()->status);
        $this->assertSame('ended', $vps->schedule->fresh()->status);
        $this->expectException(FinanceException::class);
        app(ExpenseService::class)->paySchedule($vps->schedule->fresh());
    }

    public function test_period_math_never_overflows(): void
    {
        $this->assertSame('2026-02-28', Period::add('2026-01-31', 'month', 1)->toDateString());
        $this->assertSame('2028-02-29', Period::add('2028-01-31', 'month', 1)->toDateString());
        $this->assertSame('2029-02-28', Period::add('2028-02-29', 'year', 1)->toDateString());
        $this->assertSame('2028-10-07', Period::add('2026-10-07', 'year', 2)->toDateString());
        $this->expectException(\InvalidArgumentException::class);
        Period::add('2026-10-07', 'year', 0);
    }
}
