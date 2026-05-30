<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;

/**
 * SettingCacheService
 *
 * Provides low-latency access to application settings via Redis cache.
 * Automatically invalidates cache on setting updates.
 *
 * Cache keys: "setting.{key}"
 * TTL: 1 hour (3600 seconds)
 */
class SettingCacheService
{
    /**
     * The cache TTL in seconds.
     *
     * @var int
     */
    protected int $ttl;

    /**
     * Create a new SettingCacheService instance.
     *
     * @param int|null $ttl
     * @return void
     */
    public function __construct(?int $ttl = null)
    {
        $this->ttl = $ttl ?? (int) Config::get('cache.settings_ttl', 3600);
    }

    /**
     * Get a setting value by key, with cache.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        return Cache::remember("setting.{$key}", $this->ttl, function () use ($key, $default) {
            $setting = Setting::where('key', $key)->first();
            return $setting ? $setting->getTypedValue() : $default;
        });
    }

    /**
     * Get all settings, optionally filtered by group.
     *
     * @param string|null $group
     * @return array<string, mixed>
     */
    public function getAll(?string $group = null): array
    {
        $cacheKey = $group ? "settings.group.{$group}" : 'settings.all';

        return Cache::remember($cacheKey, $this->ttl, function () use ($group) {
            $query = Setting::query();
            if ($group) {
                $query->byGroup($group);
            }
            $settings = $query->orderBy('group')->orderBy('key')->get();

            $result = [];
            foreach ($settings as $setting) {
                $result[$setting->key] = [
                    'value' => $setting->getTypedValue(),
                    'type' => $setting->type,
                    'group' => $setting->group,
                    'is_public' => $setting->is_public,
                    'description' => $setting->description,
                ];
            }

            return $result;
        });
    }

    /**
     * Get all public settings.
     *
     * @return array<string, mixed>
     */
    public function getPublic(): array
    {
        return Cache::remember('settings.public', $this->ttl, function () {
            $settings = Setting::public()->orderBy('key')->get();
            $result = [];
            foreach ($settings as $setting) {
                $result[$setting->key] = $setting->getTypedValue();
            }
            return $result;
        });
    }

    /**
     * Set a setting value and update cache.
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function set(string $key, $value): void
    {
        $setting = Setting::where('key', $key)->first();
        if ($setting) {
            $setting->update(['value' => $value]);
            $this->forget($key);
        }
    }

    /**
     * Forget a cached setting.
     *
     * @param string $key
     * @return void
     */
    public function forget(string $key): void
    {
        Cache::forget("setting.{$key}");
        Cache::forget('settings.all');
        Cache::forget('settings.public');
    }

    /**
     * Clear all settings cache.
     *
     * @return void
     */
    public function clear(): void
    {
        Cache::forget('settings.all');
        Cache::forget('settings.public');
    }

    /**
     * Check if a setting exists.
     *
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool
    {
        return Setting::where('key', $key)->exists();
    }
}
