<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        // Run auto logout shortly after closing time (22:00) daily
        $schedule->command('library:auto-logout')->dailyAt('22:05');
        // Redundant morning catch-up run in case machine was off at night
        $schedule->command('library:auto-logout')->dailyAt('08:10');
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');
    }
}
