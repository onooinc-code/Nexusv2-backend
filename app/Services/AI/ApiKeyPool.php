<?php

namespace App\Services\AI;

use App\Models\ApiKey;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ApiKeyPool
{
    protected array $pools = [];
    protected string $cachePrefix = 'ai_key_pool_';
    protected int $ttlSeconds = 3600;

    public function registerPool(string $provider, array $keys): void
    {
        $this->pools[$provider] = [
            'keys' => $keys,
            'current_index' => 0,
            'total_keys' => count($keys),
        ];

        Cache::put("{$this->cachePrefix}{$provider}", [
            'keys' => $keys,
            'current_index' => 0,
            'total_keys' => count($keys),
        ], $this->ttlSeconds);
    }

    public function getKey(string $provider): ?string
    {
        if (!isset($this->pools[$provider])) {
            $cached = Cache::get("{$this->cachePrefix}{$provider}");
            if ($cached) {
                $this->pools[$provider] = $cached;
            } else {
                return null;
            }
        }

        $pool = &$this->pools[$provider];
        if (empty($pool['keys'])) return null;

        $key = $pool['keys'][$pool['current_index'] % $pool['total_keys']];
        $pool['current_index']++;

        Cache::put("{$this->cachePrefix}{$provider}", $pool, $this->ttlSeconds);

        return $key;
    }

    public function getKeyForProvider(string $provider): ?string
    {
        return $this->getKey($provider);
    }

    public function addKey(string $provider, string $key): void
    {
        if (!isset($this->pools[$provider])) {
            $this->pools[$provider] = [
                'keys' => [],
                'current_index' => 0,
                'total_keys' => 0,
            ];
        }

        $this->pools[$provider]['keys'][] = $key;
        $this->pools[$provider]['total_keys'] = count($this->pools[$provider]['keys']);

        Cache::put("{$this->cachePrefix}{$provider}", $this->pools[$provider], $this->ttlSeconds);
    }

    public function removeKey(string $provider, string $key): bool
    {
        if (!isset($this->pools[$provider])) return false;

        $index = array_search($key, $this->pools[$provider]['keys'], true);
        if ($index === false) return false;

        unset($this->pools[$provider]['keys'][$index]);
        $this->pools[$provider]['keys'] = array_values($this->pools[$provider]['keys']);
        $this->pools[$provider]['total_keys'] = count($this->pools[$provider]['keys']);

        if ($this->pools[$provider]['current_index'] >= $this->pools[$provider]['total_keys']) {
            $this->pools[$provider]['current_index'] = 0;
        }

        Cache::put("{$this->cachePrefix}{$provider}", $this->pools[$provider], $this->ttlSeconds);

        return true;
    }

    public function getPoolStatus(string $provider): array
    {
        if (!isset($this->pools[$provider])) {
            $cached = Cache::get("{$this->cachePrefix}{$provider}");
            if ($cached) {
                $this->pools[$provider] = $cached;
            } else {
                return [
                    'provider' => $provider,
                    'total_keys' => 0,
                    'active_keys' => 0,
                    'current_index' => 0,
                ];
            }
        }

        $pool = $this->pools[$provider];
        return [
            'provider' => $provider,
            'total_keys' => $pool['total_keys'],
            'active_keys' => $pool['total_keys'],
            'current_index' => $pool['current_index'],
        ];
    }

    public function getAllPoolStatuses(): array
    {
        $statuses = [];
        foreach (array_keys($this->pools) as $provider) {
            $statuses[] = $this->getPoolStatus($provider);
        }
        return $statuses;
    }

    public function loadFromDatabase(): void
    {
        $keys = ApiKey::where('type', 'ai_provider')
            ->where('is_active', true)
            ->get()
            ->groupBy('provider');

        foreach ($keys as $provider => $providerKeys) {
            $this->registerPool($provider, $providerKeys->pluck('key')->all());
        }

        Log::info('Loaded AI key pools from database', [
            'providers' => array_keys($this->pools),
        ]);
    }

    public function clearPool(string $provider): void
    {
        unset($this->pools[$provider]);
        Cache::forget("{$this->cachePrefix}{$provider}");
    }

    public function clearAllPools(): void
    {
        $this->pools = [];
        foreach (['google_gemini', 'openai', 'anthropic', 'groq'] as $provider) {
            Cache::forget("{$this->cachePrefix}{$provider}");
        }
    }
}
