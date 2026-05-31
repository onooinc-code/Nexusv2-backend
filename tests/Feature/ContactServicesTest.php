<?php

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\ContactIdentifier;
use App\Models\ContactPreference;
use App\Models\ContactRelationship;
use App\Models\ContactAlias;
use App\Models\User;
use App\Services\ContactProfileAssembler;
use App\Services\ContactIdentityResolver;
use App\Services\ContactAuditService;
use App\Services\ContactPrivacyService;
use App\Services\ContactAnalyticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactServicesTest extends TestCase
{
    use RefreshDatabase;

    protected ContactProfileAssembler $profileAssembler;
    protected ContactIdentityResolver $identityResolver;
    protected ContactAuditService $auditService;
    protected ContactPrivacyService $privacyService;
    protected ContactAnalyticsService $analyticsService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->profileAssembler = $this->app->make(ContactProfileAssembler::class);
        $this->identityResolver = $this->app->make(ContactIdentityResolver::class);
        $this->auditService = $this->app->make(ContactAuditService::class);
        $this->privacyService = $this->app->make(ContactPrivacyService::class);
        $this->analyticsService = $this->app->make(ContactAnalyticsService::class);
    }

    public function test_identity_resolver_resolves_correctly()
    {
        $contact = Contact::factory()->create([
            'name' => 'Hedra Resolves',
            'email' => 'hedra@resolves.com',
            'phone' => '+1234567890',
        ]);

        $this->identityResolver->linkIdentifier($contact, ContactIdentifier::TYPE_EMAIL, 'hedra@resolves.com', true);

        // Resolve by exact identifier
        $resolved = $this->identityResolver->resolve([
            ['type' => ContactIdentifier::TYPE_EMAIL, 'value' => 'HEDRA@resolves.com'] // testing normalization
        ]);

        $this->assertNotNull($resolved);
        $this->assertEquals($contact->id, $resolved->id);
    }

    public function test_profile_assembler_builds_complete_profile()
    {
        $contact = Contact::factory()->create(['name' => 'Hedra Profile']);
        
        $this->identityResolver->linkIdentifier($contact, ContactIdentifier::TYPE_EMAIL, 'profile@hedra.com');
        $contact->preferences()->create(['key' => 'theme', 'value' => 'dark']);
        
        $target = Contact::factory()->create(['name' => 'Hedra Target']);
        ContactRelationship::create([
            'source_contact_id' => $contact->id,
            'target_contact_id' => $target->id,
            'type' => 'colleague',
            'strength' => 0.8,
            'confidence' => 0.9,
        ]);

        $profile = $this->profileAssembler->assemble($contact, false);

        $this->assertEquals('Hedra Profile', $profile['name']);
        $this->assertCount(1, $profile['identifiers']);
        $this->assertEquals('profile@hedra.com', $profile['identifiers'][0]['value']);
        $this->assertEquals('dark', $profile['preferences']['theme']);
        $this->assertCount(1, $profile['relationships']);
        $this->assertEquals('colleague', $profile['relationships'][0]['type']);
    }

    public function test_audit_service_logs_events()
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');

        $contact = Contact::factory()->create(['name' => 'Hedra Audit']);

        $event = $this->auditService->logEvent($contact, 'test.action', ['old' => 'value'], ['new' => 'value']);

        $this->assertDatabaseHas('contact_audit_events', [
            'contact_id' => $contact->id,
            'action' => 'test.action',
            'actor_id' => $user->id,
        ]);

        $events = $this->auditService->getEvents($contact);
        $this->assertCount(1, $events);
        $this->assertEquals('test.action', $events->first()->action);
    }

    public function test_privacy_service_erases_and_exports()
    {
        $contact = Contact::factory()->create(['name' => 'Hedra Privacy', 'email' => 'privacy@hedra.com']);
        $this->identityResolver->linkIdentifier($contact, ContactIdentifier::TYPE_EMAIL, 'privacy@hedra.com');

        $export = $this->privacyService->exportProfile($contact);
        $this->assertEquals('Hedra Privacy', $export['name']);

        $this->privacyService->eraseProfile($contact, false); // soft delete / redact
        
        $contact->refresh();
        $this->assertEquals('Erased Contact', $contact->name);
        $this->assertNull($contact->email);
        $this->assertSoftDeleted('contact_identifiers', ['contact_id' => $contact->id]);
    }

    public function test_analytics_service_gets_metrics()
    {
        $contact = Contact::factory()->create(['name' => 'Hedra Analytics']);

        $stats = $this->analyticsService->getContactStats($contact, 7);
        $this->assertEquals(0, $stats['message_count']);
        $this->assertArrayHasKey('time_series', $stats);
        
        $globalStats = $this->analyticsService->getGlobalStats();
        $this->assertGreaterThan(0, $globalStats['total_contacts']);
    }
}
