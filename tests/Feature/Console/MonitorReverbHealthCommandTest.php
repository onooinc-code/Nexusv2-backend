<?php

namespace Tests\Feature\Console;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MonitorReverbHealthCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_monitor_reverb_health_command_returns_success_when_reverb_is_healthy(): void
    {
        config([
            'broadcasting.connections.reverb.host' => '127.0.0.1',
            'broadcasting.connections.reverb.port' => 6001,
            'broadcasting.connections.reverb.scheme' => 'https',
        ]);

        Http::fake([
            'https://127.0.0.1:6001/health' => Http::response(['status' => 'healthy'], 200),
        ]);

        $this->artisan('monitor:reverb-health')
            ->expectsOutput('Reverb health check passed.')
            ->assertExitCode(0);
    }

    public function test_monitor_reverb_health_command_reports_failure_when_reverb_is_unhealthy(): void
    {
        config([
            'broadcasting.connections.reverb.host' => '127.0.0.1',
            'broadcasting.connections.reverb.port' => 6001,
            'broadcasting.connections.reverb.scheme' => 'https',
        ]);

        Http::fake([
            'https://127.0.0.1:6001/health' => Http::response('', 503),
        ]);

        $this->artisan('monitor:reverb-health')
            ->expectsOutput('Reverb health check failed with status 503.')
            ->assertExitCode(1);
    }
}
