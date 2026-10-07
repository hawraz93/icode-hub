<?php

namespace Tests\Feature;

use App\Livewire\Admin\QuickAdd;
use App\Models\ActivityReminder;
use App\Models\Client;
use App\Models\Subscription;
use App\Models\User;
use App\Services\RenewalBot;
use App\Support\RenewalLineParser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class ZeroClickTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'app.url' => 'https://icodegroup.net',
            'services.telegram.bot_token' => 'TEST',
            'services.telegram.chat_id' => '42',
        ]);
    }

    private function client(array $attrs = []): Client
    {
        return Client::create(array_merge(['name' => 'Ghsoon', 'business_name' => 'Ghsoon Co', 'phone' => '07501234567'], $attrs));
    }

    private function sub(Client $client, int $days, array $attrs = []): Subscription
    {
        return Subscription::create(array_merge([
            'client_id' => $client->id, 'name' => 'Domain', 'type' => 'domain', 'domain_name' => 'ghsoon.com',
            'selling_price' => 100, 'cost_price' => 12, 'billing_cycle' => 'annual',
            'start_date' => now()->subYear()->toDateString(), 'expiry_date' => now()->addDays($days)->toDateString(),
        ], $attrs));
    }

    private function fakeTelegramAndRdap(array $rdap = []): void
    {
        Http::fake(array_merge($rdap, [
            'api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['message_id' => 99]]),
            'data.iana.org/*' => Http::response(['services' => [[['com', 'net'], ['https://rdap.example.test/']]]]),
            'rdap.example.test/*' => Http::response([], 404),
        ]));
    }

    private function webhook(array $update)
    {
        return $this->postJson('/telegram/webhook', $update, ['X-Telegram-Bot-Api-Secret-Token' => RenewalBot::webhookSecret()]);
    }

    private function tap(string $data, int $chatId = 42)
    {
        return $this->webhook(['callback_query' => [
            'id' => 'cb1', 'data' => $data,
            'message' => ['message_id' => 7, 'chat' => ['id' => $chatId]],
        ]]);
    }

    // ------------------------------------------------------------ parser

    public function test_parser_reads_the_way_renewals_are_actually_written(): void
    {
        $a = RenewalLineParser::parse('epochsp.com  $100  1-4-2027');
        $this->assertSame('epochsp.com', $a['domain']);
        $this->assertSame(100.0, $a['amount']);
        $this->assertSame('USD', $a['currency']);
        $this->assertSame(['2027-04-01', '2027-01-04'], array_map(fn ($d) => $d->toDateString(), $a['dates']));

        $b = RenewalLineParser::parse('ghsooncompany.com.iq  100k   1/9/2026');
        $this->assertSame('ghsooncompany.com.iq', $b['domain']);
        $this->assertSame(100000.0, $b['amount']);
        $this->assertSame('IQD', $b['currency']);

        $c = RenewalLineParser::parse('norduz.net      $100           6/28/2026');
        $this->assertSame(['2026-06-28'], array_map(fn ($d) => $d->toDateString(), $c['dates'])); // only valid reading

        $d = RenewalLineParser::parse('univsul            $100           15/2/2027');
        $this->assertNull($d['domain']);
        $this->assertSame('univsul', $d['name']);
        $this->assertSame(['2027-02-15'], array_map(fn ($x) => $x->toDateString(), $d['dates']));

        $e = RenewalLineParser::parse('shop.krd 150,000 hosting Rawa Market');
        $this->assertSame('hosting', $e['type']);
        $this->assertSame('IQD', $e['currency']);
        $this->assertSame('Rawa Market', $e['client_hint']);
    }

    // ------------------------------------------------------- bot buttons

    public function test_webhook_rejects_requests_without_the_secret(): void
    {
        $this->postJson('/telegram/webhook', ['message' => ['text' => 'hi', 'chat' => ['id' => 42]]])->assertForbidden();
    }

    public function test_buttons_from_another_chat_are_ignored(): void
    {
        $this->fakeTelegramAndRdap();
        $sub = $this->sub($this->client(), 3);

        $this->tap("R:{$sub->id}", chatId: 666)->assertOk();

        $this->assertSame(now()->addDays(3)->toDateString(), $sub->fresh()->expiry_date->toDateString());
    }

    public function test_paid_renew_and_cancel_buttons_update_the_record_and_the_message(): void
    {
        $this->fakeTelegramAndRdap();
        $c = $this->client();
        $sub = $this->sub($c, 3);

        $this->tap("p:{$sub->id}")->assertOk();
        $this->assertSame(Subscription::STAGE_PAID, $sub->fresh()->renewal_stage);
        Http::assertSent(fn ($r) => str_ends_with($r->url(), '/editMessageText') && $r['message_id'] === 7);

        $this->tap("r:{$sub->id}"); // asks first, changes nothing
        $this->assertSame(now()->addDays(3)->toDateString(), $sub->fresh()->expiry_date->toDateString());

        $this->tap("R:{$sub->id}");
        $this->assertSame(now()->addDays(3)->addYear()->toDateString(), $sub->fresh()->expiry_date->toDateString());

        $other = $this->sub($c, 5, ['domain_name' => 'bye.com']);
        $this->tap("X:{$other->id}");
        $this->assertSame('cancelled', $other->fresh()->status);
    }

    public function test_whatsapp_button_marks_notified_and_opens_whatsapp(): void
    {
        $sub = $this->sub($this->client(), 3);

        $this->get(RenewalBot::whatsappLink($sub))
            ->assertRedirectContains('https://wa.me/9647501234567?text=');

        $this->assertSame(Subscription::STAGE_NOTIFIED, $sub->fresh()->renewal_stage);
        $this->assertSame(1, ActivityReminder::where('channel', 'whatsapp')->count());
        $this->get(route('renewals.whatsapp', ['subscription' => $sub->id]))->assertForbidden(); // unsigned
    }

    // ----------------------------------------------------- quick add bot

    public function test_sending_a_domain_to_the_bot_creates_it_after_one_tap(): void
    {
        $this->fakeTelegramAndRdap([
            'rdap.example.test/domain/epochsp.com' => Http::response([
                'events' => [['eventAction' => 'expiration', 'eventDate' => '2027-04-01T10:00:00Z']],
                'entities' => [['roles' => ['registrar'], 'vcardArray' => ['vcard', [['fn', [], 'text', 'NameCheap, Inc.']]]]],
            ]),
        ]);
        $client = $this->client(['business_name' => 'Epoch SP']);

        $this->webhook(['message' => ['text' => 'epochsp.com 100$', 'chat' => ['id' => 42]]])->assertOk();

        $preview = collect(Http::recorded())->map(fn ($p) => $p[0])->first(fn ($r) => str_ends_with($r->url(), '/sendMessage'));
        $this->assertStringContainsString('2027-04-01', $preview['text']);
        $this->assertStringContainsString('NameCheap', $preview['text']);
        $saveButton = collect($preview['reply_markup']['inline_keyboard'])->flatten(1)
            ->first(fn ($b) => str_starts_with($b['callback_data'] ?? '', 's:') && str_ends_with($b['callback_data'], ":{$client->id}"));
        $this->assertNotNull($saveButton);

        $this->tap($saveButton['callback_data'])->assertOk();

        $sub = Subscription::where('domain_name', 'epochsp.com')->firstOrFail();
        $this->assertSame($client->id, $sub->client_id);
        $this->assertSame('2027-04-01', $sub->expiry_date->toDateString());
        $this->assertSame('NameCheap', $sub->provider);
        $this->assertSame(100.0, (float) $sub->selling_price);
    }

    // ------------------------------------------------------ quick add web

    public function test_paste_box_turns_a_list_into_subscriptions(): void
    {
        $this->fakeTelegramAndRdap();
        $this->actingAs(User::factory()->create());
        $client = $this->client();

        $component = Livewire::test(QuickAdd::class)
            ->set('input', "norduz.net \$100 6/28/2026\nghsooncompany.com.iq 100k 1/9/2026")
            ->call('read')
            ->assertCount('drafts', 2)
            ->assertSee('بەروارەکە ڕوون نییە');

        $component->call('save')->assertHasErrors(['drafts.0.client_id', 'drafts.1.client_id']);

        $component->set('drafts.0.client_id', $client->id)
            ->set('drafts.1.client_id', $client->id)
            ->call('pickDate', 1, '2026-09-01')
            ->call('save')
            ->assertRedirect(route('admin.renewals'));

        $iq = Subscription::where('domain_name', 'ghsooncompany.com.iq')->firstOrFail();
        $this->assertSame('IQD', $iq->currency);
        $this->assertSame('2026-09-01', $iq->expiry_date->toDateString());
        $this->assertSame('IQ Registry (CMC)', $iq->provider);
        $this->assertSame('expired', Subscription::where('domain_name', 'norduz.net')->value('status'));
    }

    // ----------------------------------------------------------- monthly

    public function test_monthly_summary_totals_this_month_per_currency(): void
    {
        $this->travelTo(now()->startOfMonth()->addDays(2));
        $c = $this->client();
        $this->sub($c, 5, ['selling_price' => 100, 'cost_price' => 20]);
        $this->sub($c, 6, ['domain_name' => 'x.com.iq', 'currency' => 'IQD', 'selling_price' => 150000, 'cost_price' => 0]);

        $this->assertSame(0, Artisan::call('renewals:monthly', ['--dry-run' => true]));
        $output = Artisan::output();
        $this->assertStringContainsString('نوێکردنەوەکانی ئەم مانگە: 2', $output);
        // never converted or added; labelled as a forecast, since renewal prices are not money received
        $this->assertStringContainsString('نرخی نوێکردنەوەکان (پێشبینی): $100 · 150,000 د.ع', $output);
    }
}
