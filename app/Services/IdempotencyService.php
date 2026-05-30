<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class IdempotencyService
{
    public function isProcessed(string $key): bool
    {
        return Cache::has($key);
    }

    public function getResult(string $key): mixed
    {
        return Cache::get($key);
    }

    public function markAsProcessed(string $key, mixed $result = null, int $ttl = 3600): void
    {
        Cache::put($key, $result, $ttl);
    }

    public function forget(string $key): void
    {
        Cache::forget($key);
    }
}
