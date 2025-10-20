<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        // Scheduling is defined in routes/console.php using the Schedule facade.
        // Intentionally left empty to avoid duplication.
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');
    }
}
