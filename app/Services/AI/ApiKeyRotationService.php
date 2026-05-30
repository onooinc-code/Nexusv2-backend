<?php

namespace App\Services\AI;

use App\Models\ApiKey;
use Illuminate\Support\Facades\Log;

class ApiKeyRotationService
{
    protected ApiKeyPool $keyPool;
    protected int $rotationCheckIntervalHours = 24;

    public function __construct(ApiKeyPool $keyPool)
    {
        $this->keyPool = $keyPool;
    }

    public function checkExpirations(): array
    {
        $expired = ApiKey::where('type', 'ai_provider')
            ->where('is_active', true)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->get();

        $rotated = [];
        foreach ($expired as $key) {
            $rotated[] = $this->rotateKey($key);
        }

        return $rotated;
    }

    public function rotateKey(ApiKey $key): array
    {
        $oldKey = $key->key;
        $provider = $key->provider;

        $key->is_active = false;
        $key->save();

        $this->keyPool->removeKey($provider, $oldKey);

        Log::info("API key rotated for provider: {$provider}", [
            'key_id' => $key->id,
            'old_key' => substr($oldKey, 0, 8) . '...',
        ]);

        return [
            'success' => true,
            'key_id' => $key->id,
            'provider' => $provider,
            'old_key_prefix' => substr($oldKey, 0, 8) . '...',
            'message' => 'Key deactivated. Replace with new key in database.',
        ];
    }

    public function scheduleRotation(string $provider, \DateTimeInterface $expiresAt): array
    {
        $key = ApiKey::where('provider', $provider)
            ->where('type', 'ai_provider')
            ->where('is_active', true)
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$key) {
            return [
                'success' => false,
                'error' => "No active key found for provider: {$provider}",
            ];
        }

        $key->expires_at = $expiresAt;
        $key->save();

        return [
            'success' => true,
            'key_id' => $key->id,
            'provider' => $provider,
            'expires_at' => $expiresAt->format('Y-m-d H:i:s'),
        ];
    }

    public function getRotationSchedule(): array
    {
        $keys = ApiKey::where('type', 'ai_provider')
            ->where('is_active', true)
            ->whereNotNull('expires_at')
            ->orderBy('expires_at')
            ->get();

        return $keys->map(function ($key) {
            $daysUntilExpiry = now()->diffInDays($key->expires_at, false);
            return [
                'key_id' => $key->id,
                'name' => $key->name,
                'provider' => $key->provider,
                'expires_at' => $key->expires_at->toISOString(),
                'days_until_expiry' => $daysUntilExpiry,
                'status' => $daysUntilExpiry < 7 ? 'urgent' : ($daysUntilExpiry < 30 ? 'warning' : 'ok'),
            ];
        })->all();
    }

    public function bulkRotateExpired(): array
    {
        $expiredKeys = ApiKey::where('type', 'ai_provider')
            ->where('is_active', true)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->get();

        $results = [
            'total' => $expiredKeys->count(),
            'rotated' => 0,
            'failed' => 0,
            'details' => [],
        ];

        foreach ($expiredKeys as $key) {
            try {
                $result = $this->rotateKey($key);
                $results['rotated']++;
                $results['details'][] = $result;
            } catch (\Throwable $e) {
                $results['failed']++;
                $results['details'][] = [
                    'success' => false,
                    'key_id' => $key->id,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }
}
