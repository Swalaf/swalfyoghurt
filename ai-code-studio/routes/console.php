<?php

use Illuminate\Support\Facades\Schedule;

// Heartbeat so the installer and System health can tell cron is running.
Schedule::call(fn () => @file_put_contents(storage_path('app/cron-heartbeat'), now()->toIso8601String()))
    ->everyMinute()->name('cron-heartbeat');

// Shared-hosting friendly queue worker: drain the queue once a minute.
Schedule::command('queue:work --stop-when-empty --max-time=55 --tries=1')
    ->everyMinute()->withoutOverlapping()->runInBackground();
