<?php

namespace Tests\Feature;

use App\Livewire\Admin\ClientsManager;
use App\Livewire\Admin\Dashboard;
use App\Livewire\Admin\RenewalRadar;
use App\Livewire\Admin\SubscriptionsManager;
use App\Models\Client;
use App\Models\Server;
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

    public function test_unpaid_flag_puts_a_service_on_the_unpaid_list_until_marked_paid(): void
    {
        $sub = $this->sub($this->client(), ['is_paid' => false]);

        // Shows on the radar even though the hosting itself runs until 2028; no due date involved.
        $radar = Livewire::test(RenewalRadar::class)->assertSee('پارەیان نەداوە')->assertSee('Fly Com')->assertSee('$100');

        $radar->call('togglePaid', $sub->id);
        $this->assertTrue($sub->fresh()->is_paid);
        $this->assertNotNull($sub->fresh()->paid_at);
        Livewire::test(RenewalRadar::class)->assertDontSee('پارەیان نەداوە');
    }

    public function test_form_has_a_simple_paid_switch_and_a_dinar_option(): void
    {
        $client = $this->client(['business_name' => 'VIP']);

        Livewire::test(SubscriptionsManager::class)
            ->call('openModal')
            ->assertSet('is_paid', true)
            ->set('client_id', $client->id)
            ->set('type', 'domain')
            ->set('domain_name', 'ghsooncompany.com.iq')
            ->set('currency', 'IQD')
            ->set('selling_price', 100000)
            ->set('is_paid', false)
            ->call('save')
            ->assertHasNoErrors();

        $sub = Subscription::where('domain_name', 'ghsooncompany.com.iq')->firstOrFail();
        $this->assertSame('IQD', $sub->currency);
        $this->assertFalse($sub->is_paid);
        $this->assertSame(0, $sub->invoices()->count()); // no invoice, no date: just the flag

        Livewire::test(SubscriptionsManager::class)
            ->assertSee('100,000 د.ع')
            ->set('statusFilter', 'unpaid')
            ->assertSee('ghsooncompany.com.iq');

        // Editing and switching to "paid" clears it.
        Livewire::test(SubscriptionsManager::class)->call('edit', $sub->id)->set('is_paid', true)->call('save');
        $this->assertTrue($sub->fresh()->is_paid);
    }

    public function test_renewing_without_recording_payment_marks_the_new_period_unpaid(): void
    {
        $c = $this->client();
        $unpaidRenewal = $this->sub($c, ['expiry_date' => now()->addDays(3)->toDateString()]);
        $paidRenewal = $this->sub($c, ['domain_name' => 'paid.com', 'expiry_date' => now()->addDays(3)->toDateString(), 'renewal_stage' => Subscription::STAGE_PAID]);

        $unpaidRenewal->renew();
        $paidRenewal->renew();

        $this->assertFalse($unpaidRenewal->fresh()->is_paid);
        $this->assertTrue($paidRenewal->fresh()->is_paid);
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

    public function test_telegram_paid_button_clears_an_unpaid_client(): void
    {
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['message_id' => 5]])]);
        $sub = $this->sub($this->client(), ['is_paid' => false]);

        $this->travelTo(now()->next('Monday'));
        $this->artisan('renewals:scan')->assertSuccessful();
        Http::assertSent(fn ($r) => str_contains(json_encode($r['reply_markup'] ?? []), "u:{$sub->id}"));

        $this->postJson('/telegram/webhook', ['callback_query' => [
            'id' => 'cb', 'data' => "u:{$sub->id}", 'message' => ['message_id' => 5, 'chat' => ['id' => 42]],
        ]], ['X-Telegram-Bot-Api-Secret-Token' => RenewalBot::webhookSecret()])->assertOk();

        $this->assertTrue($sub->fresh()->is_paid);
    }

    public function test_dashboard_keeps_dollars_and_dinars_apart_and_counts_own_services_as_expenses(): void
    {
        $c = $this->client();
        $this->sub($c, ['selling_price' => 100, 'billing_cycle' => 'annual']);
        $this->sub($c, ['domain_name' => 'x.com.iq', 'currency' => 'IQD', 'selling_price' => 100000, 'billing_cycle' => 'annual', 'is_paid' => false]);
        Server::create(['name' => 'Contabo VPS', 'kind' => 'server', 'cost' => 8, 'currency' => 'USD', 'billing_cycle' => 'monthly', 'renewal_date' => now()->addDays(20)]);
        Server::create(['name' => 'icodegroup.net', 'kind' => 'domain', 'cost' => 15, 'currency' => 'USD', 'billing_cycle' => 'annual', 'renewal_date' => now()->addMonths(5)]);

        $page = Livewire::test(Dashboard::class);

        $this->assertSame(['USD' => 100.0, 'IQD' => 100000.0], $page->viewData('annualRevenue'));
        $this->assertSame(['USD' => 111.0], $page->viewData('annualCosts'));            // 8 x 12 + 15
        $this->assertSame(['USD' => -11.0, 'IQD' => 100000.0], $page->viewData('annualProfit'));
        $this->assertSame(['IQD' => 100000.0], $page->viewData('unpaidTotals'));
        $page->assertSee('100,000 د.ع')->assertDontSee('.00');
    }

    public function test_deep_link_opens_the_sheet_for_a_service_outside_the_radar_window(): void
    {
        $sub = $this->sub($this->client());

        Livewire::withQueryParams(['open' => $sub->id])->test(RenewalRadar::class)
            ->assertSet('selectedId', $sub->id)
            ->assertSee('✓ پارەی داوە');
    }
}
