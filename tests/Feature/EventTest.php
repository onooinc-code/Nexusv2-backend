<?php

namespace Tests\Feature;

use App\Events\AgentExecuted;
use App\Events\ContactCreated;
use App\Events\MemoryIndexed;
use App\Events\MessageReceived;
use App\Events\MessageSent;
use App\Events\WorkflowCompleted;
use App\Events\WorkflowStarted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class EventTest extends TestCase
{
    use RefreshDatabase;

    public function test_contact_created_event_is_dispatched(): void
    {
        Event::fake([ContactCreated::class]);
        $response = $this->postJson('/api/v1/contacts', [
            'name' => 'Test Contact',
            'email' => 'test@example.com',
            'type' => 'contact',
        ]);
        $response->assertStatus(201);
        Event::assertDispatched(ContactCreated::class);
    }

    public function test_message_received_event_is_dispatched(): void
    {
        Event::fake([MessageReceived::class]);
        $response = $this->postJson('/api/v1/webhooks/waha', [
            'event' => 'message',
            'data' => [
                'chatId' => '1234567890@c.us',
                'content' => 'Hello',
                'from' => 'Test User',
            ],
        ]);
        $response->assertStatus(200);
        Event::assertDispatched(MessageReceived::class);
    }

    public function test_memory_indexed_event_is_dispatched_after_episodic_memory_store(): void
    {
        Event::fake([MemoryIndexed::class]);

        $user = \App\Models\User::factory()->create();
        $contact = \App\Models\Contact::factory()->create();

        $this->actingAs($user, 'sanctum');

        $response = $this->postJson('/api/v1/memories', [
            'type' => 'episodic',
            'contactId' => $contact->id,
            'content' => 'Test indexed episodic memory',
            'sender' => 'user',
        ]);

        $response->assertStatus(201);
        Event::assertDispatched(MemoryIndexed::class, function (MemoryIndexed $event) use ($contact) {
            return $event->contact->id === $contact->id
                && $event->memoryType === 'episodic'
                && $event->content['content'] === 'Test indexed episodic memory';
        });
    }

    public function test_workflow_started_event_is_dispatched(): void
    {
        Event::fake([WorkflowStarted::class]);
        $workflow = \App\Models\Workflow::factory()->create([
            'status' => \App\Models\Workflow::STATUS_DRAFT,
            'is_active' => true,
            'steps' => [['name' => 'Step 1', 'action' => 'log', 'message' => 'Test']],
        ]);
        $response = $this->postJson("/api/v1/workflows/{$workflow->id}/execute");
        $response->assertStatus(200);
        Event::assertDispatched(WorkflowStarted::class);
    }

    public function test_workflow_completed_event_is_dispatched(): void
    {
        Event::fake([WorkflowCompleted::class]);
        $workflow = \App\Models\Workflow::factory()->create([
            'status' => \App\Models\Workflow::STATUS_DRAFT,
            'is_active' => true,
            'steps' => [['name' => 'Step 1', 'action' => 'log', 'message' => 'Test']],
        ]);
        $response = $this->postJson("/api/v1/workflows/{$workflow->id}/execute");
        $response->assertStatus(200);
        Event::assertDispatched(WorkflowCompleted::class);
    }

    public function test_agent_executed_event_is_dispatched(): void
    {
        Event::fake([AgentExecuted::class]);
        $agent = \App\Models\Agent::factory()->create(['status' => \App\Models\Agent::STATUS_IDLE]);
        $response = $this->postJson("/api/v1/agents/{$agent->id}/execute");
        $response->assertStatus(200);
        Event::assertDispatched(AgentExecuted::class);
    }
}
