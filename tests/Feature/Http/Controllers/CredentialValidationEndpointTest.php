<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CredentialValidationEndpointTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_validate_credential_endpoint_validates_single_setting(): void
    {
        Setting::factory()->create([
            'key' => 'integrations.openai_key',
            'value' => 'sk-test-key',
            'group' => 'integrations',
            'is_encrypted' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/settings/credentials/validate', [
                'key' => 'integrations.openai_key',
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'key',
                    'valid',
                    'status',
                    'message',
                ],
            ]);
    }

    public function test_validate_all_credentials_endpoint(): void
    {
        Setting::factory()->count(3)->create([
            'group' => 'integrations',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/settings/credentials/validate');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'valid_count',
                    'invalid_count',
                    'total',
                    'results',
                ],
            ]);
    }

    public function test_health_status_endpoint(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/settings/health');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'status',
                    'timestamp',
                    'checks' => [
                        'reverb',
                        'credentials',
                    ],
                ],
            ]);
    }

    public function test_validate_credential_with_inline_data(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/settings/credentials/validate', [
                'provider' => 'openai',
                'key' => 'sk-test-inline-key',
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data',
            ]);
    }

    public function test_unauthenticated_user_cannot_validate(): void
    {
        $response = $this->postJson('/api/v1/settings/credentials/validate', [
            'key' => 'integrations.openai_key',
        ]);

        $response->assertStatus(401);
    }

    public function test_health_check_includes_reverb_status(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/settings/health');

        $response->assertStatus(200);
        $reverb = $response->json('data.checks.reverb');
        
        $this->assertIsArray($reverb);
        $this->assertArrayHasKey('healthy', $reverb);
        $this->assertArrayHasKey('message', $reverb);
    }

    public function test_health_check_includes_credential_summary(): void
    {
        Setting::factory()->create(['group' => 'integrations']);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/settings/health');

        $response->assertStatus(200);
        $credentials = $response->json('data.checks.credentials');
        
        $this->assertIsArray($credentials);
        $this->assertArrayHasKey('valid_count', $credentials);
        $this->assertArrayHasKey('invalid_count', $credentials);
        $this->assertArrayHasKey('total', $credentials);
    }
}
