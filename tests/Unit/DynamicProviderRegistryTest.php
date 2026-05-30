<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Services\AiModelsHub\DynamicProviderRegistry;
use App\Services\AiModelsHub\EncryptedApiKeyStorage;
use App\Models\AIProvider;
use App\Models\AIModel;
use App\Models\AIApiKey;

class DynamicProviderRegistryTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_gets_provider_configuration()
    {
        $provider = AIProvider::factory()->create([
            'name' => 'Test Provider',
            'base_url' => 'https://api.test.com',
            'auth_header_format' => 'Bearer {key}',
            'payload_format' => 'openai',
            'is_active' => true,
        ]);

        $apiKey = AIApiKey::factory()->create([
            'provider_id' => $provider->id,
            'key_hash' => encrypt('test-key'),
            'is_active' => true,
        ]);

        $registry = new DynamicProviderRegistry(
            $this->app->make(EncryptedApiKeyStorage::class)
        );

        $result = $registry->getProvider($provider->id);

        $this->assertNotNull($result);
        $this->assertEquals($provider->id, $result['id']);
        $this->assertEquals($provider->name, $result['name']);
        $this->assertEquals($provider->base_url, $result['base_url']);
        $this->assertEquals($provider->auth_header_format, $result['auth_header_format']);
        $this->assertEquals($provider->payload_format, $result['payload_format']);
        $this->assertNotNull($result['api_key']);
    }

    /** @test */
    public function it_returns_null_for_inactive_provider()
    {
        $provider = AIProvider::factory()->create([
            'is_active' => false,
        ]);

        $registry = new DynamicProviderRegistry(
            $this->app->make(EncryptedApiKeyStorage::class)
        );

        $result = $registry->getProvider($provider->id);

        $this->assertNull($result);
    }

    /** @test */
    public function it_registers_a_new_provider()
    {
        $registry = new DynamicProviderRegistry(
            $this->app->make(EncryptedApiKeyStorage::class)
        );

        $providerData = [
            'name' => 'New Provider',
            'base_url' => 'https://api.new.com',
            'models_fetch_endpoint' => '/models',
            'generate_endpoint' => '/generate',
            'auth_header_format' => 'Bearer {key}',
            'payload_format' => 'openai',
            'is_active' => true,
        ];

        $apiKey = 'test-api-key';

        $result = $registry->registerProvider($providerData, $apiKey);

        $this->assertNotNull($result['id']);
        $this->assertDatabaseHas('ai_providers', [
            'name' => 'New Provider',
            'base_url' => 'https://api.new.com',
        ]);

        $this->assertDatabaseHas('ai_api_keys', [
            'provider_id' => $result['id'],
            'is_active' => true,
        ]);
    }

    /** @test */
    public function it_syncs_models_for_a_provider()
    {
        $provider = AIProvider::factory()->create([
            'models_fetch_endpoint' => '/models',
            'generate_endpoint' => '/generate',
        ]);

        // Mock the HTTP response for model sync
        \Http::fake([
            // Mock the models endpoint
            '*' => \Http::response([
                'data' => [
                    [
                        'id' => 'test-model-1',
                        'name' => 'Test Model 1',
                        'context_length' => 4096,
                    ],
                    [
                        'id' => 'test-model-2',
                        'name' => 'Test Model 2',
                        'context_length' => 8192,
                    ]
                ]
            ], 200, ['Content-Type' => 'application/json'])
        ]);

        $registry = new DynamicProviderRegistry(
            $this->app->make(EncryptedApiKeyStorage::class)
        );

        $result = $registry->syncModels($provider->id);

        $this->assertTrue($result['success']);
        $this->assertEquals(2, $result['synced_count']);

        // Check that models were created in the database
        $this->assertDatabaseCount('ai_models', 2);
        $this->assertDatabaseHas('ai_models', [
            'provider_id' => $provider->id,
            'name' => 'Test Model 1',
        ]);
        $this->assertDatabaseHas('ai_models', [
            'provider_id' => $provider->id,
            'name' => 'Test Model 2',
        ]);
    }
}