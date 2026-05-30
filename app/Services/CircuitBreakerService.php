<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Exception;

class CircuitBreakerService
{
    protected string $prefix = 'circuit_breaker:';
    protected int $failureThreshold;
    protected int $decaySeconds;
    protected int $halfOpenSeconds;

    public function __construct(int $failureThreshold = 5, int $decaySeconds = 300, int $halfOpenSeconds = 60)
    {
        $this->failureThreshold = $failureThreshold;
        $this->decaySeconds = $decaySeconds;
        $this->halfOpenSeconds = $halfOpenSeconds;
    }

    public function call(string $serviceName, callable $callback): mixed
    {
        $key = $this->getStateKey($serviceName);
        $state = Cache::get($key, ['status' => 'closed', 'failures' => 0, 'opened_at' => null]);

        if ($this->isOpen($state)) {
            if ($this->isHalfOpen($state)) {
                // allow a test call through
                return $this->attemptCall($key, $callback, $state);
            }

            throw new Exception("Circuit breaker open for service: {$serviceName}");
        }

        return $this->attemptCall($key, $callback, $state);
    }

    protected function attemptCall(string $key, callable $callback, array $state): mixed
    {
        try {
            $result = $callback();
            $this->resetState($key);
            return $result;
        } catch (Exception $exception) {
            $this->recordFailure($key, $state);
            throw $exception;
        }
    }

    protected function recordFailure(string $key, array $state): void
    {
        $state['failures'] = ($state['failures'] ?? 0) + 1;
        $state['opened_at'] = $state['opened_at'] ?? now();

        if ($state['failures'] >= $this->failureThreshold) {
            $state['status'] = 'open';
            $state['opened_at'] = now();
        }

        Cache::put($key, $state, $this->decaySeconds);
    }

    protected function resetState(string $key): void
    {
        Cache::forget($key);
    }

    protected function isOpen(array $state): bool
    {
        return ($state['status'] ?? 'closed') === 'open'
            && ! $this->isHalfOpen($state);
    }

    protected function isHalfOpen(array $state): bool
    {
        if (($state['status'] ?? '') !== 'open') {
            return false;
        }

        $openedAt = $state['opened_at'] ? now()->parse($state['opened_at']) : null;
        if (! $openedAt) {
            return false;
        }

        return now()->diffInSeconds($openedAt) >= $this->halfOpenSeconds;
    }

    protected function getStateKey(string $serviceName): string
    {
        return $this->prefix . $serviceName;
    }
}
