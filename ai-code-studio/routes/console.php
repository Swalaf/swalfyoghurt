<?php

use App\Services\Billing\Billing;
use App\Services\Licensing\LicenseClient;
use App\Support\Installation;
use App\Support\Settings;
use Illuminate\Support\Facades\Schedule;

// Heartbeat so the installer and System health can tell cron is running.
Schedule::call(fn () => @file_put_contents(storage_path('app/cron-heartbeat'), now()->toIso8601String()))
    ->everyMinute()->name('cron-heartbeat');

// Shared-hosting friendly queue worker: drain the queue once a minute.
Schedule::command('queue:work --stop-when-empty --max-time=55 --tries=1')
    ->everyMinute()->withoutOverlapping()->runInBackground();

// Billing: expire unpaid plans and refill monthly credits.
Schedule::call(function () {
    if (Installation::installed()) {
        Billing::maintain();
    }
})->dailyAt('02:10')->name('billing-maintenance');

// Re-check this installation's licence once a week.
Schedule::call(function () {
    if (Installation::installed() && Settings::get('license_code')) {
        app(LicenseClient::class)->verify();
    }
})->weeklyOn(1, '03:20')->name('license-verify');
