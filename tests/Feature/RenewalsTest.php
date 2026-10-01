<?php

namespace Tests\Feature;

use App\Livewire\Admin\RenewalRadar;
use App\Livewire\Admin\SubscriptionsManager;
use App\Models\ActivityReminder;
use App\Models\Client;
use App\Models\Server;
use App\Models\Subscription;
use App\Models\User;
use App\Services\DomainExpiryLookup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class RenewalsTest extends TestCase
{
    use RefreshDatabase;

    private function client(array $attrs = []): Client
    {
        return Client::create(array_merge(['name' => 'Rawa', 'business_name' => 'Rawa Clinic', 'phone' => '0750 123 4567'], $attrs));
    }

    private function sub(Client $client, int $days, array $attrs = []): Subscription
    {
        return Subscription::create(array_merge([
            'client_id' => $client->id,
            'name' => 'Hosting',
            'type' => 'hosting',
            'domain_name' => 'rawa.com',
            'cost_price' => 25,
            'selling_price' => 80,
            'billing_cycle' => 'annual',
            'start_date' => now()->subYear()->toDateString(),
            'expiry_date' => now()->addDays($days)->toDateString(),
        ], $attrs));
    }

    public function test_whatsapp_number_prefers_whatsapp_field_and_converts_local_format(): void
    {
        $this->assertSame('9647501234567', $this->client()->whatsapp_number);
        $this->assertSame('9647709998888', $this->client(['whatsapp' => '+964 770 999 8888'])->whatsapp_number);
        $this->assertNull($this->client(['phone' => null])->whatsapp_number);
    }

    public function test_dinar_prices_stay_in_dinars_and_totals_are_kept_per_currency(): void
    {
        config(['app.usd_to_iqd' => 1500]);
        $c = $this->client();
        $iqd = $this->sub($c, 5, ['type' => 'domain', 'domain_name' => 'ghsooncompany.com.iq', 'currency' => 'IQD', 'selling_price' => 100000, 'cost_price' => 45000]);
        $usd = $this->sub($c, 5, ['type' => 'domain', 'domain_name' => 'epochsp.com', 'currency' => 'USD', 'selling_price' => 100, 'cost_price' => 12]);

        $this->assertSame('100,000 د.ع', $iqd->selling_label);
        $this->assertStringContainsString('بڕی نوێکردنەوە: 100,000 د.ع', $iqd->whatsappMessage('ku'));
        $this->assertStringNotContainsString('$', $iqd->whatsappMessage('ku'));
        $this->assertStringContainsString('بڕی نوێکردنەوە: $100', $usd->whatsappMessage('ku'));
        $this->assertStringNotContainsString('د.ع', $usd->whatsappMessage('ku'));

        $this->actingAs(User::factory()->create());
        $summary = Livewire::test(RenewalRadar::class)->viewData('summary');
        $this->assertSame(['USD' => 100.0, 'IQD' => 100000.0], $summary['collect']);
        $this->assertSame(['USD' => 12.0, 'IQD' => 45000.0], $summary['pay']);
    }

    public function test_long_expired_items_are_reminded_on_mondays_only(): void
    {
        config(['services.telegram.bot_token' => 'TEST', 'services.telegram.chat_id' => '42']);
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true])]);
        $this->sub($this->client(), -90, ['domain_name' => 'norduz.net']);

        $this->travelTo(now()->next('Tuesday'));
        $this->artisan('renewals:scan')->expectsOutput('Nothing needs a reminder today.');

        $this->travelTo(now()->next('Monday'));
        $this->artisan('renewals:scan')->assertSuccessful();
        Http::assertSent(fn ($r) => str_contains($r['text'], 'norduz.net'));
    }

    public function test_open_renewals_includes_expired_but_not_cancelled_or_far_away(): void
    {
        $c = $this->client();
        $late = $this->sub($c, -5);
        $soon = $this->sub($c, 10);
        $this->sub($c, 60);
        $this->sub($c, 3, ['status' => 'cancelled']);

        $ids = Subscription::openRenewals(30)->pluck('id')->all();

        $this->assertEqualsCanonicalizing([$late->id, $soon->id], $ids);
    }

    public function test_renew_extends_from_expiry_and_resets_stage(): void
    {
        $sub = $this->sub($this->client(), 10, ['renewal_stage' => Subscription::STAGE_PAID]);
        $expected = now()->addDays(10)->addYear()->toDateString();

        $sub->renew();

        $sub->refresh();
        $this->assertSame($expected, $sub->expiry_date->toDateString());
        $this->assertSame(Subscription::STAGE_NONE, $sub->renewal_stage);
        $this->assertSame('active', $sub->status);
    }

    public function test_scan_marks_expired_and_sends_digest_to_telegram(): void
    {
        config(['services.telegram.bot_token' => 'TEST', 'services.telegram.chat_id' => '42']);
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true])]);

        $c = $this->client();
        $late = $this->sub($c, -2, ['domain_name' => 'late.com']);
        $this->sub($c, 3, ['domain_name' => 'soon.com']);
        $this->sub($c, 20, ['domain_name' => 'quiet.com']);          // not a milestone day
        $this->sub($c, 30, ['domain_name' => 'milestone.com']);      // matches reminder_days_before

        $this->artisan('renewals:scan')->assertSuccessful();

        $this->assertSame('expired', $late->fresh()->status);

        // One summary message, then one card with buttons per due subscription.
        $texts = collect(Http::recorded())->map(fn ($pair) => $pair[0]['text'])->all();
        $this->assertCount(4, $texts);
        $this->assertStringContainsString('نوێکردنەوەکانی ئەمڕۆ', $texts[0]);
        $cards = implode("\n", array_slice($texts, 1));
        foreach (['late.com', 'soon.com', 'milestone.com'] as $domain) {
            $this->assertStringContainsString($domain, $cards);
        }
        $this->assertStringNotContainsString('quiet.com', implode("\n", $texts));
        Http::assertSent(fn ($r) => isset($r['reply_markup']['inline_keyboard']) && str_contains($r['text'], 'soon.com'));
        $this->assertSame(3, ActivityReminder::where('channel', 'telegram')->where('status', 'sent')->count());

        // Running again the same day sends nothing new.
        $this->artisan('renewals:scan')->expectsOutput('Nothing needs a reminder today.');
        Http::assertSentCount(4);
    }

    public function test_telegram_test_explains_token_and_chat_errors(): void
    {
        Http::fake([
            'api.telegram.org/*/getUpdates' => Http::response(['ok' => false, 'error_code' => 401, 'description' => 'Unauthorized'], 401),
            'api.telegram.org/*/sendMessage' => Http::response(['ok' => false, 'description' => 'Bad Request: chat not found'], 400),
        ]);

        config(['services.telegram.bot_token' => 'BAD', 'services.telegram.chat_id' => null]);
        $this->artisan('telegram:test')
            ->expectsOutput('Telegram rejected the token: Unauthorized')
            ->assertFailed();

        config(['services.telegram.bot_token' => 'GOOD', 'services.telegram.chat_id' => '999']);

        $this->artisan('telegram:test')
            ->expectsOutput('Sending failed: Bad Request: chat not found')
            ->assertFailed();
    }

    public function test_radar_renders_and_walks_through_the_renewal_steps(): void
    {
        $this->actingAs(User::factory()->create());
        $sub = $this->sub($this->client(), 5, ['domain_name' => 'radar.com']);

        $this->get(route('admin.renewals'))->assertOk()->assertSee('radar.com');

        $component = Livewire::test(RenewalRadar::class)
            ->set('selectedId', $sub->id)
            ->assertSee('ناردن بە واتسئاپ')
            ->call('markNotified', $sub->id, 'ku');
        $this->assertSame(Subscription::STAGE_NOTIFIED, $sub->fresh()->renewal_stage);
        $this->assertSame(1, ActivityReminder::where('channel', 'whatsapp')->count());

        $component->call('setStage', $sub->id, 2);
        $this->assertSame(Subscription::STAGE_PAID, $sub->fresh()->renewal_stage);

        $component->call('setStage', $sub->id, 2); // tapping a done step undoes it
        $this->assertSame(Subscription::STAGE_NOTIFIED, $sub->fresh()->renewal_stage);

        $component->call('renew', $sub->id)->assertSet('selectedId', null);
        $this->assertSame(now()->addDays(5)->addYear()->toDateString(), $sub->fresh()->expiry_date->toDateString());
    }

    public function test_late_domain_renewal_extends_from_old_expiry_but_hosting_restarts_today(): void
    {
        $c = $this->client();
        $domain = $this->sub($c, -20, ['type' => 'domain']);
        $hosting = $this->sub($c, -20, ['type' => 'hosting']);

        $domain->renew();
        $hosting->renew();

        $this->assertSame(now()->subDays(20)->addYear()->toDateString(), $domain->fresh()->expiry_date->toDateString());
        $this->assertSame(now()->addYear()->toDateString(), $hosting->fresh()->expiry_date->toDateString());
    }

    public function test_server_renew_keeps_billing_anchor_day(): void
    {
        $server = Server::create([
            'name' => 'VPS', 'cost' => 32, 'billing_cycle' => 'monthly',
            'renewal_date' => now()->subDays(40)->toDateString(),
        ]);

        $next = $server->renew();

        $this->assertTrue($next->isFuture());
        $this->assertSame(now()->subDays(40)->day, $next->day);
    }

    public function test_registrable_domain_strips_subdomains_and_keeps_second_level_suffixes(): void
    {
        $this->assertSame('example.com', DomainExpiryLookup::registrableDomain('https://www.pos.example.com/path'));
        $this->assertSame('uni.edu.krd', DomainExpiryLookup::registrableDomain('exams.uni.edu.krd'));
        $this->assertSame('smartpharma.krd', DomainExpiryLookup::registrableDomain('pos.smartpharma.krd'));
        $this->assertNull(DomainExpiryLookup::registrableDomain('localhost'));
    }

    public function test_domain_check_follows_registry_renewals_and_flags_earlier_dates(): void
    {
        config(['services.telegram.bot_token' => 'TEST', 'services.telegram.chat_id' => '42']);
        $later = now()->addDays(200)->startOfDay();
        $earlier = now()->addDays(5)->startOfDay();
        $rdap = fn ($date) => Http::response(['events' => [['eventAction' => 'expiration', 'eventDate' => $date->toIso8601String()]]]);
        Http::fake([
            'data.iana.org/*' => Http::response(['services' => [[['com'], ['https://rdap.example.test/']]]]),
            'rdap.example.test/domain/rawa.com' => $rdap($later),
            'rdap.example.test/domain/early.com' => $rdap($earlier),
            'api.telegram.org/*' => Http::response(['ok' => true]),
        ]);

        $c = $this->client();
        $autoRenewed = $this->sub($c, 10, ['type' => 'domain', 'domain_name' => 'rawa.com', 'renewal_stage' => Subscription::STAGE_NOTIFIED]);
        $risky = $this->sub($c, 40, ['type' => 'domain', 'domain_name' => 'early.com']);
        $noRdap = $this->sub($c, 10, ['type' => 'domain', 'domain_name' => 'rawa.krd']);

        $this->artisan('domains:check-expiry')->assertSuccessful();

        // Renewed at the registry: followed automatically, workflow reset, you get told.
        $autoRenewed->refresh();
        $this->assertSame($later->toDateString(), $autoRenewed->expiry_date->toDateString());
        $this->assertSame(Subscription::STAGE_NONE, $autoRenewed->renewal_stage);
        Http::assertSent(fn ($r) => str_contains($r->url(), 'api.telegram.org') && str_contains($r['text'], 'rawa.com') && str_contains($r['text'], 'نوێکراونەتەوە'));

        // Registry says it expires sooner than recorded: never auto-applied, only flagged.
        $risky->refresh();
        $this->assertTrue($risky->has_registry_mismatch);
        $this->assertSame(now()->addDays(40)->toDateString(), $risky->expiry_date->toDateString());
        Http::assertSent(fn ($r) => str_contains($r->url(), 'api.telegram.org') && str_contains($r['text'], 'early.com'));

        $this->assertNull($noRdap->fresh()->registry_expiry_date);
        $this->assertNotNull($noRdap->fresh()->registry_checked_at);

        $this->actingAs(User::factory()->create());
        Livewire::test(RenewalRadar::class)->call('useRegistryDate', $risky->id);
        $this->assertSame($earlier->toDateString(), $risky->fresh()->expiry_date->toDateString());
    }

    public function test_radar_lists_servers_and_marks_them_paid(): void
    {
        $this->actingAs(User::factory()->create());
        $server = Server::create(['name' => 'Contabo VPS', 'cost' => 32, 'billing_cycle' => 'monthly', 'renewal_date' => now()->addDays(3)->toDateString()]);

        Livewire::test(RenewalRadar::class)
            ->assertSee('Contabo VPS')
            ->call('renewServer', $server->id);

        $this->assertSame(now()->addDays(3)->addMonth()->toDateString(), $server->fresh()->renewal_date->toDateString());
    }

    public function test_subscription_search_keeps_other_filters_applied(): void
    {
        $this->actingAs(User::factory()->create());
        $c = $this->client();
        $this->sub($c, 100, ['name' => 'Rawa domain', 'type' => 'domain', 'domain_name' => 'rawa-d.com']);
        $this->sub($c, 100, ['name' => 'Rawa email', 'type' => 'email', 'domain_name' => 'rawa-e.com']);

        Livewire::test(SubscriptionsManager::class)
            ->set('search', 'rawa')
            ->set('typeFilter', 'email')
            ->assertSee('rawa-e.com')
            ->assertDontSee('rawa-d.com');
    }
}
