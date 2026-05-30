<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Log;

class FallbackChainService
{
    protected array $chain = [];
    protected int $maxRetries = 3;
    protected float $backoffMultiplier = 2.0;
    protected int $initialDelayMs = 1000;

    public function __construct(array $chain = [])
    {
        $this->chain = $chain;
    }

    public function setChain(array $chain): void
    {
        $this->chain = $chain;
    }

    public function addToChain(ProviderInterface $provider, int $priority = 100): void
    {
        $this->chain[] = [
            'provider' => $provider,
            'priority' => $priority,
        ];

        usort($this->chain, fn($a, $b) => $a['priority'] <=> $b['priority']);
    }

    public function executeWithFallback(array $request): array
    {
        $errors = [];
        $lastError = null;

        foreach ($this->chain as $index => $chainItem) {
            $provider = $chainItem['provider'];
            $retries = 0;
            $delay = $this->initialDelayMs;

            while ($retries < $this->maxRetries) {
                try {
                    $result = $provider->execute($request);

                    if ($result['success']) {
                        $result['fallback_used'] = $index > 0;
                        $result['fallback_index'] = $index;
                        $result['fallback_chain'] = array_map(fn($item) => $item['provider']->getProviderName(), $this->chain);
                        return $result;
                    }

                    $lastError = $result['error'] ?? 'Unknown error';
                    $errors[] = [
                        'provider' => $provider->getProviderName(),
                        'error' => $lastError,
                        'attempt' => $retries + 1,
                    ];

                    if ($this->shouldRetry($lastError, $retries)) {
                        $retries++;
                        usleep($delay * 1000);
                        $delay *= $this->backoffMultiplier;
                        continue;
                    }

                    break;
                } catch (\Throwable $e) {
                    $lastError = $e->getMessage();
                    $errors[] = [
                        'provider' => $provider->getProviderName(),
                        'error' => $lastError,
                        'attempt' => $retries + 1,
                    ];

                    if ($this->shouldRetry($lastError, $retries)) {
                        $retries++;
                        usleep($delay * 1000);
                        $delay *= $this->backoffMultiplier;
                        continue;
                    }

                    break;
                }
            }

            Log::warning("Provider {$provider->getProviderName()} failed after {$retries} attempts");
        }

        return [
            'success' => false,
            'error' => 'All providers in fallback chain failed',
            'last_error' => $lastError,
            'errors' => $errors,
            'fallback_chain' => array_map(fn($item) => $item['provider']->getProviderName(), $this->chain),
        ];
    }

    protected function shouldRetry(string $error, int $attempt): bool
    {
        if ($attempt >= $this->maxRetries - 1) return false;

        $retryablePatterns = [
            'rate limit',
            'timeout',
            '503',
            '502',
            '500',
            'network',
            'connection',
            'temporarily unavailable',
        ];

        $lowerError = strtolower($error);
        foreach ($retryablePatterns as $pattern) {
            if (str_contains($lowerError, $pattern)) {
                return true;
            }
        }

        return false;
    }

    public function getChainStatus(): array
    {
        $status = [];
        foreach ($this->chain as $index => $chainItem) {
            $provider = $chainItem['provider'];
            $status[] = [
                'priority' => $chainItem['priority'],
                'provider' => $provider->getProviderName(),
                'models' => $provider->getAvailableModels(),
                'health' => $provider->getHealthStatus(),
            ];
        }

        return $status;
    }

    public function clearChain(): void
    {
        $this->chain = [];
    }

    public function getChainLength(): int
    {
        return count($this->chain);
    }
}
