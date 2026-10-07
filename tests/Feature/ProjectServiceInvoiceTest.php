<?php

namespace Tests\Feature;

use App\Livewire\Admin\ContractsManager;
use App\Livewire\Admin\InvoicesManager;
use App\Livewire\Admin\ProjectsManager;
use App\Livewire\Admin\SubscriptionsManager;
use App\Livewire\Public\ClientPortal;
use App\Livewire\Public\PortfolioHome;
use App\Models\Client;
use App\Models\Contract;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\Server;
use App\Models\ServicePeriod;
use App\Models\Subscription;
use App\Models\User;
use App\Services\InvoiceService;
use App\Services\PaymentService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class ProjectServiceInvoiceTest extends TestCase
{
    use RefreshDatabase;

    private Client $client;
    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
        $this->travelTo(Carbon::parse('2026-10-07 09:00'));
        $this->client = Client::create(['name' => 'Ahmad', 'business_name' => 'Ahmad Clinic', 'phone' => '07501230000']);
        $this->project = Project::create(['client_id' => $this->client->id, 'title' => 'Clinic website', 'slug' => 'clinic-website', 'status' => 'in_progress']);
    }

    private function header(array $attrs = []): array
    {
        return array_merge([
            'client_id' => $this->client->id, 'project_id' => $this->project->id, 'contract_id' => null,
            'invoice_number' => 'ICODE-INV-2026-500', 'issue_date' => '2026-10-07', 'due_date' => '2026-10-21',
            'discount' => 0, 'tax' => 0, 'currency' => 'USD', 'status' => 'sent',
        ], $attrs);
    }

    private function hosting(array $attrs = []): array
    {
        return array_merge([
            'description' => 'Hosting', 'service_type' => 'hosting', 'billing_cycle' => 'annual',
            'quantity' => 2, 'unit_price' => 100, 'start_date' => '2026-10-07',
        ], $attrs);
    }

    public function test_project_page_invoice_creates_the_hosting_service_with_its_first_period(): void
    {
        $page = Livewire::withQueryParams(['new' => 1, 'project' => $this->project->id])->test(InvoicesManager::class)
            ->assertSet('showModal', true)
            ->assertSet('client_id', $this->client->id)
            ->assertSet('project_id', $this->project->id)
            ->set('due_date', '2026-10-21')
            ->set('items.0.description', 'Website development')
            ->set('items.0.unit_price', 500)
            ->call('addItem')
            ->set('items.1.description', 'Hosting clinic.com')
            ->set('items.1.service_type', 'hosting')
            ->set('items.1.billing_cycle', 'annual')
            ->set('items.1.quantity', 2)
            ->set('items.1.unit_price', 100)
            ->set('items.1.start_date', '2026-10-07')
            ->set('items.1.create_service', true);
        $page->call('save')->assertHasNoErrors();

        $invoice = Invoice::with('items')->sole();
        $this->assertSame('700.00', (string) $invoice->total);
        $this->assertSame('2026-10-21', $invoice->due_date->toDateString());

        $service = Subscription::sole();
        $this->assertSame($this->project->id, $service->project_id);
        $this->assertSame('100.00', (string) $service->selling_price); // renewal price per year
        $this->assertSame('annual', $service->billing_cycle);
        $this->assertSame('2028-10-07', $service->expiry_date->toDateString());

        $period = ServicePeriod::sole();
        $this->assertSame(['2026-10-07', '2028-10-07', 'year', 2, '200.00', 'invoice'],
            [$period->starts_on->toDateString(), $period->expires_on->toDateString(), $period->billing_unit, $period->billing_count, (string) $period->price, $period->origin]);

        $line = $invoice->items->firstWhere('service_type', 'hosting');
        $this->assertSame([$service->id, $period->id], [$line->subscription_id, $line->service_period_id]);
        $this->assertSame('2 ساڵ', $line->quantity_label);

        // Two payments: 300 + 400. Paying never moves the expiry.
        app(PaymentService::class)->record($invoice, 300);
        app(PaymentService::class)->record($invoice, 400);
        $this->assertSame('paid', $invoice->fresh()->status);
        $this->assertSame('2028-10-07', $service->fresh()->expiry_date->toDateString());
    }

    public function test_backend_computes_the_expiry_and_rejects_bad_durations(): void
    {
        $service = app(InvoiceService::class);

        // A tampered expiry is replaced by start + duration.
        $invoice = $service->save(null, $this->header(), [$this->hosting(['expiry_date' => '2030-01-01'])]);
        $this->assertSame('2028-10-07', $invoice->items->first()->expiry_date->toDateString());

        foreach ([['quantity' => 1.5], ['quantity' => 0], ['quantity' => -1], ['start_date' => null]] as $i => $bad) {
            try {
                $service->save(null, $this->header(['invoice_number' => "BAD-{$i}"]), [$this->hosting($bad)]);
                $this->fail('Invalid duration accepted: ' . json_encode($bad));
            } catch (ValidationException) {
                $this->addToAssertionCount(1);
            }
        }

        // Custom period needs a reason and a real end.
        $this->expectValidationError(fn () => $service->save(null, $this->header(['invoice_number' => 'C-1']), [$this->hosting(['custom_period' => true, 'expiry_date' => '2028-12-31'])]), 'items.0.period_note');
        $custom = $service->save(null, $this->header(['invoice_number' => 'C-2']), [$this->hosting(['custom_period' => true, 'expiry_date' => '2028-12-31', 'period_note' => 'تا کۆتایی ساڵ'])]);
        $this->assertSame('2028-12-31', $custom->items->first()->expiry_date->toDateString());

        // One-time lines may use fractional quantities and carry no dates.
        $oneOff = $service->save(null, $this->header(['invoice_number' => 'O-1']), [['description' => 'Design hours', 'service_type' => 'development', 'billing_cycle' => 'one_time', 'quantity' => 1.5, 'unit_price' => 20, 'start_date' => '2026-10-07']]);
        $this->assertNull($oneOff->items->first()->start_date);
        $this->assertSame('30.00', (string) $oneOff->total);

        $this->expectValidationError(fn () => $service->save(null, $this->header(['invoice_number' => 'D-1', 'due_date' => '2026-10-01']), [$this->hosting()]), 'due_date');
    }

    public function test_month_end_and_leap_year_periods_do_not_overflow(): void
    {
        $service = app(InvoiceService::class);
        $items = $service->normalizeItems([
            $this->hosting(['billing_cycle' => 'monthly', 'quantity' => 1, 'start_date' => '2026-01-31']),
            $this->hosting(['billing_cycle' => 'annual', 'quantity' => 1, 'start_date' => '2028-02-29']),
            $this->hosting(['billing_cycle' => 'monthly', 'quantity' => 1, 'start_date' => '2028-01-31']),
        ]);

        $this->assertSame(['2026-02-28', '2029-02-28', '2028-02-29'], array_column($items, 'expiry_date'));
    }

    public function test_project_contract_and_services_of_another_client_are_refused(): void
    {
        $other = Client::create(['name' => 'Other', 'phone' => '07709990000']);
        $otherProject = Project::create(['client_id' => $other->id, 'title' => 'Other site', 'slug' => 'other-site']);
        $otherContract = Contract::create(['contract_number' => 'CNT-O', 'client_id' => $other->id, 'title' => 'x', 'start_date' => '2026-01-01']);
        $otherService = Subscription::create(['client_id' => $other->id, 'name' => 'Other host', 'type' => 'hosting', 'selling_price' => 50, 'start_date' => '2026-01-01', 'expiry_date' => '2027-01-01']);
        $service = app(InvoiceService::class);

        $this->expectValidationError(fn () => $service->save(null, $this->header(['project_id' => $otherProject->id]), [$this->hosting()]), 'project_id');
        $this->expectValidationError(fn () => $service->save(null, $this->header(['contract_id' => $otherContract->id]), [$this->hosting()]), 'contract_id');
        $this->expectValidationError(fn () => $service->save(null, $this->header(), [$this->hosting(['subscription_id' => $otherService->id])]), 'items.0.subscription_id');
        $this->assertSame(0, Invoice::count());

        Livewire::test(SubscriptionsManager::class)->call('edit', $otherService->id)->set('project_id', $this->project->id)->call('save')->assertHasErrors('project_id');
        Livewire::test(ContractsManager::class)->call('openModal')
            ->set('client_id', $this->client->id)->set('project_id', $otherProject->id)->set('title', 'c')->set('contract_number', 'CNT-N')->set('start_date', '2026-10-07')
            ->call('save')->assertHasErrors('project_id');
    }

    public function test_issued_invoice_keeps_the_client_details_it_was_issued_with(): void
    {
        $invoice = app(InvoiceService::class)->save(null, $this->header(), [$this->hosting()]);
        $this->client->update(['business_name' => 'Renamed Co']);

        $this->assertSame('Ahmad Clinic', $invoice->fresh()->bill_to['business_name']);

        // Admin print and client portal render the same document.
        Livewire::test(InvoicesManager::class)->call('viewInvoice', $invoice->id)
            ->assertSee('Ahmad Clinic')->assertSee('2026-10-07 → 2028-10-07')->assertSee('$200');
        session([ClientPortal::SESSION_KEY => $this->client->id]);
        Livewire::test(ClientPortal::class)->call('viewInvoice', $invoice->id)
            ->assertSee('Ahmad Clinic')->assertSee('2026-10-07 → 2028-10-07')->assertSee('$200');
    }

    public function test_paid_invoice_price_is_locked(): void
    {
        $invoice = app(InvoiceService::class)->save(null, $this->header(), [$this->hosting()]);
        app(PaymentService::class)->record($invoice, 200);

        $this->expectValidationError(fn () => app(InvoiceService::class)->save($invoice->fresh(), $this->header(), [$this->hosting(['unit_price' => 120])]), 'discount');
        // Same price, new description: allowed.
        app(InvoiceService::class)->save($invoice->fresh(), $this->header(), [$this->hosting(['description' => 'Hosting (2 years)'])]);
        $this->assertSame('paid', $invoice->fresh()->status);
    }

    public function test_renewal_keeps_the_old_period_and_the_old_invoice_line(): void
    {
        $invoice = app(InvoiceService::class)->save(null, $this->header(), [$this->hosting(['create_service' => true, 'quantity' => 1])]);
        $service = Subscription::sole();
        $first = ServicePeriod::sole();
        $this->travelTo(Carbon::parse('2027-10-01'));

        $service->renew();

        $periods = ServicePeriod::orderBy('starts_on')->get();
        $this->assertCount(2, $periods);
        $this->assertSame('superseded', $periods[0]->status);
        $this->assertSame(['2027-10-07', '2028-10-07', 'renewal'], [$periods[1]->starts_on->toDateString(), $periods[1]->expires_on->toDateString(), $periods[1]->origin]);
        $this->assertSame($first->id, $periods[1]->renewed_from_id);
        $this->assertSame('2027-10-07', $service->fresh()->start_date->toDateString());

        $line = $invoice->items()->first();
        $this->assertSame($first->id, $line->service_period_id);
        $this->assertSame('2027-10-07', $line->expiry_date->toDateString());

        // Editing the service afterwards does not drag the new period back to the old start.
        Livewire::test(SubscriptionsManager::class)->call('edit', $service->id)->set('notes', 'note')->call('save')->assertHasNoErrors();
        $this->assertSame('2027-10-07', ServicePeriod::where('status', 'active')->sole()->starts_on->toDateString());
    }

    public function test_correcting_service_dates_corrects_the_current_period_only(): void
    {
        $service = Subscription::create(['client_id' => $this->client->id, 'name' => 'Domain', 'type' => 'domain', 'selling_price' => 20, 'start_date' => '2026-03-01', 'expiry_date' => '2027-03-01']);
        $service->update(['expiry_date' => '2027-03-05']); // registry says the 5th

        $this->assertSame(1, ServicePeriod::count());
        $this->assertSame('2027-03-05', ServicePeriod::sole()->expires_on->toDateString());
        $this->assertSame('initial', ServicePeriod::sole()->origin);
    }

    public function test_shared_hosting_has_no_purchase_price_and_needs_its_vps(): void
    {
        $vps = Server::create(['name' => 'VPS', 'cost' => 20, 'renewal_date' => '2026-11-01']);

        $form = Livewire::test(SubscriptionsManager::class)->call('openModal')
            ->set('client_id', $this->client->id)->set('type', 'hosting')->set('domain_name', 'clinic.com')
            ->set('cost_basis', 'shared_infrastructure')->set('server_id', null)->set('cost_price', 30)
            ->set('start_date', '2026-10-07')
            ->call('save')->assertHasErrors('server_id');

        $form->set('server_id', $vps->id)->set('project_id', $this->project->id)->call('save')->assertHasNoErrors();
        $service = Subscription::where('domain_name', 'clinic.com')->sole();
        $this->assertSame('0.00', (string) $service->cost_price);
        $this->assertSame('shared_infrastructure', $service->cost_basis);
        $this->assertSame($this->project->id, $service->project_id);
        $this->assertSame('2027-10-07', $service->expiry_date->toDateString());
        $this->assertSame(0, \App\Models\Expense::count()); // hosting on the VPS creates no expense
    }

    public function test_projects_with_money_history_are_archived_and_private_work_never_reaches_the_portfolio(): void
    {
        app(InvoiceService::class)->save(null, $this->header(), [$this->hosting()]);
        $public = Project::create(['title' => 'Showcase', 'slug' => 'showcase', 'is_public' => true]);

        Livewire::test(ProjectsManager::class)->call('delete', $this->project->id);
        $this->assertNotNull($this->project->fresh()->archived_at);

        Livewire::test(PortfolioHome::class)->assertSee('Showcase')->assertDontSee('Clinic website');
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        Livewire::test(PortfolioHome::class)->call('viewProject', $this->project->id);
        $this->assertNotNull($public);
    }

    public function test_project_page_shows_money_per_currency_and_no_profit_claim(): void
    {
        $invoice = app(InvoiceService::class)->save(null, $this->header(), [
            ['description' => 'Website', 'service_type' => 'development', 'billing_cycle' => 'one_time', 'quantity' => 1, 'unit_price' => 500],
            $this->hosting(['create_service' => true]),
        ]);
        app(PaymentService::class)->record($invoice, 300);

        $this->get(route('admin.projects.show', $this->project))->assertOk()
            ->assertSee('Clinic website')->assertSee('$500')->assertSee('$700')->assertSee('$300')->assertSee('$400')
            ->assertSee('2026-10-07 → ', false)->assertSee('قازانجی ڕاستەقینەی پڕۆژە» نییە');
    }

    private function expectValidationError(callable $fn, string $key): void
    {
        try {
            $fn();
        } catch (ValidationException $e) {
            $this->assertArrayHasKey($key, $e->errors());

            return;
        }
        $this->fail("Expected a validation error on {$key}");
    }
}
