<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\AIProvider;
use App\Models\AIModel;
use App\Models\AIApiKey;
use App\Models\User;

class AiProviderTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        // Create and authenticate a user for all tests (routes are auth:sanctum protected)
        $this->user = User::factory()->create();
        $this->actingAs($this->user, 'sanctum');
    }

    /** @test */
    public function it_creates_a_new_provider()
    {
        $response = $this->postJson('/api/v1/ai/providers', [
            'name'                  => 'Test Provider',
            'base_url'              => 'https://api.test.com',
            'models_fetch_endpoint' => '/models',
            'generate_endpoint'     => '/generate',
            'auth_header_format'    => 'Bearer {key}',
            'payload_format'        => 'openai',
            'is_active'             => true,
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'id',
                'name',
                'base_url',
                'models_fetch_endpoint',
                'generate_endpoint',
                'auth_header_format',
                'payload_format',
                'is_active',
                'created_at',
                'updated_at',
            ],
            'message',
        ]);

        $this->assertDatabaseHas('ai_providers', [
            'name'     => 'Test Provider',
            'base_url' => 'https://api.test.com',
        ]);
    }

    /** @test */
    public function it_lists_all_providers()
    {
        AIProvider::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/ai/providers');

        $response->assertStatus(200);
        $response->assertJsonCount(3, 'data');
    }

    /** @test */
    public function it_gets_a_specific_provider()
    {
        $provider = AIProvider::factory()->create();

        $response = $this->getJson("/api/v1/ai/providers/{$provider->id}");

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'data'    => [
                'id'   => $provider->id,
                'name' => $provider->name,
            ],
        ]);
    }

    /** @test */
    public function it_updates_a_provider()
    {
        $provider = AIProvider::factory()->create([
            'name'      => 'Original Name',
            'is_active' => true,
        ]);

        $response = $this->putJson("/api/v1/ai/providers/{$provider->id}", [
            'name'      => 'Updated Name',
            'base_url'  => $provider->base_url, // base_url still required by validator
            'is_active' => false,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'data'    => [
                'name'      => 'Updated Name',
                'is_active' => false,
            ],
        ]);

        $this->assertDatabaseHas('ai_providers', [
            'id'        => $provider->id,
            'name'      => 'Updated Name',
            'is_active' => false,
        ]);
    }

    /** @test */
    public function it_deletes_a_provider()
    {
        $provider = AIProvider::factory()->create();

        $response = $this->deleteJson("/api/v1/ai/providers/{$provider->id}");

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'AI provider deleted successfully',
        ]);
        $this->assertDatabaseMissing('ai_providers', ['id' => $provider->id]);
    }

    /** @test */
    public function it_syncs_models_for_a_provider()
    {
        $provider = AIProvider::factory()->create([
            'models_fetch_endpoint' => '/models',
            'generate_endpoint'     => '/generate',
        ]);

        // Mock HTTP response from external provider API
        \Illuminate\Support\Facades\Http::fake([
            '*' => \Illuminate\Support\Facades\Http::response([
                'data' => [
                    [
                        'id'             => 'gpt-4',
                        'name'           => 'GPT-4',
                        'context_length' => 8192,
                    ],
                    [
                        'id'             => 'gpt-3.5-turbo',
                        'name'           => 'GPT-3.5 Turbo',
                        'context_length' => 4096,
                    ],
                ],
            ], 200, ['Content-Type' => 'application/json']),
        ]);

        $response = $this->postJson("/api/v1/ai/providers/{$provider->id}/sync-models");

        $response->assertStatus(200);
        $response->assertJson([
            'message'      => 'Models synchronized successfully',
            'synced_count' => 2,
        ]);

        $this->assertDatabaseCount('ai_models', 2);
        $this->assertDatabaseHas('ai_models', [
            'provider_id' => $provider->id,
            'name'        => 'gpt-4',
        ]);
        $this->assertDatabaseHas('ai_models', [
            'provider_id' => $provider->id,
            'name'        => 'gpt-3.5-turbo',
        ]);
    }
}