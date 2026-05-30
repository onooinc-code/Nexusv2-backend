<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\Workflow;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserFlowTest extends TestCase
{
    use RefreshDatabase;

    // ─── Contact Onboarding Flow ─────────────────────────────────────────

    public function test_contact_onboarding_flow(): void
    {
        $user = \App\Models\User::factory()->create();
        $this->actingAs($user, 'sanctum');

        $response = $this->postJson('/api/v1/contacts', [
            'name' => 'New Client',
            'email' => 'newclient@example.com',
            'phone' => '+1234567890',
            'type' => Contact::TYPE_CLIENT,
            'company' => 'Acme Corp',
        ]);

        $response->assertStatus(201);
        $contactId = $response->json('data.id');

        $this->assertDatabaseHas('contacts', ['id' => $contactId, 'name' => 'New Client']);

        $memoryResponse = $this->postJson('/api/v1/memories', [
            'type' => 'episodic',
            'contactId' => $contactId,
            'content' => 'Initial onboarding conversation',
            'sender' => 'agent',
        ]);

        $memoryResponse->assertStatus(201);

        $analyticsResponse = $this->getJson("/api/v1/contacts/{$contactId}/analytics");
        $analyticsResponse->assertStatus(200);
    }

    // ─── Workflow Execution Flow ─────────────────────────────────────────

    public function test_workflow_execution_flow(): void
    {
        $user = \App\Models\User::factory()->create();
        $this->actingAs($user, 'sanctum');

        $workflow = \App\Models\Workflow::factory()->create([
            'name' => 'Test Workflow',
            'status' => \App\Models\Workflow::STATUS_DRAFT,
            'is_active' => true,
            'steps' => [
                ['name' => 'Step 1', 'action' => 'log', 'message' => 'Step 1'],
                ['name' => 'Step 2', 'action' => 'log', 'message' => 'Step 2'],
            ],
        ]);

        $executeResponse = $this->postJson("/api/v1/workflows/{$workflow->id}/execute");
        $executeResponse->assertStatus(200);

        $progressResponse = $this->getJson("/api/v1/workflows/{$workflow->id}/progress");
        $progressResponse->assertStatus(200)
            ->assertJsonStructure(['data' => ['progress', 'total_steps', 'completed_steps']]);
    }

    // ─── Agent Task Flow ─────────────────────────────────────────────────

    public function test_agent_task_lifecycle_flow(): void
    {
        $user = \App\Models\User::factory()->create();
        $this->actingAs($user, 'sanctum');

        $agent = Agent::factory()->create();

        $createResponse = $this->postJson('/api/v1/tasks', [
            'title' => 'Test Task',
            'description' => 'Test task description',
            'agent_id' => $agent->id,
            'priority' => 5,
        ]);

        $createResponse->assertStatus(201);
        $taskId = $createResponse->json('data.id');

        $this->postJson("/api/v1/tasks/{$taskId}/pause");
        $this->postJson("/api/v1/tasks/{$taskId}/resume");
        $this->postJson("/api/v1/tasks/{$taskId}/cancel");

        $this->assertDatabaseHas('agent_tasks', [
            'id' => $taskId,
            'status' => 'cancelled',
        ]);
    }

    // ─── Memory Management Flow ──────────────────────────────────────────

    public function test_memory_management_flow(): void
    {
        $user = \App\Models\User::factory()->create();
        $this->actingAs($user, 'sanctum');

        $contact = Contact::factory()->create();

        $storeResponse = $this->postJson('/api/v1/memories', [
            'type' => 'episodic',
            'contactId' => $contact->id,
            'content' => 'Test memory content',
        ]);

        $storeResponse->assertStatus(201);
        $memoryId = $storeResponse->json('id');

        $searchResponse = $this->getJson("/api/v1/memories/search?query=test&contactId={$contact->id}");
        $searchResponse->assertStatus(200)
            ->assertJsonPath('totalResults', 1)
            ->assertJsonFragment(['content' => 'Test memory content']);

        $this->postJson("/api/v1/memories/{$memoryId}", [
            'type' => 'episodic',
            'content' => 'Updated memory content',
        ]);

        $this->deleteJson("/api/v1/memories/{$memoryId}");

        $this->assertDatabaseMissing('memories', ['id' => $memoryId]);
    }

    // ─── Settings Management Flow ────────────────────────────────────────

    public function test_settings_management_flow(): void
    {
        $user = \App\Models\User::factory()->create();
        $this->actingAs($user, 'sanctum');

        $createResponse = $this->postJson('/api/v1/settings', [
            'key' => 'test_feature_flag',
            'value' => 'true',
            'type' => 'boolean',
            'group' => 'general',
        ]);

        $createResponse->assertStatus(201);

        $groupedResponse = $this->getJson('/api/v1/settings/grouped');
        $groupedResponse->assertStatus(200);

        $this->putJson('/api/v1/settings/test_feature_flag', [
            'value' => 'false',
        ]);

        $this->assertDatabaseHas('settings', [
            'key' => 'test_feature_flag',
            'value' => 'false',
        ]);
    }
}
