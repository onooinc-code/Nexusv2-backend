<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Crypt;

/**
 * CredentialEncryptionService
 *
 * Handles encryption and decryption of sensitive settings (credentials).
 */
class CredentialEncryptionService
{
    /**
     * Encrypt a credential value.
     */
    public function encrypt(string $value): string
    {
        return Crypt::encryptString($value);
    }

    /**
     * Decrypt a credential value.
     */
    public function decrypt(string $encryptedValue): string
    {
        try {
            return Crypt::decryptString($encryptedValue);
        } catch (\Exception $e) {
            \Log::error('Failed to decrypt credential', ['error' => $e->getMessage()]);
            return '';
        }
    }

    /**
     * Mask a value for display (show first 4 and last 4 chars, rest as ****).
     */
    public function mask(string $value): string
    {
        $length = strlen($value);
        if ($length <= 8) {
            return '****';
        }
        return substr($value, 0, 4) . str_repeat('*', $length - 8) . substr($value, -4);
    }

    /**
     * Encrypt setting if it contains sensitive credentials.
     */
    public function encryptIfNeeded(Setting $setting): void
    {
        if ($this->shouldEncrypt($setting->key)) {
            if (!$setting->is_encrypted && $setting->value) {
                $setting->update([
                    'value' => $this->encrypt($setting->value),
                    'is_encrypted' => true,
                ]);
            }
        }
    }

    /**
     * Decrypt setting if encrypted.
     */
    public function decryptIfNeeded(Setting $setting): void
    {
        if ($setting->is_encrypted && $setting->value) {
            $decrypted = $this->decrypt($setting->value);
            $setting->setAttribute('value', $decrypted);
        }
    }

    /**
     * Determine if a setting key should be encrypted.
     */
    public function shouldEncrypt(string $key): bool
    {
        $encryptedKeys = [
            'integrations.pinecone_api_key',
            'integrations.neo4j_password',
            'integrations.waha_api_key',
            'integrations.openai_api_key',
            'integrations.gemini_api_key',
            'integrations.anthropic_api_key',
            'integrations.groq_api_key',
            'integrations.stripe_secret_key',
        ];

        foreach ($encryptedKeys as $encryptedKey) {
            if (str_contains($key, $encryptedKey) || str_starts_with($key, 'integrations.') && str_contains($key, 'key')) {
                return true;
            }
        }

        return false;
    }
}
