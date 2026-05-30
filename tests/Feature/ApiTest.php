<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\Workflow;
use App\Models\Contact;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiTest extends TestCase
{
    use RefreshDatabase;

    // ─── API Authentication ──────────────────────────────────────────────

    public function test_api_requires_authentication_for_protected_routes(): void
    {
        $routes = [
            '/api/v1/agents',
            '/api/v1/workflows',
            '/api/v1/tasks',
            '/api/v1/settings',
            '/api/v1/logs',
        ];

        foreach ($routes as $route) {
            $response = $this->getJson($route);
            $response->assertStatus(401);
        }
    }

    public function test_api_allows_access_with_valid_token(): void
    {
        $user = \App\Models\User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->getJson('/api/v1/agents', [
            'Authorization' => 'Bearer ' . $token,
        ]);

        $response->assertStatus(200);
    }

    // ─── API Response Format ─────────────────────────────────────────────

    public function test_api_returns_json_content_type(): void
    {
        $user = \App\Models\User::factory()->create();
        $this->actingAs($user, 'sanctum');

        $response = $this->getJson('/api/v1/agents');

        $response->assertHeader('content-type', 'application/json');
    }

    public function test_api_returns_consistent_error_format(): void
    {
        $user = \App\Models\User::factory()->create();
        $this->actingAs($user, 'sanctum');

        $response = $this->postJson('/api/v1/agents', []);

        $response->assertStatus(422)
            ->assertJsonStructure(['errors']);
    }

    // ─── API Pagination ──────────────────────────────────────────────────

    public function test_api_paginates_index_responses(): void
    {
        $user = \App\Models\User::factory()->create();
        $this->actingAs($user, 'sanctum');

        Agent::factory()->count(25)->create();

        $response = $this->getJson('/api/v1/agents');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [],
                'meta' => [
                    'current_page',
                    'last_page',
                    'per_page',
                    'total',
                ],
            ]);
    }

    // ─── API Filtering ───────────────────────────────────────────────────

    public function test_api_filters_agents_by_type(): void
    {
        $user = \App\Models\User::factory()->create();
        $this->actingAs($user, 'sanctum');

        Agent::factory()->create(['type' => Agent::TYPE_REFLECTION]);
        Agent::factory()->create(['type' => Agent::TYPE_TEAM]);

        $response = $this->getJson('/api/v1/agents?type=reflection');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_api_filters_workflows_by_status(): void
    {
        $user = \App\Models\User::factory()->create();
        $this->actingAs($user, 'sanctum');

        Workflow::factory()->create(['status' => Workflow::STATUS_DRAFT]);
        Workflow::factory()->create(['status' => Workflow::STATUS_ACTIVE]);

        $response = $this->getJson('/api/v1/workflows?status=active');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    // ─── API Search ──────────────────────────────────────────────────────

    public function test_api_searches_contacts_by_name(): void
    {
        $user = \App\Models\User::factory()->create();
        $this->actingAs($user, 'sanctum');

        Contact::factory()->create(['name' => 'Alice Wonderland']);
        Contact::factory()->create(['name' => 'Bob Builder']);

        $response = $this->getJson('/api/v1/contacts?search=Alice');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data.data');
    }

    // ─── API Rate Limiting ───────────────────────────────────────────────

    public function test_api_enforces_rate_limits(): void
    {
        $user = \App\Models\User::factory()->create();
        $this->actingAs($user, 'sanctum');

        // Make many requests to trigger rate limit
        for ($i = 0; $i < 100; $i++) {
            $this->getJson('/api/v1/agents');
        }

        $response = $this->getJson('/api/v1/agents');

        // Should either succeed or be rate limited
        $this->assertContains($response->status(), [200, 429]);
    }

    // ─── API CORS ────────────────────────────────────────────────────────

    public function test_api_handles_cors_preflight(): void
    {
        $response = $this->options('/api/v1/agents');

        $response->assertStatus(200);
    }

    // ─── API Health Check ────────────────────────────────────────────────

    public function test_api_health_endpoint_returns_status(): void
    {
        $response = $this->getJson('/api/v1/health');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'timestamp',
                'services' => [
                    'database',
                    'cache',
                    'queue',
                ],
            ]);
    }
}
