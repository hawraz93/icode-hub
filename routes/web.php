<?php

use App\Livewire\Admin\ClientsManager;
use App\Livewire\Admin\ContractsManager;
use App\Livewire\Admin\Dashboard;
use App\Livewire\Admin\ExpensesManager;
use App\Livewire\Admin\InvoicesManager;
use App\Livewire\Admin\ProfileManager;
use App\Livewire\Admin\ProjectsManager;
use App\Livewire\Admin\QuickAdd;
use App\Livewire\Admin\RenewalRadar;
use App\Livewire\Admin\ServersManager;
use App\Livewire\Admin\SubscriptionsManager;
use App\Livewire\Auth\Login;
use App\Livewire\Public\ClientPortal;
use App\Livewire\Public\PortfolioHome;
use App\Http\Controllers\RenewalWhatsappController;
use App\Http\Controllers\TelegramWebhookController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// Public Routes
Route::get('/', PortfolioHome::class)->name('home');
Route::get('/portal', ClientPortal::class)->name('client.portal');
Route::get('/lang/{locale}', function (string $locale) {
    if (in_array($locale, ['en', 'ku', 'ar'])) {
        session(['locale' => $locale]);
    }
    return redirect()->back();
})->name('set.locale');

// Telegram bot webhook (verified by secret header) and signed WhatsApp links from Telegram buttons
Route::post('/telegram/webhook', TelegramWebhookController::class)->name('telegram.webhook');
Route::get('/r/wa/{subscription}', RenewalWhatsappController::class)->middleware('signed')->name('renewals.whatsapp');

// Authentication Routes
Route::get('/login', Login::class)->name('login')->middleware('guest');
Route::post('/logout', function () {
    Auth::logout();
    session()->invalidate();
    session()->regenerateToken();
    return redirect()->route('login');
})->name('logout');

// Admin Operating System Routes (Protected by Auth)
Route::prefix('admin')->name('admin.')->middleware('auth')->group(function () {
    Route::get('/', Dashboard::class)->name('dashboard');
    Route::get('/renewals', RenewalRadar::class)->name('renewals');
    Route::get('/renewals/add', QuickAdd::class)->name('quick-add');
    Route::get('/subscriptions', SubscriptionsManager::class)->name('subscriptions');
    Route::get('/servers', ServersManager::class)->name('servers');
    Route::get('/expenses', ExpensesManager::class)->name('expenses');
    Route::get('/clients', ClientsManager::class)->name('clients');
    Route::get('/invoices', InvoicesManager::class)->name('invoices');
    Route::get('/contracts', ContractsManager::class)->name('contracts');
    Route::get('/projects', ProjectsManager::class)->name('projects');
    Route::get('/profile', ProfileManager::class)->name('profile');
});

