<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MonitoringHealthTest extends TestCase
{
    use RefreshDatabase;

    public function test_monitoring_health_endpoint_reports_healthy_when_checks_pass(): void
    {
        $this->withoutExceptionHandling();

        $redis = \Mockery::mock();
        $redis->shouldReceive('ping')->once()->andReturn('PONG');
        $redis->shouldReceive('llen')->with('queues:default')->andReturn(0);
        $redis->shouldReceive('llen')->with('queues:critical')->andReturn(0);
        $redis->shouldReceive('llen')->with('queues:llm-inference')->andReturn(0);
        $redis->shouldReceive('llen')->with('queues:batch')->andReturn(0);

        Redis::shouldReceive('connection')->andReturn($redis);

        Http::fake([
            'https://127.0.0.1:6001/health' => Http::response(['ok' => true], 200),
        ]);

        $response = $this->getJson('/api/v1/monitoring/health');

        $response->assertOk();
        $response->assertJsonPath('status', 'healthy');
        $response->assertJsonPath('checks.redis.ok', true);
        $response->assertJsonPath('checks.reverb.ok', true);
        $response->assertJsonPath('checks.queue.ok', true);
    }
}
