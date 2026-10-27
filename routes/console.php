<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('meta-ads:store-today')
    ->dailyAt(config('services.meta.daily_sync_time', '14:33'))
    ->timezone(config('app.timezone'))
    ->withoutOverlapping(10)
    ->appendOutputTo(storage_path('logs/meta-ads-scheduler.log'));
