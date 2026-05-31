<?php

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactImportAndMessagesTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->actingAs($this->user, 'sanctum');
    }

    public function test_whatsapp_txt_preview_and_commit_create_messages()
    {
        $contact = Contact::factory()->create([
            'name' => 'Sam WhatsApp',
            'whatsapp_number' => '+15550001111',
        ]);

        $content = implode("\n", [
            '31/05/2026, 2:30 PM - Sam WhatsApp: Hello Hedra',
            '31/05/2026, 2:31 PM - You: Hello Sam',
            '31/05/2026, 2:32 PM - Sam WhatsApp: Are we still on for tomorrow?',
        ]);

        $this->postJson('/api/v1/contacts/import/preview', [
            'contact_id' => $contact->id,
            'source' => 'whatsapp',
            'format' => 'txt',
            'content' => $content,
            'timezone' => 'Africa/Cairo',
        ])->assertStatus(200)
            ->assertJsonPath('data.success', true)
            ->assertJsonPath('data.total_messages', 3)
            ->assertJsonPath('data.inbound_count', 2)
            ->assertJsonPath('data.outbound_count', 1);

        $response = $this->postJson('/api/v1/contacts/import/whatsapp', [
            'contact_id' => $contact->id,
            'format' => 'txt',
            'content' => $content,
            'timezone' => 'Africa/Cairo',
        ])->assertStatus(200)
            ->assertJsonPath('data.success', true)
            ->assertJsonPath('data.created', 3);

        $batchId = $response->json('data.batch_id');

        $this->assertDatabaseHas('contact_import_batches', [
            'id' => $batchId,
            'contact_id' => $contact->id,
            'source' => 'whatsapp',
            'status' => 'completed',
        ]);
        $this->assertDatabaseHas('contact_messages', [
            'contact_id' => $contact->id,
            'channel' => 'whatsapp',
            'body' => 'Hello Hedra',
            'direction' => 'inbound',
        ]);
        $this->assertDatabaseHas('contact_messages', [
            'contact_id' => $contact->id,
            'body' => 'Hello Sam',
            'direction' => 'outbound',
        ]);
    }

    public function test_message_routes_filter_search_and_source_specific_views()
    {
        $contact = Contact::factory()->create(['name' => 'Filter Contact']);

        $this->postJson('/api/v1/contacts/import/whatsapp', [
            'contact_id' => $contact->id,
            'format' => 'json',
            'content' => json_encode([
                'messages' => [
                    [
                        'id' => 'wa-1',
                        'chatId' => '15550001111@c.us',
                        'timestamp' => 1780230600,
                        'fromMe' => false,
                        'from' => '15550001111@c.us',
                        'body' => 'Need the pricing sheet',
                    ],
                    [
                        'id' => 'wa-2',
                        'chatId' => '15550001111@c.us',
                        'timestamp' => 1780230660,
                        'fromMe' => true,
                        'from' => 'me',
                        'body' => 'I will send it today',
                    ],
                ],
            ]),
        ])->assertStatus(200);

        $this->getJson("/api/v1/contacts/{$contact->id}/messages?search=pricing")
            ->assertStatus(200)
            ->assertJsonPath('data.data.0.body', 'Need the pricing sheet');

        $this->getJson("/api/v1/contacts/{$contact->id}/messages/whatsapp")
            ->assertStatus(200)
            ->assertJsonCount(2, 'data.data');

        $this->getJson("/api/v1/contacts/{$contact->id}/threads")
            ->assertStatus(200)
            ->assertJsonPath('data.data.0.messages_count', 2);
    }

    public function test_facebook_import_and_rollback()
    {
        $contact = Contact::factory()->create(['name' => 'Facebook Contact']);

        $response = $this->postJson('/api/v1/contacts/import/facebook', [
            'contact_id' => $contact->id,
            'format' => 'json',
            'content' => json_encode([
                'title' => 'Facebook Contact',
                'participants' => [['name' => 'Facebook Contact'], ['name' => 'Hedra']],
                'messages' => [
                    [
                        'sender_name' => 'Facebook Contact',
                        'timestamp_ms' => 1780230600000,
                        'content' => 'Facebook hello',
                    ],
                ],
            ]),
        ])->assertStatus(200)
            ->assertJsonPath('data.created', 1);

        $batchId = $response->json('data.batch_id');
        $this->assertDatabaseHas('contact_messages', ['import_batch_id' => $batchId, 'body' => 'Facebook hello']);

        $this->postJson("/api/v1/contacts/imports/{$batchId}/rollback")
            ->assertStatus(200)
            ->assertJsonPath('data.deleted', 1);

        $this->assertSoftDeleted('contact_messages', ['import_batch_id' => $batchId, 'body' => 'Facebook hello']);
        $this->assertDatabaseHas('contact_import_batches', ['id' => $batchId, 'status' => 'rolled_back']);
    }

    public function test_contacthub_supporting_vnext_endpoints()
    {
        $contact = Contact::factory()->create(['name' => 'VNext Contact']);

        $this->postJson("/api/v1/contacts/{$contact->id}/reply-rules", [
            'rule' => 'Require approval for pricing topics.',
        ])->assertStatus(201)
            ->assertJsonPath('data.rule', 'Require approval for pricing topics.');

        $this->postJson("/api/v1/contacts/{$contact->id}/analysis-runs", [
            'options' => ['source' => 'all'],
        ])->assertStatus(201)
            ->assertJsonPath('data.status', 'pending');

        $this->postJson("/api/v1/contacts/{$contact->id}/memory-maintenance", [
            'operation' => 'detect_conflicts',
            'dry_run' => true,
        ])->assertStatus(201)
            ->assertJsonPath('data.status', 'dry_run');

        $this->getJson("/api/v1/contacts/{$contact->id}/intelligence")
            ->assertStatus(200)
            ->assertJsonPath('data.contact_id', $contact->id);

        $this->postJson("/api/v1/contacts/{$contact->id}/export")
            ->assertStatus(200)
            ->assertJsonPath('data.schema_version', 1);
    }
}
