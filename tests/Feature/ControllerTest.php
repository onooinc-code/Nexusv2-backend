<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\Workflow;
use App\Models\Contact;
use App\Models\Setting;
use App\Models\SystemLog;
use App\Models\AIModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $user = \App\Models\User::factory()->create();
        $this->actingAs($user, 'sanctum');
    }

    public function test_agent_controller_index_returns_paginated_results(): void
    {
        Agent::factory()->count(3)->create();
        $response = $this->getJson('/api/v1/agents');
        $response->assertStatus(200)
            ->assertJsonStructure(['data' => [], 'meta' => []]);
    }

    public function test_agent_controller_store_creates_new_agent(): void
    {
        $response = $this->postJson('/api/v1/agents', [
            'name' => 'Test Agent',
            'key' => 'test-agent',
            'type' => Agent::TYPE_AUTONOMOUS,
        ]);
        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'Test Agent');
        $this->assertDatabaseHas('agents', ['name' => 'Test Agent']);
    }

    public function test_agent_controller_execute_starts_agent(): void
    {
        $agent = Agent::factory()->create(['status' => Agent::STATUS_IDLE]);
        $response = $this->postJson("/api/v1/agents/{$agent->id}/execute");
        $response->assertStatus(200)
            ->assertJsonPath('message', 'Agent execution started');
    }

    public function test_workflow_controller_store_creates_new_workflow(): void
    {
        $response = $this->postJson('/api/v1/workflows', [
            'name' => 'Test Workflow',
            'key' => 'test-workflow',
            'steps' => [['name' => 'Step 1', 'action' => 'process']],
            'trigger_type' => 'manual',
        ]);
        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'Test Workflow');
    }

    public function test_workflow_controller_execute_runs_workflow(): void
    {
        $workflow = Workflow::factory()->create([
            'status' => Workflow::STATUS_DRAFT,
            'is_active' => true,
            'steps' => [['name' => 'Step 1', 'action' => 'log', 'message' => 'Test']],
        ]);
        $response = $this->postJson("/api/v1/workflows/{$workflow->id}/execute");
        $response->assertStatus(200)
            ->assertJsonPath('message', 'Workflow execution completed');
    }

    public function test_task_controller_store_creates_and_enqueues_task(): void
    {
        $agent = Agent::factory()->create();
        $response = $this->postJson('/api/v1/tasks', [
            'title' => 'Test Task',
            'agent_id' => $agent->id,
        ]);
        $response->assertStatus(201)
            ->assertJsonPath('data.title', 'Test Task');
        $this->assertDatabaseHas('agent_tasks', ['title' => 'Test Task', 'status' => 'pending']);
    }

    public function test_task_controller_cancel_updates_status(): void
    {
        $task = \App\Models\AgentTask::factory()->create(['status' => 'pending']);
        $response = $this->postJson("/api/v1/tasks/{$task->id}/cancel");
        $response->assertStatus(200)
            ->assertJsonPath('message', 'Task cancelled');
    }

    public function test_memory_controller_store_working_memory(): void
    {
        $response = $this->postJson('/api/v1/memories', [
            'type' => 'working',
            'key' => 'test_key',
            'value' => 'test_value',
        ]);
        $response->assertStatus(201)
            ->assertJsonPath('type', 'working');
    }

    public function test_memory_controller_store_episodic_memory(): void
    {
        $contact = Contact::factory()->create();
        $response = $this->postJson('/api/v1/memories', [
            'type' => 'episodic',
            'contactId' => $contact->id,
            'content' => 'Test memory',
        ]);
        $response->assertStatus(201)
            ->assertJsonPath('type', 'episodic');
    }

    public function test_setting_controller_store_creates_setting(): void
    {
        $response = $this->postJson('/api/v1/settings', [
            'key' => 'test_setting',
            'value' => 'test_value',
            'type' => 'string',
            'group' => 'general',
        ]);
        $response->assertStatus(201);
        $this->assertDatabaseHas('settings', ['key' => 'test_setting']);
    }

    public function test_log_controller_stats_returns_statistics(): void
    {
        $response = $this->getJson('/api/v1/logs/stats');
        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['total', 'by_level', 'by_category', 'today', 'errors_today']]);
    }

    public function test_ai_model_controller_store_creates_model(): void
    {
        $response = $this->postJson('/api/v1/ai-models', [
            'name' => 'Test Model',
            'provider' => 'openai',
            'model' => 'gpt-4',
            'api_key' => 'test-key',
        ]);
        $response->assertStatus(201);
        $this->assertDatabaseHas('ai_models', ['name' => 'Test Model']);
    }

    public function test_contact_controller_import_creates_multiple_contacts(): void
    {
        $response = $this->postJson('/api/v1/contacts/import', [
            'contacts' => [
                ['name' => 'Alice', 'email' => 'alice@example.com', 'type' => Contact::TYPE_FRIEND],
                ['name' => 'Bob', 'email' => 'bob@example.com', 'type' => Contact::TYPE_FAMILY],
            ],
        ]);
        $response->assertStatus(200)
            ->assertJsonPath('created', 2);
    }
}
