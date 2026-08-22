<?php

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class Login extends Component
{
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

    public function login()
    {
        $this->validate();

        if (!Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            throw ValidationException::withMessages([
                'email' => 'ئیمەیڵ یان وشەی نهێنی هەڵەیە. تکایە دووبارە هەوڵ بدەرەوە.',
            ]);
        }

        session()->regenerate();

        return redirect()->intended(route('admin.dashboard'));
    }

    public function render()
    {
        return view('livewire.auth.login')
            ->layout('layouts.guest', ['title' => 'چوونەژوورەوەی ئەدمین | iCode Hub']);
    }
}
