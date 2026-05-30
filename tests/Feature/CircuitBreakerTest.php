<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\AIProvider;
use App\Models\AIModel;
use App\Models\IntentRouting;
use App\Services\AiModelsHub\CircuitBreaker;
use App\Services\AiModelsHub\SemanticCache;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CircuitBreakerTest extends TestCase
{
    protected CircuitBreaker $circuitBreaker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->circuitBreaker = app(CircuitBreaker::class);
    }

    /** @test */
    public function it_executes_primary_callback_successfully()
    {
        $result = $this->circuitBreaker->executeWithFallback(
            fn() => ['success' => true, 'data' => 'primary_response', 'provider_id' => 'p1', 'model_id' => 'm1'],
            []
        );

        $this->assertTrue($result['success']);
        $this->assertEquals('primary_response', $result['data']);
        $this->assertFalse($result['fallback_triggered']);
    }

    /** @test */
    public function it_falls_back_to_secondary_when_primary_fails()
    {
        $result = $this->circuitBreaker->executeWithFallback(
            fn() => throw new \Exception('Primary failed'),
            [
                fn() => ['success' => true, 'data' => 'fallback_response', 'provider_id' => 'p2', 'model_id' => 'm2'],
            ]
        );

        $this->assertTrue($result['success']);
        $this->assertEquals('fallback_response', $result['data']);
        $this->assertTrue($result['fallback_triggered']);
    }

    /** @test */
    public function it_returns_failure_when_all_providers_fail()
    {
        $result = $this->circuitBreaker->executeWithFallback(
            fn() => throw new \Exception('Primary failed'),
            [
                fn() => throw new \Exception('Fallback 1 failed'),
                fn() => throw new \Exception('Fallback 2 failed'),
            ]
        );

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('errors', $result);
        $this->assertCount(3, $result['errors']);
    }

    /** @test */
    public function it_logs_rate_limit_exceptions_and_tries_fallback()
    {
        Log::shouldReceive('warning')->atLeast()->once();

        $result = $this->circuitBreaker->executeWithFallback(
            fn() => throw new \App\Exceptions\AiRateLimitException(),
            [
                fn() => ['success' => true, 'data' => 'rate_limit_fallback', 'provider_id' => 'p2', 'model_id' => 'm2'],
            ]
        );

        $this->assertTrue($result['success']);
        $this->assertTrue($result['fallback_triggered']);
    }
}
