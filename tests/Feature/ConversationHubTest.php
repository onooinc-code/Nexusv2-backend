<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConversationHubTest extends TestCase
{
    use RefreshDatabase;

    public function test_conversation_messages_payload_contains_channel_thread_and_metadata(): void
    {
        $user = \App\Models\User::factory()->create();
        $this->actingAs($user, 'sanctum');

        $conversationId = 123;

        $response = $this->getJson("/api/v1/conversations/{$conversationId}/messages");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'conversation_id',
                    'messages' => [
                        ['id', 'conversation_id', 'sender', 'channel', 'thread_id', 'content', 'metadata', 'created_at'],
                    ],
                ],
            ]);

        $this->assertEquals($conversationId, $response->json('data.conversation_id'));
    }

    public function test_send_message_accepts_channel_thread_and_metadata(): void
    {
        $user = \App\Models\User::factory()->create();
        $this->actingAs($user, 'sanctum');

        $conversationId = 456;

        $payload = [
            'sender' => 'agent',
            'content' => 'Hello from the hub',
            'channel' => 'email',
            'thread_id' => 'thread-789',
            'metadata' => ['urgent' => true],
        ];

        $response = $this->postJson("/api/v1/conversations/{$conversationId}/send-message", $payload);

        $response->assertStatus(201)
            ->assertJsonPath('data.conversation_id', $conversationId)
            ->assertJsonPath('data.sender', 'agent')
            ->assertJsonPath('data.channel', 'email')
            ->assertJsonPath('data.thread_id', 'thread-789')
            ->assertJsonPath('data.metadata.urgent', true);
    }
}
