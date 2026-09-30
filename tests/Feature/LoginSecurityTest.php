<?php

namespace Tests\Feature;

use App\Livewire\Auth\Login;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class LoginSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_five_wrong_passwords_lock_the_login_and_alert_telegram(): void
    {
        config(['services.telegram.bot_token' => 'TEST', 'services.telegram.chat_id' => '42']);
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true])]);
        User::factory()->create(['email' => 'me@icodegroup.net', 'password' => bcrypt('correct-horse')]);

        for ($i = 0; $i < 5; $i++) {
            Livewire::test(Login::class)->set('email', 'me@icodegroup.net')->set('password', 'wrong-pass')->call('login');
        }
        Http::assertSent(fn ($r) => str_contains($r['text'], 'هەوڵی چوونەژوورەوەی هەڵە'));

        // Even the right password is refused while locked.
        Livewire::test(Login::class)->set('email', 'me@icodegroup.net')->set('password', 'correct-horse')->call('login')
            ->assertHasErrors('email');
        $this->assertGuest();
    }

    public function test_successful_login_is_reported_to_telegram(): void
    {
        config(['services.telegram.bot_token' => 'TEST', 'services.telegram.chat_id' => '42']);
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true])]);
        User::factory()->create(['email' => 'me@icodegroup.net', 'password' => bcrypt('correct-horse')]);

        Livewire::test(Login::class)->set('email', 'me@icodegroup.net')->set('password', 'correct-horse')->call('login')
            ->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticated();
        Http::assertSent(fn ($r) => str_contains($r['text'], 'چوونەژوورەوە بۆ iCode Hub'));
    }
}
