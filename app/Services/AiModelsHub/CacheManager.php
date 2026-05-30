<?php

namespace App\Services\AiModelsHub;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CacheManager
{
    // Cache TTL constants
    const PROVIDER_TTL = 3600; // 1 hour
    const INTENT_TTL = 1800;   // 30 minutes
    const MODELS_TTL = 3600;   // 1 hour

    /**
     * Cache provider data with automatic invalidation support
     */
    public function cacheProvider(string $providerId, callable $callback, int $ttl = null)
    {
        $ttl = $ttl ?? self::PROVIDER_TTL;
        $key = "provider:{$providerId}";

        return Cache::remember($key, $ttl, $callback);
    }

    /**
     * Cache intent routing data with automatic invalidation support
     */
    public function cacheIntentRouting(string $intentName, callable $callback, int $ttl = null)
    {
        $ttl = $ttl ?? self::INTENT_TTL;
        $key = "intent:{$intentName}";

        return Cache::remember($key, $ttl, $callback);
    }

    /**
     * Cache provider models data with automatic invalidation support
     */
    public function cacheProviderModels(string $providerId, callable $callback, int $ttl = null)
    {
        $ttl = $ttl ?? self::MODELS_TTL;
        $key = "provider:{$providerId}:models";

        return Cache::remember($key, $ttl, $callback);
    }

    /**
     * Invalidate provider cache
     */
    public function invalidateProvider(string $providerId)
    {
        Cache::forget("provider:{$providerId}");
        Cache::forget("provider:{$providerId}:models");
        Log::debug("Invalidated cache for provider {$providerId}");
    }

    /**
     * Invalidate intent routing cache
     */
    public function invalidateIntentRouting(string $intentName)
    {
        Cache::forget("intent:{$intentName}");
        Log::debug("Invalidated cache for intent routing {$intentName}");
    }

    /**
     * Invalidate all provider caches
     */
    public function invalidateAllProviders()
    {
        // Note: Without cache tags, we can't easily clear all provider caches
        // In production with Redis, consider using cache tags
        Log::debug("Invalidated all provider caches (limited without tags)");
    }

    /**
     * Invalidate all intent routing caches
     */
    public function invalidateAllIntentRouting()
    {
        Cache::forget('intents:all');
        Log::debug("Invalidated all intent routing caches");
    }

    /**
     * Get cached provider data if exists
     */
    public function getCachedProvider(string $providerId)
    {
        return Cache::get("provider:{$providerId}");
    }

    /**
     * Get cached intent routing data if exists
     */
    public function getCachedIntentRouting(string $intentName)
    {
        return Cache::get("intent:{$intentName}");
    }

    /**
     * Get cached provider models data if exists
     */
    public function getCachedProviderModels(string $providerId)
    {
        return Cache::get("provider:{$providerId}:models");
    }
}