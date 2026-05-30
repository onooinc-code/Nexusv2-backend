<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        $schedule
            ->command('monitor:reverb-health')
            ->everyFiveMinutes()
            ->withoutOverlapping()
            ->description('Run periodic Reverb WebSocket health checks.');

        $schedule
            ->command('proactive:run-scheduler')
            ->everyMinute()
            ->withoutOverlapping()
            ->description('Run proactive AI autonomous trigger scheduler.');

        $schedule
            ->command('monitor:settings-health')
            ->everyFifteenMinutes()
            ->withoutOverlapping()
            ->description('Run periodic health checks for settings and integration credentials.');
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
