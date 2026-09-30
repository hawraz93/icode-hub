<?php

namespace Tests\Feature;

use App\Livewire\Admin\RenewalRadar;
use App\Livewire\Admin\SubscriptionsManager;
use App\Models\ActivityReminder;
use App\Models\Client;
use App\Models\Subscription;
use App\Models\User;
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
        Http::assertSent(function ($request) {
            $text = $request['text'];

            return $request['chat_id'] === '42'
                && str_contains($text, 'late.com')
                && str_contains($text, 'soon.com')
                && str_contains($text, 'milestone.com')
                && ! str_contains($text, 'quiet.com');
        });
        $this->assertSame(3, ActivityReminder::where('channel', 'telegram')->where('status', 'sent')->count());

        // Running again the same day sends nothing new.
        $this->artisan('renewals:scan')->expectsOutput('Nothing needs a reminder today.');
        Http::assertSentCount(1);
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
