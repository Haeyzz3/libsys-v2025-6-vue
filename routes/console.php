<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Models\LibrarySetting;
use App\Models\LibraryVisit;
use Carbon\Carbon;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Auto logout command: closes any active (exit_time null) visits after operating hours closing time
Artisan::command('library:auto-logout', function () {
    $now = Carbon::now();
    $operating = LibrarySetting::getValue('operating_hours', []);
    $enabled = LibrarySetting::getValue('auto_logout_enabled', true);
    if (!$enabled) {
        $this->info('Auto logout disabled.');
        return 0;
    }
    $dayKey = strtolower($now->format('l'));
    $hours = $operating[$dayKey] ?? null;
    if (!$hours || empty($hours['close'])) {
        $this->info('No closing hours configured for today.');
        return 0;
    }
    try {
        $closing = Carbon::parse($now->format('Y-m-d').' '.$hours['close'].':00');
    } catch (\Exception $e) {
        $this->error('Invalid closing time format.');
        return 1;
    }
    if ($now->lte($closing)) {
        $this->info('Current time not past closing ('.$closing->toDateTimeString().'). Nothing to do.');
        return 0;
    }
    // Update only visits from today still open, setting exit_time to the closing time (not the time of command execution)
    $affected = LibraryVisit::whereNull('exit_time')
        ->whereDate('entry_time', $now->toDateString())
        ->update(['exit_time' => $closing]);
    $this->info('Auto-logged out '.$affected.' visit(s) at closing time '.$closing->toTimeString().'.');
    return 0;
})->purpose('Automatically logout patrons remaining past closing time');
