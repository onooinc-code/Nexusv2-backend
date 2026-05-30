<?php

namespace Tests\Feature;

use App\Console\Kernel;
use Tests\TestCase;

class ScheduleTest extends TestCase
{
    public function test_reverb_monitoring_command_is_scheduled(): void
    {
        $kernel = $this->app->make(Kernel::class);
        $schedule = $kernel->resolveConsoleSchedule();

        $commands = collect($schedule->events())
            ->map(fn ($event) => $event->command)
            ->filter()
            ->values()
            ->all();

        $this->assertStringContainsString('monitor:reverb-health', implode(' ', $commands));
    }
}
