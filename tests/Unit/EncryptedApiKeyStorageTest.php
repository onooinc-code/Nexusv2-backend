<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Services\AiModelsHub\EncryptedApiKeyStorage;
use App\Models\AIProvider;
use App\Models\AIApiKey;
use Illuminate\Support\Facades\Crypt;

class EncryptedApiKeyStorageTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_stores_and_retrieves_encrypted_api_key()
    {
        $provider = AIProvider::factory()->create();
        $storage = new EncryptedApiKeyStorage();
        $apiKey = 'sk-test-1234567890abcdef';

        $storedKey = $storage->storeKey($provider->id, $apiKey, 'Test Key');

        $this->assertNotNull($storedKey->id);
        $this->assertEquals($provider->id, $storedKey->provider_id);
        $this->assertEquals('Test Key', $storedKey->name);
        $this->assertTrue($storedKey->is_active);

        // Verify the key is encrypted in the database
        $this->assertNotEquals($apiKey, $storedKey->key_hash);
        $this->assertStringNotContains('sk-test', $storedKey->key_hash);

        // Verify we can decrypt and get the original key
        $retrievedKey = $storage->getDecryptedKey($provider->id);
        $this->assertEquals($apiKey, $retrievedKey);
    }

    /** @test */
    public function it_returns_null_for_nonexistent_provider_key()
    {
        $storage = new EncryptedApiKeyStorage();

        $key = $storage->getDecryptedKey('nonexistent-provider-id');

        $this->assertNull($key);
    }

    /** @test */
    public function it_checks_if_key_exists_for_provider()
    {
        $provider = AIProvider::factory()->create();
        $storage = new EncryptedApiKeyStorage();

        // Initially no key should exist
        $this->assertFalse($storage->hasKey($provider->id));

        // After storing a key, it should exist
        $storage->storeKey($provider->id, 'test-key');
        $this->assertTrue($storage->hasKey($provider->id));
    }

    /** @test */
    public function it_updates_existing_api_key()
    {
        $provider = AIProvider::factory()->create();
        $storage = new EncryptedApiKeyStorage();

        // Store initial key
        $storage->storeKey($provider->id, 'old-key', 'Original Key');

        // Update the key
        $updatedKey = $storage->updateKey($provider->id, 'new-key', 'Updated Key');

        $this->assertEquals('Updated Key', $updatedKey->name);
        $this->assertEquals('new-key', $storage->getDecryptedKey($provider->id));

        // Verify only one key record exists
        $this->assertDatabaseCount('ai_api_keys', 1);
    }

    /** @test */
    public function it_deactivates_api_key()
    {
        $provider = AIProvider::factory()->create();
        $storage = new EncryptedApiKeyStorage();

        // Store a key
        $storage->storeKey($provider->id, 'test-key');

        // Verify key exists and is active
        $this->assertTrue($storage->hasKey($provider->id));
        $this->assertNotNull($storage->getDecryptedKey($provider->id));

        // Deactivate the key
        $result = $storage->deactivateKey($provider->id);

        $this->assertTrue($result);
        $this->assertFalse($storage->hasKey($provider->id));
        $this->assertNull($storage->getDecryptedKey($provider->id));

        // Verify the record exists but is inactive
        $this->assertDatabaseHas('ai_api_keys', [
            'provider_id' => $provider->id,
            'is_active' => false,
        ]);
    }
}