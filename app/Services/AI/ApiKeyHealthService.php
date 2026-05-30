<?php

namespace App\Services\AI;

use App\Models\ApiKey;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ApiKeyHealthService
{
    protected string $cachePrefix = 'ai_key_health_';
    protected int $ttlSeconds = 300;
    protected array $healthChecks = [];

    public function checkKey(string $provider, string $apiKey): array
    {
        $cacheKey = $this->getCacheKey($provider, $apiKey);
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $startTime = microtime(true);

        try {
            $providerInstance = $this->resolveProvider($provider, $apiKey);
            $health = $providerInstance->getHealthStatus();
            $latencyMs = round((microtime(true) - $startTime) * 1000, 2);

            $result = [
                'provider' => $provider,
                'status' => $health['status'] ?? 'unknown',
                'latency_ms' => $latencyMs,
                'checked_at' => now()->toISOString(),
                'error' => $health['error'] ?? null,
            ];

            Cache::put($cacheKey, $result, $this->ttlSeconds);

            $this->updateKeyRecord($provider, $apiKey, $result);

            return $result;
        } catch (\Throwable $e) {
            $latencyMs = round((microtime(true) - $startTime) * 1000, 2);
            $result = [
                'provider' => $provider,
                'status' => 'unhealthy',
                'latency_ms' => $latencyMs,
                'error' => $e->getMessage(),
                'checked_at' => now()->toISOString(),
            ];

            Cache::put($cacheKey, $result, $this->ttlSeconds);
            $this->updateKeyRecord($provider, $apiKey, $result);

            return $result;
        }
    }

    public function checkAllKeys(array $keyMap): array
    {
        $results = [];
        foreach ($keyMap as $provider => $apiKey) {
            $results[$provider] = $this->checkKey($provider, $apiKey);
        }
        return $results;
    }

    public function getKeyHealth(string $provider, string $apiKey): array
    {
        $cacheKey = $this->getCacheKey($provider, $apiKey);
        return Cache::get($cacheKey, [
            'provider' => $provider,
            'status' => 'unknown',
            'latency_ms' => 0,
            'checked_at' => null,
        ]);
    }

    public function getUnhealthyKeys(array $keyMap): array
    {
        $unhealthy = [];
        foreach ($keyMap as $provider => $apiKey) {
            $health = $this->getKeyHealth($provider, $apiKey);
            if (($health['status'] ?? 'unknown') === 'unhealthy') {
                $unhealthy[$provider] = $health;
            }
        }
        return $unhealthy;
    }

    public function getHealthyKeys(array $keyMap): array
    {
        $healthy = [];
        foreach ($keyMap as $provider => $apiKey) {
            $health = $this->getKeyHealth($provider, $apiKey);
            if (($health['status'] ?? 'unknown') === 'healthy') {
                $healthy[$provider] = $health;
            }
        }
        return $healthy;
    }

    public function clearHealthCache(string $provider = null): void
    {
        if ($provider) {
            $pattern = $this->cachePrefix . $provider . '_*';
            foreach (Cache::getRedis()->keys($pattern) as $key) {
                Cache::forget(str_replace($this->cachePrefix, '', $key));
            }
        } else {
            foreach (['google_gemini', 'openai', 'anthropic', 'groq'] as $p) {
                $this->clearHealthCache($p);
            }
        }
    }

    protected function resolveProvider(string $provider, string $apiKey): ProviderInterface
    {
        return match ($provider) {
            'google_gemini' => new GoogleGeminiProvider($apiKey),
            'openai' => new OpenAIProvider($apiKey),
            'anthropic' => new AnthropicProvider($apiKey),
            'groq' => new GroqProvider($apiKey),
            default => throw new \InvalidArgumentException("Unknown provider: {$provider}"),
        };
    }

    protected function updateKeyRecord(string $provider, string $apiKey, array $health): void
    {
        try {
            $keyRecord = ApiKey::where('provider', $provider)
                ->where('key', $apiKey)
                ->first();

            if ($keyRecord) {
                $keyRecord->metadata = array_merge($keyRecord->metadata ?? [], [
                    'last_health_check' => $health,
                    'health_status' => $health['status'],
                    'last_checked_at' => now(),
                ]);
                $keyRecord->save();
            }
        } catch (\Throwable $e) {
            Log::warning('Failed to update key health record: ' . $e->getMessage());
        }
    }

    protected function getCacheKey(string $provider, string $apiKey): string
    {
        $keyHash = substr(md5($apiKey), 0, 12);
        return $this->cachePrefix . $provider . '_' . $keyHash;
    }
}
