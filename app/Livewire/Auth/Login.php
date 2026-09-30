<?php

namespace App\Livewire\Auth;

use App\Services\TelegramNotifier;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class Login extends Component
{
    /** Wrong passwords allowed per email+IP before a lockout. */
    private const MAX_ATTEMPTS = 5;

    private const LOCKOUT_SECONDS = 300;

    public string $email = '';
    public string $password = '';
    public bool $remember = true;

    protected function rules(): array
    {
        return [
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ];
    }

    public function login(TelegramNotifier $telegram)
    {
        $this->validate();

        $key = 'login:' . Str::lower($this->email) . '|' . request()->ip();

        if (RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS)) {
            $minutes = (int) ceil(RateLimiter::availableIn($key) / 60);
            throw ValidationException::withMessages([
                'email' => "هەوڵی زۆر هەڵە درا. تکایە {$minutes} خولەکی تر هەوڵ بدەرەوە.",
            ]);
        }

        if (! Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            RateLimiter::hit($key, self::LOCKOUT_SECONDS);

            if (RateLimiter::attempts($key) === self::MAX_ATTEMPTS) {
                $telegram->send('🚨 <b>هەوڵی چوونەژوورەوەی هەڵە</b>' . "\n"
                    . self::MAX_ATTEMPTS . ' جار وشەی نهێنی هەڵە بۆ ' . TelegramNotifier::escape($this->email) . "\n"
                    . 'IP: <code>' . TelegramNotifier::escape(request()->ip()) . '</code> · ٥ خولەک داخرا');
            }

            throw ValidationException::withMessages([
                'email' => 'ئیمەیڵ یان وشەی نهێنی هەڵەیە. تکایە دووبارە هەوڵ بدەرەوە.',
            ]);
        }

        RateLimiter::clear($key);
        session()->regenerate();

        // Every successful admin login is reported, so an unknown one is noticed immediately.
        $telegram->send('🔐 <b>چوونەژوورەوە بۆ iCode Hub</b>' . "\n"
            . TelegramNotifier::escape(Auth::user()->email) . "\n"
            . 'IP: <code>' . TelegramNotifier::escape(request()->ip()) . '</code>' . "\n"
            . TelegramNotifier::escape(Str::limit((string) request()->userAgent(), 90)) . "\n"
            . 'ئەگەر تۆ نەبوویت، یەکسەر وشەی نهێنی بگۆڕە.');

        return redirect()->intended(route('admin.dashboard'));
    }

    public function render()
    {
        return view('livewire.auth.login')
            ->layout('layouts.guest', ['title' => 'چوونەژوورەوەی ئەدمین | iCode Hub']);
    }
}
