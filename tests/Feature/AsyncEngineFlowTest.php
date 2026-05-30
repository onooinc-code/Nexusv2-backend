<?php

namespace Tests\Feature;

use App\Jobs\ExtractMemoryJob;
use App\Jobs\ProcessAiInferenceJob;
use App\Models\Conversation;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class AsyncEngineFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_webhook_endpoint_dispatches_ai_inference_job(): void
    {
        Bus::fake();

        $conversation = Conversation::factory()->create();
        $contact = $conversation->contact;

        $response = $this->postJson('/api/v1/webhooks/waha', [
            'conversation_id' => $conversation->id,
            'message' => 'Hello from webhook',
            'sender' => 'webhook_user',
            'channel' => 'whatsapp',
            'metadata' => ['source' => 'waha'],
        ]);

        $response->assertStatus(202)
            ->assertJson([ 'message' => 'Webhook message queued for processing' ]);

        Bus::assertDispatched(ProcessAiInferenceJob::class, function ($job) use ($conversation) {
            $reflection = new \ReflectionObject($job);
            $property = $reflection->getProperty('conversationId');
            $property->setAccessible(true);
            return $property->getValue($job) == $conversation->id;
        });
    }

    public function test_memory_index_endpoint_dispatches_extract_memory_job(): void
    {
        Bus::fake();

        $user = User::factory()->create();
        $conversation = Conversation::factory()->create();
        $contact = $conversation->contact;

        $this->actingAs($user, 'sanctum');

        $response = $this->postJson("/api/v1/memories/{$conversation->id}/index", [
            'type' => 'episodic',
            'contactId' => $contact->id,
        ]);

        $response->assertStatus(202)
            ->assertJson([ 'message' => 'Memory extraction dispatched' ]);

        Bus::assertDispatched(ExtractMemoryJob::class, function ($job) use ($conversation) {
            $reflection = new \ReflectionObject($job);
            $property = $reflection->getProperty('conversationId');
            $property->setAccessible(true);
            return $property->getValue($job) == $conversation->id;
        });
    }
}
