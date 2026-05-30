<?php

namespace Tests\Feature\Http;

use App\Models\Agent;
use App\Models\AgentPersona;
use App\Models\AgentRuntimeLog;
use App\Models\User;
use App\Services\AgentExecutionService;
use App\Services\AgentSimulationService;
use App\Services\AgentQuarantineService;
use App\Services\AgentRateLimiter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AgentsHubTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Agent $agent;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->actingAs($this->user, 'sanctum');

        $persona = AgentPersona::create([
            'id'            => Str::uuid(),
            'name'          => 'Test Persona',
            'system_prompt' => 'You are a test AI agent.',
            'tone_preferences' => ['formality' => 'casual'],
        ]);

        $this->agent = Agent::create([
            'name'                => 'Test Agent',
            'key'                 => 'test_agent_hub',
            'description'         => 'Used for AgentsHub feature tests.',
            'type'                => Agent::TYPE_AUTONOMOUS,
            'status'              => Agent::STATUS_ACTIVE,
            'is_active'           => true,
            'is_system'           => false,
            'owner_id'            => $this->user->id,
            'persona_id'          => $persona->id,
            'rate_limit_per_minute' => 60,
        ]);
    }

    // ─────────────────────────────────────────────
    // INDEX
    // ─────────────────────────────────────────────

    public function test_index_returns_paginated_agent_list(): void
    {
        Agent::create(['name' => 'Second Agent', 'key' => 'second_agent', 'type' => Agent::TYPE_SPECIALIZED]);

        $response = $this->getJson('/api/v1/agents');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data'         => [['id', 'name', 'key', 'type', 'status', 'is_active']],
                'current_page',
                'total',
            ]);
    }

    public function test_index_filters_by_status(): void
    {
        Agent::create([
            'name' => 'Quarantined Agent', 'key' => 'q_agent',
            'type' => Agent::TYPE_AUTONOMOUS, 'status' => Agent::STATUS_QUARANTINED,
        ]);

        $response = $this->getJson('/api/v1/agents?status=quarantined');

        $response->assertStatus(200);
        $data = $response->json('data');
        foreach ($data as $item) {
            $this->assertEquals('quarantined', $item['status']);
        }
    }

    public function test_index_filters_by_type(): void
    {
        Agent::create(['name' => 'Reflection Bot', 'key' => 'reflect_bot', 'type' => Agent::TYPE_REFLECTION]);

        $response = $this->getJson('/api/v1/agents?type=autonomous');

        $response->assertStatus(200);
        $data = $response->json('data');
        foreach ($data as $item) {
            $this->assertEquals('autonomous', $item['type']);
        }
    }

    public function test_index_searches_by_name(): void
    {
        $response = $this->getJson('/api/v1/agents?search=Test+Agent');

        $response->assertStatus(200);
        $names = collect($response->json('data'))->pluck('name')->toArray();
        $this->assertContains('Test Agent', $names);
    }

    // ─────────────────────────────────────────────
    // STORE
    // ─────────────────────────────────────────────

    public function test_store_creates_agent_with_valid_payload(): void
    {
        $response = $this->postJson('/api/v1/agents', [
            'name'        => 'Brand New Agent',
            'key'         => 'brand_new_agent',
            'type'        => Agent::TYPE_SPECIALIZED,
            'description' => 'Handles post-deployment tasks.',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'Brand New Agent')
            ->assertJsonPath('data.type', 'specialized');

        $this->assertDatabaseHas('agents', ['key' => 'brand_new_agent']);
    }

    public function test_store_rejects_missing_required_fields(): void
    {
        $response = $this->postJson('/api/v1/agents', ['description' => 'No name or type']);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'key', 'type']);
    }

    public function test_store_rejects_duplicate_key(): void
    {
        $this->postJson('/api/v1/agents', [
            'name' => 'Duplicate A', 'key' => 'dup_key', 'type' => Agent::TYPE_AUTONOMOUS,
        ]);

        $response = $this->postJson('/api/v1/agents', [
            'name' => 'Duplicate B', 'key' => 'dup_key', 'type' => Agent::TYPE_REFLECTION,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['key']);
    }

    public function test_store_rejects_invalid_type(): void
    {
        $response = $this->postJson('/api/v1/agents', [
            'name' => 'Bad Type', 'key' => 'bad_type', 'type' => 'hacker',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['type']);
    }

    // ─────────────────────────────────────────────
    // SHOW
    // ─────────────────────────────────────────────

    public function test_show_returns_agent_with_relations(): void
    {
        $response = $this->getJson("/api/v1/agents/{$this->agent->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $this->agent->id)
            ->assertJsonStructure(['data' => ['id', 'name', 'key', 'type', 'status', 'persona', 'tools', 'skills', 'mcp_servers']]);
    }

    public function test_show_returns_404_for_missing_agent(): void
    {
        $response = $this->getJson('/api/v1/agents/9999999');
        $response->assertStatus(404);
    }

    // ─────────────────────────────────────────────
    // UPDATE
    // ─────────────────────────────────────────────

    public function test_update_modifies_agent_fields(): void
    {
        $response = $this->putJson("/api/v1/agents/{$this->agent->id}", [
            'description' => 'Updated description',
            'is_active'   => false,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.is_active', false);

        $this->assertDatabaseHas('agents', [
            'id'          => $this->agent->id,
            'description' => 'Updated description',
        ]);
    }

    public function test_update_rate_limit_per_minute(): void
    {
        $response = $this->putJson("/api/v1/agents/{$this->agent->id}", [
            'rate_limit_per_minute' => 30,
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('agents', [
            'id'                    => $this->agent->id,
            'rate_limit_per_minute' => 30,
        ]);
    }

    // ─────────────────────────────────────────────
    // DESTROY
    // ─────────────────────────────────────────────

    public function test_destroy_deactivates_non_system_agent(): void
    {
        $response = $this->deleteJson("/api/v1/agents/{$this->agent->id}");

        $response->assertStatus(200)
            ->assertJsonPath('message', 'Agent deactivated successfully');

        $this->assertDatabaseHas('agents', ['id' => $this->agent->id, 'is_active' => false]);
    }

    public function test_destroy_blocks_system_agents(): void
    {
        $systemAgent = Agent::create([
            'name'      => 'Core System Agent',
            'key'       => 'core_system',
            'type'      => Agent::TYPE_SUPERVISOR,
            'is_system' => true,
        ]);

        $response = $this->deleteJson("/api/v1/agents/{$systemAgent->id}");
        $response->assertStatus(403);
    }

    // ─────────────────────────────────────────────
    // STATUS
    // ─────────────────────────────────────────────

    public function test_get_status_returns_agent_health_metrics(): void
    {
        $response = $this->getJson("/api/v1/agents/{$this->agent->id}/status");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id', 'name', 'type', 'status', 'is_active',
                    'is_quarantined', 'has_error', 'success_rate',
                    'execution_count', 'success_count', 'error_count',
                    'last_executed_at', 'rate_limit',
                ],
            ]);
    }

    public function test_status_reflects_execution_count(): void
    {
        $this->agent->increment('execution_count', 5);
        $this->agent->increment('success_count', 4);

        $response = $this->getJson("/api/v1/agents/{$this->agent->id}/status");

        $response->assertStatus(200)
            ->assertJsonPath('data.execution_count', 5)
            ->assertJsonPath('data.success_count', 4)
            ->assertJsonPath('data.success_rate', 80.0);
    }

    // ─────────────────────────────────────────────
    // QUARANTINE & UNQUARANTINE
    // ─────────────────────────────────────────────

    public function test_quarantine_sets_agent_status(): void
    {
        $response = $this->postJson("/api/v1/agents/{$this->agent->id}/quarantine", [
            'reason' => 'Exceeded error threshold during load test.',
        ]);

        $response->assertStatus(200)->assertJsonPath('success', true);
        $this->assertDatabaseHas('agents', ['id' => $this->agent->id, 'status' => Agent::STATUS_QUARANTINED]);
    }

    public function test_run_returns_error_for_quarantined_agent(): void
    {
        $this->agent->quarantine();

        $response = $this->postJson("/api/v1/agents/{$this->agent->id}/run", [
            'input' => 'Process this task.',
        ]);

        $response->assertStatus(400)
            ->assertJsonPath('success', false);
    }

    public function test_unquarantine_restores_agent_to_active(): void
    {
        $this->agent->quarantine();
        $this->assertDatabaseHas('agents', ['id' => $this->agent->id, 'status' => Agent::STATUS_QUARANTINED]);

        $response = $this->postJson("/api/v1/agents/{$this->agent->id}/unquarantine");

        $response->assertStatus(200)->assertJsonPath('success', true);
        $this->assertDatabaseHas('agents', ['id' => $this->agent->id, 'status' => Agent::STATUS_ACTIVE]);
    }

    // ─────────────────────────────────────────────
    // SIMULATE
    // ─────────────────────────────────────────────

    public function test_simulate_returns_thought_process(): void
    {
        $response = $this->postJson("/api/v1/agents/{$this->agent->id}/simulate", [
            'input'      => ['task' => 'Analyze this dataset.'],
            'mock_tools' => [],
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('mode', 'simulation')
            ->assertJsonStructure([
                'success', 'mode', 'trace_id', 'duration_ms',
                'agent' => ['id', 'name', 'type'],
                'thought_process',
            ]);
    }

    public function test_simulate_logs_runtime_trace(): void
    {
        $this->postJson("/api/v1/agents/{$this->agent->id}/simulate", [
            'input' => 'Test input payload.',
        ]);

        $this->assertDatabaseHas('agent_runtime_logs', [
            'agent_id' => $this->agent->id,
            'step'     => 'simulation',
        ]);
    }

    public function test_simulate_is_blocked_for_quarantined_agent(): void
    {
        $this->agent->quarantine();

        $response = $this->postJson("/api/v1/agents/{$this->agent->id}/simulate", [
            'input' => 'Should be blocked.',
        ]);

        $response->assertStatus(400)->assertJsonPath('success', false);
    }

    public function test_simulate_requires_input_field(): void
    {
        $response = $this->postJson("/api/v1/agents/{$this->agent->id}/simulate", []);
        $response->assertStatus(422)->assertJsonValidationErrors(['input']);
    }

    // ─────────────────────────────────────────────
    // RUN (execution service mocked — no real LLM)
    // ─────────────────────────────────────────────

    public function test_run_dispatches_async_and_returns_trace_id(): void
    {
        // Mock execution service so we don't need a real LLM
        $this->mock(AgentExecutionService::class, function ($mock) {
            $mock->shouldReceive('runAsync')
                ->once()
                ->andReturn([
                    'success'  => true,
                    'trace_id' => 'mock-trace-id-1234',
                    'mode'     => 'async',
                    'message'  => 'Agent task queued for execution.',
                ]);
        });

        $response = $this->postJson("/api/v1/agents/{$this->agent->id}/run", [
            'input' => 'Process async task.',
            'async' => true,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('mode', 'async')
            ->assertJsonPath('trace_id', 'mock-trace-id-1234');
    }

    public function test_run_requires_input_field(): void
    {
        $response = $this->postJson("/api/v1/agents/{$this->agent->id}/run", []);
        $response->assertStatus(422)->assertJsonValidationErrors(['input']);
    }

    // ─────────────────────────────────────────────
    // LOGS
    // ─────────────────────────────────────────────

    public function test_logs_returns_paginated_runtime_logs(): void
    {
        AgentRuntimeLog::create([
            'id'          => Str::uuid(),
            'agent_id'    => $this->agent->id,
            'trace_id'    => Str::uuid(),
            'step'        => 'completed',
            'input'       => ['task' => 'test'],
            'output'      => ['result' => 'ok'],
            'duration_ms' => 100,
        ]);

        $response = $this->getJson("/api/v1/agents/{$this->agent->id}/logs");

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => [['id', 'agent_id', 'trace_id', 'step', 'duration_ms']]]);
    }

    public function test_logs_returns_empty_for_agent_with_no_history(): void
    {
        $fresh = Agent::create([
            'name' => 'Fresh Agent', 'key' => 'fresh_agent',
            'type' => Agent::TYPE_AUTONOMOUS,
        ]);

        $response = $this->getJson("/api/v1/agents/{$fresh->id}/logs");
        $response->assertStatus(200);
        $this->assertCount(0, $response->json('data'));
    }

    // ─────────────────────────────────────────────
    // AGENT PERSONA
    // ─────────────────────────────────────────────

    public function test_agent_inherits_persona_system_prompt(): void
    {
        $response = $this->getJson("/api/v1/agents/{$this->agent->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.persona.name', 'Test Persona')
            ->assertJsonPath('data.persona.system_prompt', 'You are a test AI agent.');
    }

    // ─────────────────────────────────────────────
    // AGENT PERSONAS CRUD
    // ─────────────────────────────────────────────

    public function test_persona_index_returns_list(): void
    {
        $response = $this->getJson('/api/v1/agent-personas');
        $response->assertStatus(200);
    }

    public function test_persona_store_creates_valid_persona(): void
    {
        $response = $this->postJson('/api/v1/agent-personas', [
            'name'          => 'Sherlock Mode',
            'system_prompt' => 'You deduce everything with precision.',
            'tone_preferences' => ['formality' => 'very_formal'],
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'Sherlock Mode');

        $this->assertDatabaseHas('agent_personas', ['name' => 'Sherlock Mode']);
    }
}
