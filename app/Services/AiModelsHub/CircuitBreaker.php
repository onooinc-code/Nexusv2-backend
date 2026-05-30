<?php

namespace App\Services\AiModelsHub;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Exception;

class CircuitBreaker
{
    protected $failureThreshold = 5;
    protected $recoveryTimeout = 60; // seconds
    protected $cachePrefix = 'circuit_breaker:';

    /**
     * Execute a callback with circuit breaker protection and fallback
     * @param callable $primaryCallback
     * @param callable[] $fallbackCallbacks
     * @return mixed
     * @throws Exception
     */
    public function executeWithFallback(callable $primaryCallback, array $fallbackCallbacks = [])
    {
        $errors = [];
        
        try {
            $result = $primaryCallback();
            $result['fallback_triggered'] = false;
            return $result;
        } catch (Exception $e) {
            Log::warning("Primary provider failed: {$e->getMessage()}");
            $errors[] = $e->getMessage();
            
            if ($e->getCode() == 429) {
                Log::warning("Rate limit hit on primary execution.");
            }
            
            // Try fallback providers
            foreach ($fallbackCallbacks as $index => $fallbackCallback) {
                try {
                    Log::info("Trying fallback sequence: " . ($index + 1));
                    
                    $result = $fallbackCallback();
                    $result['fallback_triggered'] = true;
                    return $result;
                } catch (Exception $fallbackE) {
                    Log::warning("Fallback sequence " . ($index + 1) . " failed: {$fallbackE->getMessage()}");
                    $errors[] = $fallbackE->getMessage();
                    
                    if ($fallbackE->getCode() == 429) {
                        Log::warning("Rate limit hit on fallback.");
                    }
                    continue;
                }
            }
            
            // If all fail, return a structured error result instead of just throwing (or throw a custom exception)
            return [
                'success' => false,
                'errors' => $errors,
                'message' => 'All providers failed.'
            ];
        }
    }

    /**
     * Check if circuit breaker is open for a provider
     */
    protected function isCircuitOpen($providerId)
    {
        $key = $this->cachePrefix . "provider:{$providerId}:state";
        $state = Cache::get($key, 'closed');
        
        if ($state === 'open') {
            // Check if recovery timeout has passed
            $lastFailureTime = Cache::get($this->cachePrefix . "provider:{$providerId}:last_failure");
            if ($lastFailureTime && (now()->getTimestamp() - $lastFailureTime) > $this->recoveryTimeout) {
                // Try half-open state
                Cache::put($key, 'half-open', $this->recoveryTimeout);
                return false;
            }
            return true;
        }
        
        return false;
    }

    /**
     * Record a failure for a provider
     */
    protected function recordFailure($providerId)
    {
        $key = $this->cachePrefix . "provider:{$providerId}:failures";
        $failures = Cache::increment($key, 1);
        
        if ($failures === 1) {
            // First failure, set expiration
            Cache::put($key, 1, $this->recoveryTimeout);
        }
        
        if ($failures >= $this->failureThreshold) {
            // Open the circuit
            Cache::put($this->cachePrefix . "provider:{$providerId}:state", 'open', $this->recoveryTimeout);
            Cache::put($this->cachePrefix . "provider:{$providerId}:last_failure", now()->getTimestamp(), $this->recoveryTimeout);
        }
    }

    /**
     * Record a success for a provider
     */
    protected function recordSuccess($providerId)
    {
        // Reset failure count on success
        Cache::forget($this->cachePrefix . "provider:{$providerId}:failures");
        Cache::put($this->cachePrefix . "provider:{$providerId}:state", 'closed', $this->recoveryTimeout);
    }
}