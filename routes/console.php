<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

// Daily renewal digest to Telegram (needs `php artisan schedule:run` every minute via cron / Task Scheduler)
Schedule::command('renewals:bill-installments')->dailyAt('08:30')->timezone('Asia/Baghdad')->withoutOverlapping();
Schedule::command('domains:check-expiry')->dailyAt('08:45')->timezone('Asia/Baghdad')->withoutOverlapping();
Schedule::command('renewals:scan')->dailyAt('09:00')->timezone('Asia/Baghdad')->withoutOverlapping();
Schedule::command('renewals:monthly')->monthlyOn(1, '09:05')->timezone('Asia/Baghdad');

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
