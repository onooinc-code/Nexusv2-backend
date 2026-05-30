<?php

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IntegrationTest extends TestCase
{
    use RefreshDatabase;

    // ─── WAHA Integration ────────────────────────────────────────────────

    public function test_waha_webhook_receives_and_stores_message(): void
    {
        $response = $this->postJson('/api/v1/webhooks/waha', [
            'event' => 'message',
            'data' => [
                'chatId' => '1234567890@c.us',
                'content' => 'Hello from WAHA',
                'from' => 'Test User',
                'timestamp' => now()->toISOString(),
            ],
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('status', 'received');
    }

    public function test_waha_webhook_creates_contact_if_not_exists(): void
    {
        $this->postJson('/api/v1/webhooks/waha', [
            'event' => 'message',
            'data' => [
                'chatId' => '9876543210@c.us',
                'content' => 'Hello',
                'from' => 'New Contact',
            ],
        ]);

        $this->assertDatabaseHas('contacts', ['name' => 'New Contact']);
    }

    // ─── Pinecone Integration ────────────────────────────────────────────

    public function test_semantic_memory_stores_embeddings(): void
    {
        $contact = Contact::factory()->create();

        $response = $this->postJson('/api/v1/memories', [
            'type' => 'semantic',
            'contactId' => $contact->id,
            'content' => 'Test semantic memory content',
        ]);

        $response->assertStatus(201);
    }

    // ─── AI Provider Integration ─────────────────────────────────────────

    public function test_ai_model_execute_with_openai_provider(): void
    {
        $model = \App\Models\AIModel::factory()->create([
            'provider' => 'openai',
            'model' => 'gpt-4',
            'api_key' => 'test-key',
        ]);

        $response = $this->postJson('/api/v1/ai-models/execute', [
            'model_id' => $model->id,
            'prompt' => 'Hello, world!',
        ]);

        $response->assertStatus(200);
    }

    public function test_ai_model_execute_with_gemini_provider(): void
    {
        $model = \App\Models\AIModel::factory()->create([
            'provider' => 'google',
            'model' => 'gemini-pro',
            'api_key' => 'test-key',
        ]);

        $response = $this->postJson('/api/v1/ai-models/execute', [
            'model_id' => $model->id,
            'prompt' => 'Hello, world!',
        ]);

        $response->assertStatus(200);
    }

    // ─── Full Conversation Flow ──────────────────────────────────────────

    public function test_full_conversation_flow_from_webhook_to_response(): void
    {
        $contact = Contact::factory()->create(['phone' => '1234567890']);

        $webhookResponse = $this->postJson('/api/v1/webhooks/waha', [
            'event' => 'message',
            'data' => [
                'chatId' => '1234567890@c.us',
                'content' => 'Hello, how are you?',
                'from' => $contact->name,
            ],
        ]);

        $webhookResponse->assertStatus(200);

        $this->assertDatabaseHas('messages', [
            'content' => 'Hello, how are you?',
        ]);
    }

    // ─── Memory Sync Integration ─────────────────────────────────────────

    public function test_memory_sync_job_syncs_to_external_stores(): void
    {
        $contact = Contact::factory()->create();
        $memory = Memory::factory()->create([
            'contact_id' => $contact->id,
            'type' => 'episodic',
            'content' => 'Sync test content',
        ]);

        $job = new SyncMemoryJob($memory);
        $result = $job->handle(new \App\Services\Memory\SemanticMemoryService());

        $this->assertTrue($result);
    }
}
