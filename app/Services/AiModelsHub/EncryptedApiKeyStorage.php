<?php

namespace App\Services\AiModelsHub;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use App\Models\AIApiKey;

class EncryptedApiKeyStorage
{
    /**
     * Store an encrypted API key
     */
    public function storeKey($providerId, $key, $name = null)
    {
        // Encrypt the API key using Laravel's encrypter (AES-256-CBC)
        $encryptedKey = Crypt::encryptString($key);

        // Use firstOrNew to avoid overwriting the PK on update
        $apiKey = AIApiKey::where('provider_id', $providerId)->first();

        if ($apiKey) {
            $apiKey->update([
                'key_hash'  => $encryptedKey,
                'name'      => $name ?? $apiKey->name,
                'is_active' => true,
            ]);
        } else {
            $apiKey = AIApiKey::create([
                'id'          => \Illuminate\Support\Str::uuid(),
                'provider_id' => $providerId,
                'key_hash'    => $encryptedKey,
                'name'        => $name ?? "API Key for Provider {$providerId}",
                'is_active'   => true,
            ]);
        }

        return $apiKey;
    }

    /**
     * Get decrypted API key by provider ID
     */
    public function getDecryptedKey($providerId)
    {
        $apiKey = AIApiKey::where('provider_id', $providerId)
            ->where('is_active', true)
            ->first();

        if (!$apiKey) {
            return null;
        }

        try {
            // Decrypt the key
            return Crypt::decryptString($apiKey->key_hash);
        } catch (\Exception $e) {
            Log::error("Failed to decrypt API key for provider {$providerId}: {$e->getMessage()}");
            return null;
        }
    }

    /**
     * Check if API key exists for provider
     */
    public function hasKey($providerId)
    {
        return AIApiKey::where('provider_id', $providerId)
            ->where('is_active', true)
            ->exists();
    }

    /**
     * Update API key
     */
    public function updateKey($providerId, $key, $name = null)
    {
        $encryptedKey = Crypt::encryptString($key);

        $apiKey = AIApiKey::where('provider_id', $providerId)
            ->where('is_active', true)
            ->first();

        if ($apiKey) {
            $apiKey->update([
                'key_hash' => $encryptedKey,
                'name' => $name ?? $apiKey->name,
                'updated_at' => now(),
            ]);
        } else {
            $apiKey = $this->storeKey($providerId, $key, $name);
        }

        return $apiKey;
    }

    /**
     * Deactivate API key
     */
    public function deactivateKey($providerId)
    {
        $apiKey = AIApiKey::where('provider_id', $providerId)
            ->where('is_active', true)
            ->first();

        if ($apiKey) {
            $apiKey->update(['is_active' => false]);
            return true;
        }

        return false;
    }
}