<?php

namespace Tests\Feature\Console;

use App\Services\CredentialValidationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MonitorSettingsHealthCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_monitor_settings_health_command_executes(): void
    {
        $this->artisan('monitor:settings-health')
            ->expectsOutput('Starting settings and credential health checks...')
            ->expectsOutput('Credential validation completed:')
            ->assertExitCode(0);
    }

    public function test_monitor_settings_health_command_handles_failures(): void
    {
        $mockValidationService = $this->mock(CredentialValidationService::class);
        $mockValidationService->shouldReceive('validateAllCredentials')
            ->andThrow(new \Exception('Validation failed'));

        $this->instance(CredentialValidationService::class, $mockValidationService);

        $this->artisan('monitor:settings-health')
            ->expectsOutput('Settings health check failed: Validation failed')
            ->assertExitCode(1);
    }

    public function test_settings_health_check_is_scheduled(): void
    {
        $kernel = $this->app->make(\App\Console\Kernel::class);
        $schedule = $kernel->resolveConsoleSchedule();

        $commands = collect($schedule->events())
            ->map(fn ($event) => $event->command)
            ->filter()
            ->values()
            ->all();

        $this->assertStringContainsString('monitor:settings-health', implode(' ', $commands));
    }
}
