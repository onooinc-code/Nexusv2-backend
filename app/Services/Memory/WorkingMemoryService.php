<?php

namespace App\Services\Memory;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class WorkingMemoryService
{
    /**
     * Store data in working memory (Redis)
     *
     * @param string $key
     * @param mixed $value
     * @param float|int $ttl Time to live in seconds (null for default)
     * @return bool
     */
    public function store(string $key, $value, $ttl = null): bool
    {
        try {
            $ttl = $ttl ?? config('cache.ttl', 60); // Default TTL from config or 60 seconds
            Cache::put($key, $value, now()->addSeconds($ttl));
            return true;
        } catch (\Exception $e) {
            Log::error('WorkingMemoryService::store failed', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Retrieve data from working memory
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        try {
            return Cache::get($key, $default);
        } catch (\Exception $e) {
            Log::error('WorkingMemoryService::get failed', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);
            return $default;
        }
    }

    /**
     * Update data in working memory
     *
     * @param string $key
     * @param mixed $value
     * @param float|int $ttl Time to live in seconds (null to keep existing TTL)
     * @return bool
     */
    public function update(string $key, $value, $ttl = null): bool
    {
        try {
            // If TTL is null, we try to get the existing TTL (not directly supported by Cache facade)
            // For simplicity, we'll just store with default TTL if not provided
            if ($ttl === null) {
                $ttl = config('cache.ttl', 60);
            }
            Cache::put($key, $value, now()->addSeconds($ttl));
            return true;
        } catch (\Exception $e) {
            Log::error('WorkingMemoryService::update failed', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Delete data from working memory
     *
     * @param string $key
     * @return bool
     */
    public function delete(string $key): bool
    {
        try {
            return Cache::forget($key);
        } catch (\Exception $e) {
            Log::error('WorkingMemoryService::delete failed', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Check if a key exists in working memory
     *
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool
    {
        try {
            return Cache::has($key);
        } catch (\Exception $e) {
            Log::error('WorkingMemoryService::has failed', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Increment a value in working memory
     *
     * @param string $key
     * @param int $step
     * @return int|false
     */
    public function increment(string $key, int $step = 1)
    {
        try {
            return Cache::increment($key, $step);
        } catch (\Exception $e) {
            Log::error('WorkingMemoryService::increment failed', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Decrement a value in working memory
     *
     * @param string $key
     * @param int $step
     * @return int|false
     */
    public function decrement(string $key, int $step = 1)
    {
        try {
            return Cache::decrement($key, $step);
        } catch (\Exception $e) {
            Log::error('WorkingMemoryService::decrement failed', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get multiple keys from working memory
     *
     * @param array $keys
     * @param mixed $default
     * @return array
     */
    public function getMany(array $keys, $default = null): array
    {
        try {
            return Cache::many($keys, $default);
        } catch (\Exception $e) {
            Log::error('WorkingMemoryService::getMany failed', [
                'keys' => $keys,
                'error' => $e->getMessage()
            ]);
            return array_fill_keys($keys, $default);
        }
    }

    /**
     * Store multiple keys in working memory
     *
     * @param array $data
     * @param float|int $ttl
     * @return bool
     */
    public function putMany(array $data, $ttl = null): bool
    {
        try {
            $ttl = $ttl ?? config('cache.ttl', 60);
            $expiration = now()->addSeconds($ttl);
            foreach ($data as $key => $value) {
                Cache::put($key, $value, $expiration);
            }
            return true;
        } catch (\Exception $e) {
            Log::error('WorkingMemoryService::putMany failed', [
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Delete multiple keys from working memory
     *
     * @param array $keys
     * @return bool
     */
    public function forgetMany(array $keys): bool
    {
        try {
            foreach ($keys as $key) {
                Cache::forget($key);
            }
            return true;
        } catch (\Exception $e) {
            Log::error('WorkingMemoryService::forgetMany failed', [
                'keys' => $keys,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}