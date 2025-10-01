<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        // Runs at 07:00, 07:15, ..., 16:45, 17:00 daily
        $schedule
            ->command('library:auto-logout')
            ->everyFifteenMinutes()
            ->between('07:00', '17:00')
            ->withoutOverlapping();
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');
    }
}
