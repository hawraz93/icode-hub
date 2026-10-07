<?php

namespace Tests\Feature;

use App\Livewire\Public\ClientPortal;
use App\Models\Client;
use App\Models\Expense;
use App\Models\ExpenseSchedule;
use App\Models\Invoice;
use App\Models\Server;
use App\Models\Subscription;
use App\Models\User;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Every page touched by the finance changes renders with realistic data (Blade/runtime errors).
 */
class AdminPagesRenderTest extends TestCase
{
    use RefreshDatabase;

    public function test_finance_pages_render_with_data(): void
    {
        $this->actingAs(User::factory()->create());
        $client = Client::create(['name' => 'Render client', 'phone' => '07501111111', 'portal_access_code' => 'CL-RENDER1']);
        $server = Server::create(['name' => 'Render VPS', 'cost' => 20, 'billing_cycle' => 'monthly', 'renewal_date' => now()->addDays(5)->toDateString()]);
        Subscription::create(['client_id' => $client->id, 'server_id' => $server->id, 'name' => 'Hosting', 'type' => 'hosting', 'domain_name' => 'render.com', 'selling_price' => 100, 'start_date' => '2026-01-01', 'expiry_date' => now()->addDays(20)->toDateString()]);
        $invoice = Invoice::create(['invoice_number' => 'ICODE-INV-2026-900', 'client_id' => $client->id, 'issue_date' => now(), 'due_date' => now()->addDays(10), 'subtotal' => 100000, 'total' => 100000, 'currency' => 'IQD', 'status' => 'sent']);
        $invoice->items()->create(['description' => 'Domain', 'quantity' => 1, 'unit_price' => 100000, 'total_price' => 100000, 'service_type' => 'domain']);
        app(PaymentService::class)->record($invoice, 40000);
        ExpenseSchedule::create(['title' => 'ChatGPT', 'currency' => 'USD', 'amount_per_cycle' => 20, 'cycle_unit' => 'month', 'cycle_count' => 1, 'next_due_on' => now()->subDays(2)]);
        Expense::create(['title' => 'Legacy internet', 'category' => 'telecom', 'amount' => 30000, 'currency' => 'IQD', 'billing_cycle' => 'monthly', 'expense_date' => now(), 'payment_method' => 'cash', 'needs_review' => true]);

        $this->get(route('admin.dashboard'))->assertOk()->assertSee('جیاوازی پێشبینی')->assertSee('پارەی وەرگیراو لەم مانگەدا');
        $this->get(route('admin.expenses'))->assertOk()->assertSee('Legacy internet')->assertSee('پێویستی بە پشکنین');
        $this->get(route('admin.expenses', ['tab' => 'recurring']))->assertOk()->assertSee('ChatGPT')->assertSee('Render VPS');
        $this->get(route('admin.invoices'))->assertOk()->assertSee('ICODE-INV-2026-900')->assertSee('+ پارەدان');
        $this->get(route('admin.clients'))->assertOk()->assertSee('کۆدی کۆن');
        $this->get(route('admin.servers'))->assertOk()->assertSee('پارەدرا');
        $this->get(route('admin.renewals'))->assertOk();
        $project = \App\Models\Project::create(['client_id' => $client->id, 'title' => 'Render project', 'slug' => 'render-project']);
        $this->get(route('admin.projects'))->assertOk()->assertSee('Render project')->assertSee('ناوخۆیی');
        $this->get(route('admin.projects.show', $project))->assertOk()->assertSee('+ دروستکردنی وەسڵ');
        $this->get(route('admin.subscriptions'))->assertOk()->assertSee('render.com');
        $this->get(route('admin.invoices', ['new' => 1, 'project' => $project->id]))->assertOk()->assertSee('Render project');
        $this->get(route('admin.contracts'))->assertOk();

        auth()->logout();
        $this->get(route('client.portal'))->assertOk()->assertDontSee('ژمارەی مۆبایل');
        $this->withSession([ClientPortal::SESSION_KEY => $client->id])->get(route('client.portal'))
            ->assertOk()->assertSee('ICODE-INV-2026-900')->assertSee('60,000 د.ع')->assertDontSee('CL-RENDER1');
    }
}
