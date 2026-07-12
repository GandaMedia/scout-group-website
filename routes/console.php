<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('calendar:sync-osm-feeds')
    ->name('calendar:sync-osm-feeds')
    ->dailyAt('00:00')
    ->withoutOverlapping()
    ->runInBackground();

Schedule::command('waiting-list:sync')
    ->name('waiting-list:sync')
    ->everyFiveMinutes()
    ->withoutOverlapping();
