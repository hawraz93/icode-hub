<?php

namespace Tests\Feature;

use App\Livewire\Admin\ClientsManager;
use App\Livewire\Admin\Dashboard;
use App\Livewire\Admin\RenewalRadar;
use App\Livewire\Admin\SubscriptionsManager;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Subscription;
use App\Models\User;
use App\Services\RenewalBot;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class PaymentsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
        config(['services.telegram.bot_token' => 'TEST', 'services.telegram.chat_id' => '42', 'app.url' => 'https://icodegroup.net']);
    }

    private function client(array $attrs = []): Client
    {
        return Client::create(array_merge(['name' => 'Karzan', 'business_name' => 'Fly Com', 'phone' => '07700663625'], $attrs));
    }

    private function sub(Client $c, array $attrs = []): Subscription
    {
        return Subscription::create(array_merge([
            'client_id' => $c->id, 'name' => 'Hosting', 'type' => 'hosting', 'domain_name' => 'finance.icodegroup.net',
            'selling_price' => 100, 'cost_price' => 0, 'billing_cycle' => 'biennial',
            'start_date' => '2026-08-10', 'expiry_date' => '2028-08-10',
        ], $attrs));
    }

    public function test_domains_typed_as_urls_are_cleaned(): void
    {
        $this->assertSame('finance.icodegroup.net', Subscription::normalizeDomain('https://Finance.iCodeGroup.net/'));
        $this->assertSame('hemophilia.icodegroup.net', Subscription::normalizeDomain('https://hemophilia.icodegroup.net/path?x=1'));
        $this->assertSame('DASS', Subscription::normalizeDomain('DASS'));
        $this->assertSame('finance.icodegroup.net', $this->sub($this->client(), ['domain_name' => 'https://finance.icodegroup.net/'])->domain_name);
    }

    public function test_pays_at_start_of_next_month_is_recorded_and_can_be_marked_paid(): void
    {
        $this->travelTo(Carbon::parse('2026-09-30'));
        $sub = $this->sub($this->client());

        $radar = Livewire::test(RenewalRadar::class)->call('recordDebt', $sub->id, 100);

        $invoice = Invoice::firstOrFail();
        $this->assertSame($sub->id, $invoice->subscription_id);
        $this->assertSame('2026-10-01', $invoice->due_date->toDateString());
        $this->assertSame(100.0, $sub->fresh()->unpaid_balance_usd);

        // Shows in "money you're waiting for", even though the hosting runs until 2028.
        $radar->assertSee('پارەی چاوەڕوانکراو')->assertSee('Fly Com');

        $radar->call('markInvoicePaid', $invoice->id);
        $this->assertSame('paid', $invoice->fresh()->status);
        $this->assertSame(0.0, $sub->fresh()->unpaid_balance_usd);
    }

    public function test_deep_link_opens_the_sheet_for_a_service_outside_the_radar_window(): void
    {
        $sub = $this->sub($this->client());

        Livewire::withQueryParams(['open' => $sub->id])->test(RenewalRadar::class)
            ->assertSet('selectedId', $sub->id)
            ->assertSee('+ تۆمارکردنی قەرز');
    }

    public function test_monthly_installments_bill_once_per_month_on_the_payment_day(): void
    {
        $sub = $this->sub($this->client(), ['payment_plan' => 'monthly', 'installment_amount' => 10, 'payment_day' => 5]);

        $this->assertNull($sub->billMonthlyInstallment(Carbon::parse('2026-10-04')));   // before the day
        $first = $sub->billMonthlyInstallment(Carbon::parse('2026-10-05'));
        $this->assertSame('2026-10-05', $first->due_date->toDateString());
        $this->assertNull($sub->billMonthlyInstallment(Carbon::parse('2026-10-20')));   // already billed
        $this->assertNotNull($sub->billMonthlyInstallment(Carbon::parse('2026-11-05')));
        $this->assertNull($sub->billMonthlyInstallment(Carbon::parse('2028-09-05')));   // after expiry

        $this->travelTo(Carbon::parse('2026-12-06'));
        $this->artisan('renewals:bill-installments')->assertSuccessful();
        $this->assertSame(3, $sub->invoices()->count());
    }

    public function test_same_phone_number_is_never_saved_as_a_second_client(): void
    {
        $existing = $this->client(['name' => 'د هەندرێن', 'business_name' => 'epochsp', 'phone' => '07725216760']);

        Livewire::test(ClientsManager::class)
            ->call('openModal')
            ->set('name', 'د هەندرێن')->set('phone', '0772 521 6760')->set('status', 'active')
            ->call('save')
            ->assertHasErrors('phone');

        Livewire::test(SubscriptionsManager::class)
            ->set('quick_client_name', 'د هەندرێن')->set('quick_client_phone', '+964 772 521 6760')
            ->call('saveQuickClient')
            ->assertSet('client_id', $existing->id);

        $this->assertSame(1, Client::count());
    }

    public function test_telegram_sends_due_payment_and_paid_button_settles_it(): void
    {
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['message_id' => 5]])]);
        $sub = $this->sub($this->client());
        $invoice = $sub->bill(100, Carbon::today(), 'پارەی هۆستینگ');

        $this->artisan('renewals:scan')->assertSuccessful();
        Http::assertSent(fn ($r) => str_contains((string) ($r['text'] ?? ''), '💳') && str_contains(json_encode($r['reply_markup'] ?? []), "v:{$invoice->id}"));

        $this->postJson('/telegram/webhook', ['callback_query' => [
            'id' => 'cb', 'data' => "v:{$invoice->id}", 'message' => ['message_id' => 5, 'chat' => ['id' => 42]],
        ]], ['X-Telegram-Bot-Api-Secret-Token' => RenewalBot::webhookSecret()])->assertOk();

        $this->assertSame('paid', $invoice->fresh()->status);
    }

    public function test_dashboard_shows_real_cash_received_due_and_overdue(): void
    {
        $this->travelTo(Carbon::parse('2026-10-10'));
        $sub = $this->sub($this->client());
        $sub->bill(100, Carbon::parse('2026-10-01'), 'a')->markPaid();   // received this month
        $sub->bill(40, Carbon::parse('2026-10-20'), 'b');                // due later this month
        $sub->bill(25, Carbon::parse('2026-09-15'), 'c');                // overdue

        $cash = Livewire::test(Dashboard::class)->viewData('cash');

        $this->assertEqualsWithDelta(100, $cash['received'], 0.01);
        $this->assertEqualsWithDelta(40, $cash['due'], 0.01);
        $this->assertEqualsWithDelta(25, $cash['overdue'], 0.01);
    }
}
