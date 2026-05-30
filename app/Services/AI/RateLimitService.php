<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class RateLimitService
{
    protected string $cachePrefix = 'ai_rate_limit_';
    protected int $defaultLimit = 60;
    protected int $defaultWindowSeconds = 60;
    protected array $providerLimits = [
        'openai' => ['limit' => 60, 'window' => 60],
        'google_gemini' => ['limit' => 60, 'window' => 60],
        'anthropic' => ['limit' => 60, 'window' => 60],
        'groq' => ['limit' => 60, 'window' => 60],
    ];

    public function check(string $provider, string $key = null): array
    {
        $limits = $this->providerLimits[$provider] ?? [
            'limit' => $this->defaultLimit,
            'window' => $this->defaultWindowSeconds,
        ];

        $cacheKey = $this->getCacheKey($provider, $key);
        $current = Cache::get($cacheKey, 0);

        if ($current >= $limits['limit']) {
            $ttl = Cache::get($cacheKey . '_ttl', 0);
            $resetAt = now()->addSeconds($ttl);

            return [
                'allowed' => false,
                'remaining' => 0,
                'limit' => $limits['limit'],
                'reset_at' => $resetAt->toISOString(),
                'retry_after_seconds' => $ttl,
            ];
        }

        $remaining = $limits['limit'] - $current - 1;
        $ttl = $limits['window'];

        Cache::put($cacheKey, $current + 1, $ttl);
        Cache::put($cacheKey . '_ttl', $ttl, $ttl);

        return [
            'allowed' => true,
            'remaining' => $remaining,
            'limit' => $limits['limit'],
            'reset_at' => now()->addSeconds($ttl)->toISOString(),
            'retry_after_seconds' => 0,
        ];
    }

    public function recordSuccess(string $provider, string $key = null): void
    {
        $cacheKey = $this->getCacheKey($provider, $key);
        $current = Cache::get($cacheKey, 0);
        Cache::put($cacheKey, max(0, $current - 1), $this->defaultWindowSeconds);
    }

    public function recordFailure(string $provider, string $key = null): void
    {
        $cacheKey = $this->getCacheKey($provider, $key);
        $current = Cache::get($cacheKey, 0);
        Cache::put($cacheKey, $current + 1, $this->defaultWindowSeconds);
    }

    public function reset(string $provider, string $key = null): void
    {
        $cacheKey = $this->getCacheKey($provider, $key);
        Cache::forget($cacheKey);
        Cache::forget($cacheKey . '_ttl');
    }

    public function getStatus(string $provider, string $key = null): array
    {
        $limits = $this->providerLimits[$provider] ?? [
            'limit' => $this->defaultLimit,
            'window' => $this->defaultWindowSeconds,
        ];

        $cacheKey = $this->getCacheKey($provider, $key);
        $current = Cache::get($cacheKey, 0);
        $ttl = Cache::get($cacheKey . '_ttl', $limits['window']);

        return [
            'provider' => $provider,
            'limit' => $limits['limit'],
            'used' => $current,
            'remaining' => max(0, $limits['limit'] - $current),
            'reset_at' => now()->addSeconds($ttl)->toISOString(),
            'window_seconds' => $limits['window'],
        ];
    }

    public function getAllStatuses(): array
    {
        $statuses = [];
        foreach (array_keys($this->providerLimits) as $provider) {
            $statuses[$provider] = $this->getStatus($provider);
        }
        return $statuses;
    }

    public function setProviderLimit(string $provider, int $limit, int $windowSeconds = 60): void
    {
        $this->providerLimits[$provider] = [
            'limit' => $limit,
            'window' => $windowSeconds,
        ];
    }

    protected function getCacheKey(string $provider, ?string $key): string
    {
        $keyPart = $key ? substr($key, -8) : 'default';
        return $this->cachePrefix . $provider . '_' . $keyPart;
    }

    public function waitIfNeeded(string $provider, string $key = null): void
    {
        $status = $this->check($provider, $key);
        if (!$status['allowed']) {
            sleep($status['retry_after_seconds']);
        }
    }
}
