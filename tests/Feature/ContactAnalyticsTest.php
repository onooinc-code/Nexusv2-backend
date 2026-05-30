<?php

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\User;
use App\Models\Memory;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_contact_analytics_returns_time_series_and_counts()
    {
        $user = User::factory()->create();
        $contact = Contact::factory()->for($user)->create();

        // Create memories across the last 3 days
        Memory::factory()->for($contact)->count(3)->create();

        // Create a conversation and messages
        $conversation = Conversation::factory()->for($contact)->create();
        Message::factory()->for($conversation)->count(5)->create([
            'sent_at' => now(),
        ]);

        $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/contacts/{$contact->id}/analytics")
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'contact_id',
                    'analytics' => [
                        'type',
                        'last_seen_at',
                        'memory_count',
                        'tag_count',
                        'rule_count',
                        'baseline',
                        'time_series' => [
                            'memories',
                            'messages',
                            'conversations',
                        ],
                    ],
                ],
            ]);
    }
}
