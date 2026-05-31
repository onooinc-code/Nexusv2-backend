<?php

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactsHubTest extends TestCase
{
    use RefreshDatabase;

    public function test_contact_crud_and_search_workflow()
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');

        $createResponse = $this->postJson('/api/v1/contacts', [
            'name' => 'Hedra Nexus',
            'email' => 'hedra@nexus.ai',
            'phone' => '+1234567890',
            'type' => Contact::TYPE_CLIENT,
            'company' => 'Nexus Labs',
        ]);

        $createResponse->assertStatus(201);
        $contactId = $createResponse->json('data.id');

        $this->assertDatabaseHas('contacts', ['id' => $contactId, 'name' => 'Hedra Nexus', 'type' => Contact::TYPE_CLIENT]);

        $showResponse = $this->getJson("/api/v1/contacts/{$contactId}");
        $showResponse->assertStatus(200);
        $showResponse->assertJsonPath('data.name', 'Hedra Nexus');

        $searchResponse = $this->getJson('/api/v1/contacts?search=Hedra');
        $searchResponse->assertStatus(200);
        $searchResponse->assertJsonPath('data.0.name', 'Hedra Nexus');

        $updateResponse = $this->putJson("/api/v1/contacts/{$contactId}", [
            'title' => 'Executive AI',
            'type' => Contact::TYPE_PROSPECT,
        ]);
        $updateResponse->assertStatus(200);
        $updateResponse->assertJsonPath('data.title', 'Executive AI');
        $this->assertDatabaseHas('contacts', ['id' => $contactId, 'type' => Contact::TYPE_PROSPECT]);

        $deleteResponse = $this->deleteJson("/api/v1/contacts/{$contactId}");
        $deleteResponse->assertStatus(200);
        $this->assertSoftDeleted('contacts', ['id' => $contactId]);
    }

    public function test_contact_import_export_endpoints()
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');

        $importPayload = [
            'contacts' => [
                [
                    'name' => 'Alice Example',
                    'email' => 'alice@example.com',
                    'phone' => '+15550000001',
                    'type' => Contact::TYPE_FRIEND,
                ],
                [
                    'name' => 'Bob Example',
                    'email' => 'bob@example.com',
                    'phone' => '+15550000002',
                    'type' => Contact::TYPE_FAMILY,
                ],
            ],
        ];

        $importResponse = $this->postJson('/api/v1/contacts/import', $importPayload);
        $importResponse->assertStatus(200);
        $importResponse->assertJsonPath('created', 2);

        $this->assertDatabaseHas('contacts', ['name' => 'Alice Example', 'type' => Contact::TYPE_FRIEND]);
        $this->assertDatabaseHas('contacts', ['name' => 'Bob Example', 'type' => Contact::TYPE_FAMILY]);

        $exportResponse = $this->get('/api/v1/contacts/export');
        $exportResponse->assertStatus(200);
        $this->assertStringContainsString('text/csv', $exportResponse->headers->get('content-type'));
        $this->assertStringContainsString('Alice Example', $exportResponse->getContent());
    }

    public function test_contact_upsert_with_identifiers_updates_existing_contact()
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');

        $firstResponse = $this->postJson('/api/v1/contacts', [
            'name' => 'Original Persona',
            'email' => 'original@example.com',
            'phone' => '+15551234567',
        ]);

        $firstResponse->assertStatus(201);
        $contactId = $firstResponse->json('data.id');

        $secondResponse = $this->postJson('/api/v1/contacts', [
            'name' => 'Updated Persona',
            'email' => 'original@example.com',
            'company' => 'Updated Corp',
        ]);

        $secondResponse->assertStatus(200);
        $this->assertEquals($contactId, $secondResponse->json('data.id'));
        $this->assertDatabaseHas('contacts', ['id' => $contactId, 'company' => 'Updated Corp']);
    }

    public function test_contact_merge_and_erase_and_enrich_workflows()
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');

        $primaryContact = Contact::factory()->create(['name' => 'Primary Merge', 'email' => 'primary@merge.test']);
        $sourceContact = Contact::factory()->create(['name' => 'Source Merge', 'email' => 'source@merge.test']);

        $mergeResponse = $this->postJson("/api/v1/contacts/{$primaryContact->id}/merge", [
            'source_contact_id' => $sourceContact->id,
            'strategy' => 'prefer_new',
        ]);

        $mergeResponse->assertStatus(200);
        $this->assertDatabaseMissing('contacts', ['id' => $sourceContact->id, 'deleted_at' => null]);
        $this->assertSoftDeleted('contacts', ['id' => $sourceContact->id]);

        $enrichResponse = $this->postJson("/api/v1/contacts/{$primaryContact->id}/enrich", [
            'profile_data' => ['company' => 'Enriched Corp', 'title' => 'Enriched Title'],
            'source' => 'unit-test',
        ]);

        $enrichResponse->assertStatus(200);
        $this->assertEquals('Enriched Corp', $enrichResponse->json('data.company'));

        $eraseResponse = $this->deleteJson("/api/v1/contacts/{$primaryContact->id}/erase");
        $eraseResponse->assertStatus(200);
        $this->assertSoftDeleted('contacts', ['id' => $primaryContact->id]);
        $this->assertDatabaseMissing('contact_identifiers', ['contact_id' => $primaryContact->id]);
    }

    public function test_contact_analytics_and_related_endpoints()
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');

        $contact = Contact::factory()->create(['type' => Contact::TYPE_VENDOR]);

        $analyticsResponse = $this->getJson("/api/v1/contacts/{$contact->id}/analytics");
        $analyticsResponse->assertStatus(200);
        $analyticsResponse->assertJsonPath('data.contact_id', (string) $contact->id);
        $analyticsResponse->assertJsonPath('data.analytics.type', Contact::TYPE_VENDOR);
    }

    public function test_contact_subresource_endpoints_work()
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');

        $primary = Contact::factory()->create(['type' => Contact::TYPE_CLIENT]);
        $related = Contact::factory()->create(['type' => Contact::TYPE_PARTNER]);

        $identifierResponse = $this->postJson("/api/v1/contacts/{$primary->id}/identifiers", [
            'type' => 'email',
            'value' => 'test.person@example.com',
            'is_primary' => true,
        ]);
        $identifierResponse->assertStatus(201);
        $this->assertDatabaseHas('contact_identifiers', ['contact_id' => $primary->id, 'type' => 'email']);

        $relationshipResponse = $this->postJson("/api/v1/contacts/{$primary->id}/relationships", [
            'target_contact_id' => $related->id,
            'type' => 'partner',
            'strength' => 0.9,
            'confidence' => 0.9,
        ]);
        $relationshipResponse->assertStatus(201);
        $this->assertDatabaseHas('contact_relationships', ['source_contact_id' => $primary->id, 'target_contact_id' => $related->id]);

        $preferenceResponse = $this->postJson("/api/v1/contacts/{$primary->id}/preferences", [
            'key' => 'timezone',
            'value' => 'Europe/London',
        ]);
        $preferenceResponse->assertStatus(201);
        $this->assertDatabaseHas('contact_preferences', ['contact_id' => $primary->id, 'key' => 'timezone']);

        $aliasResponse = $this->postJson("/api/v1/contacts/{$primary->id}/aliases", [
            'name' => 'Terminal Nexus',
        ]);
        $aliasResponse->assertStatus(201);
        $this->assertDatabaseHas('contact_aliases', ['contact_id' => $primary->id, 'name' => 'Terminal Nexus']);

        $identifiersIndex = $this->getJson("/api/v1/contacts/{$primary->id}/identifiers");
        $identifiersIndex->assertStatus(200)->assertJsonCount(1, 'data');

        $relationshipsIndex = $this->getJson("/api/v1/contacts/{$primary->id}/relationships");
        $relationshipsIndex->assertStatus(200)->assertJsonCount(1, 'data');

        $preferencesIndex = $this->getJson("/api/v1/contacts/{$primary->id}/preferences");
        $preferencesIndex->assertStatus(200)->assertJsonCount(1, 'data');

        $aliasesIndex = $this->getJson("/api/v1/contacts/{$primary->id}/aliases");
        $aliasesIndex->assertStatus(200)->assertJsonCount(1, 'data');
    }
}
