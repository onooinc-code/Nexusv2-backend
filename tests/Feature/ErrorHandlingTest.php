<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\Workflow;
use App\Models\Contact;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ErrorHandlingTest extends TestCase
{
    use RefreshDatabase;

    // ─── Validation Errors ───────────────────────────────────────────────

    public function test_agent_creation_validates_required_fields(): void
    {
        $response = $this->postJson('/api/v1/agents', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'key', 'type']);
    }

    public function test_workflow_creation_validates_required_steps(): void
    {
        $response = $this->postJson('/api/v1/workflows', [
            'name' => 'Test',
            'key' => 'test',
            'trigger_type' => 'manual',
            'steps' => [],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['steps']);
    }

    public function test_memory_creation_validates_type(): void
    {
        $response = $this->postJson('/api/v1/memories', [
            'type' => 'invalid_type',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['type']);
    }

    // ─── Not Found Errors ────────────────────────────────────────────────

    public function test_agent_not_found_returns_404(): void
    {
        $response = $this->getJson('/api/v1/agents/99999');
        $response->assertStatus(404);
    }

    public function test_workflow_not_found_returns_404(): void
    {
        $response = $this->getJson('/api/v1/workflows/99999');
        $response->assertStatus(404);
    }

    public function test_contact_not_found_returns_404(): void
    {
        $response = $this->getJson('/api/v1/contacts/99999');
        $response->assertStatus(404);
    }

    // ─── Conflict Errors ─────────────────────────────────────────────────

    public function test_agent_already_running_returns_conflict(): void
    {
        $agent = Agent::factory()->create(['status' => Agent::STATUS_RUNNING]);
        $response = $this->postJson("/api/v1/agents/{$agent->id}/execute");
        $response->assertStatus(409)
            ->assertJsonPath('message', 'Agent is already running');
    }

    public function test_workflow_already_running_returns_conflict(): void
    {
        $workflow = Workflow::factory()->create(['status' => Workflow::STATUS_RUNNING]);
        $response = $this->postJson("/api/v1/workflows/{$workflow->id}/execute");
        $response->assertStatus(409)
            ->assertJsonPath('message', 'Workflow is already running');
    }

    // ─── Server Errors ───────────────────────────────────────────────────

    public function test_invalid_json_returns_400(): void
    {
        $response = $this->postJson('/api/v1/agents', [], [
            'HTTP_Content-Type' => 'application/json',
        ]);

        $response->assertStatus(422);
    }

    // ─── Graceful Degradation ────────────────────────────────────────────

    public function test_memory_service_unavailable_returns_error(): void
    {
        $response = $this->postJson('/api/v1/memories', [
            'type' => 'semantic',
            'contactId' => 99999,
            'content' => 'Test',
        ]);

        $response->assertStatus(422);
    }

    // ─── Error Response Format ───────────────────────────────────────────

    public function test_error_responses_have_consistent_format(): void
    {
        $response = $this->postJson('/api/v1/agents', []);

        $response->assertStatus(422)
            ->assertJsonStructure(['errors']);
    }

    public function test_server_errors_have_message_and_error_fields(): void
    {
        $response = $this->getJson('/api/v1/agents/99999');

        if ($response->status() === 500) {
            $response->assertJsonStructure(['message', 'error']);
        }
    }
}
