<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Auto-logout library visits schedule - runs every 15 minutes during operating hours
Schedule::command('library:auto-logout')
    ->everyFifteenMinutes()
    ->between('07:00', '23:00')
    ->withoutOverlapping();

// Safety run at 23:30 to catch any stragglers
Schedule::command('library:auto-logout')
    ->dailyAt('23:30')
    ->withoutOverlapping();
