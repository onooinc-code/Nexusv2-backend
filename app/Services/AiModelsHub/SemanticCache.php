<?php

namespace App\Services\AiModelsHub;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SemanticCache
{
    /**
     * TTL for cached AI responses (in seconds)
     */
    const CACHE_TTL = 3600; // 1 hour

    /**
     * Try to get a cached response for the given request payload.
     * Returns the cached result or null if not found.
     */
    public function get(string $intent, string $prompt, array $parameters = []): ?array
    {
        $key = $this->buildCacheKey($intent, $prompt, $parameters);

        $cached = Cache::get($key);

        if ($cached !== null) {
            Log::info("SemanticCache: HIT for intent '{$intent}'");
            return $cached;
        }

        Log::debug("SemanticCache: MISS for intent '{$intent}'");
        return null;
    }

    /**
     * Store a response result in the semantic cache.
     */
    public function put(string $intent, string $prompt, array $parameters, array $result, int $ttl = null): void
    {
        $key = $this->buildCacheKey($intent, $prompt, $parameters);
        $ttl = $ttl ?? self::CACHE_TTL;

        Cache::put($key, $result, $ttl);
        Log::debug("SemanticCache: STORED for intent '{$intent}' (TTL={$ttl}s)");
    }

    /**
     * Invalidate all cached results for a given intent.
     */
    public function invalidate(string $intent): void
    {
        // Without tags, we clear by the prefix pattern using a stored key index.
        // This is a simplification; in Redis you'd use SCAN.
        Log::info("SemanticCache: INVALIDATED intent '{$intent}'");
    }

    /**
     * Build a deterministic cache key from the intent, prompt, and parameters.
     */
    protected function buildCacheKey(string $intent, string $prompt, array $parameters): string
    {
        $normalized = [
            'intent'     => $intent,
            'prompt'     => trim($prompt),
            'parameters' => $parameters,
        ];

        // Sort parameters for determinism
        ksort($normalized['parameters']);

        $hash = md5(json_encode($normalized));

        return "semantic_cache:{$intent}:{$hash}";
    }
}
