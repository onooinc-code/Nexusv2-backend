<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Services\AiModelsHub\CircuitBreaker;
use Illuminate\Support\Facades\Cache;

class CircuitBreakerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush(); // Clear cache before each test
    }

    /** @test */
    public function it_allows_request_when_closed()
    {
        $breaker = new CircuitBreaker('test-service', 3, 60);

        $result = $breaker->execute(function () {
            return 'success';
        });

        $this->assertEquals('success', $result);
        $this->assertFalse($breaker->isOpen());
    }

    /** @test */
    public function it_opens_after_consecutive_failures()
    {
        $breaker = new CircuitBreaker('test-service', 2, 60);

        // First failure
        $breaker->execute(function () {
            throw new \Exception('First failure');
        });

        $this->assertFalse($breaker->isOpen()); // Still closed

        // Second failure - should open the circuit
        $breaker->execute(function () {
            throw new \Exception('Second failure');
        });

        $this->assertTrue($breaker->isOpen()); // Now open

        // Next request should fail fast
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Circuit breaker is open');
        $breaker->execute(function () {
            return 'should not execute';
        });
    }

    /** @test */
    public function it_half_opens_after_timeout_and_closes_on_success()
    {
        $breaker = new CircuitBreaker('test-service', 2, 1); // 1 second timeout

        // Cause failures to open the circuit
        $breaker->execute(function () {
            throw new \Exception('Failure 1');
        });
        $breaker->execute(function () {
            throw new \Exception('Failure 2');
        });

        $this->assertTrue($breaker->isOpen());

        // Wait for timeout (in real test we'd mock time, but for simplicity)
        sleep(2);

        // Now should allow one request to test (half-open)
        $result = $breaker->execute(function () {
            return 'success';
        });

        $this->assertEquals('success', $result);
        $this->assertFalse($breaker->isOpen()); // Should be closed now after success
    }

    /** @test */
    public function it_half_opens_and_reopens_on_failure()
    {
        $breaker = new CircuitBreaker('test-service', 2, 1); // 1 second timeout

        // Cause failures to open the circuit
        $breaker->execute(function () {
            throw new \Exception('Failure 1');
        });
        $breaker->execute(function () {
            throw new \Exception('Failure 2');
        });

        $this->assertTrue($breaker->isOpen());

        // Wait for timeout
        sleep(2);

        // Request fails during half-open - should reopen
        $this->expectException(\Exception::class);
        $breaker->execute(function () {
            throw new \Exception('Failure during half-open');
        });

        $this->assertTrue($breaker->isOpen()); // Should be open again
    }

    /** @test */
    public function it_gets_status()
    {
        $breaker = new CircuitBreaker('test-service', 3, 60);

        $status = $breaker->getStatus();

        $this->assertEquals('closed', $status['state']);
        $this->assertEquals(0, $status['failure_count']);
        $this->assertEquals(3, $status['failure_threshold']);
        $this->assertEquals(60, $status['timeout_seconds']);
    }
}