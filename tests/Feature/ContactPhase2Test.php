<?php

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\User;
use App\Services\ContactReplyModeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ContactPhase2Test extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->actingAs($this->user, 'sanctum');
    }

    // =========================================================================
    // Stats
    // =========================================================================

    public function test_stats_endpoint_returns_expected_keys()
    {
        Contact::factory()->count(3)->create(['is_active' => true]);

        $response = $this->getJson('/api/v1/contacts/stats');
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'data' => [
                         'total_contacts',
                         'active_contacts',
                         'new_imported_messages',
                         'pending_analysis_runs',
                         'stale_memory_count',
                         'identity_conflict_count',
                         'autopilot_enabled_count',
                         'failed_imports',
                         'failed_analysis_runs',
                         'generated_at',
                     ],
                 ]);

        $this->assertGreaterThanOrEqual(3, $response->json('data.total_contacts'));
    }

    // =========================================================================
    // Global reply mode
    // =========================================================================

    public function test_get_global_reply_mode_defaults_to_manual()
    {
        Cache::forget(ContactReplyModeService::GLOBAL_KEY);

        $response = $this->getJson('/api/v1/contacts/reply-mode');
        $response->assertStatus(200)
                 ->assertJsonPath('data.mode', ContactReplyModeService::MODE_MANUAL)
                 ->assertJsonPath('data.is_autopilot_active', false);
    }

    public function test_set_global_reply_mode_to_copilot()
    {
        $response = $this->patchJson('/api/v1/contacts/reply-mode', [
            'mode' => 'copilot',
        ]);

        $response->assertStatus(200)
                 ->assertJsonPath('data.mode', 'copilot');

        // Verify persistence via the GET endpoint
        $this->getJson('/api/v1/contacts/reply-mode')
             ->assertJsonPath('data.mode', 'copilot');
    }

    public function test_set_global_reply_mode_to_autopilot_returns_warning_flag()
    {
        $this->patchJson('/api/v1/contacts/reply-mode', ['mode' => 'autopilot'])
             ->assertStatus(200)
             ->assertJsonPath('data.mode', 'autopilot');

        $this->getJson('/api/v1/contacts/reply-mode')
             ->assertJsonPath('data.is_autopilot_active', true);
    }

    public function test_set_global_reply_mode_rejects_invalid_mode()
    {
        $this->patchJson('/api/v1/contacts/reply-mode', ['mode' => 'turbo-ai'])
             ->assertStatus(422);
    }

    // =========================================================================
    // Per-contact reply mode
    // =========================================================================

    public function test_get_contact_reply_mode_inherits_global()
    {
        Cache::put(ContactReplyModeService::GLOBAL_KEY, 'copilot', now()->addHour());
        $contact = Contact::factory()->create(['reply_mode_override' => null]);

        $this->getJson("/api/v1/contacts/{$contact->id}/reply-mode")
             ->assertStatus(200)
             ->assertJsonPath('data.global_mode', 'copilot')
             ->assertJsonPath('data.override', null)
             ->assertJsonPath('data.effective', 'copilot');
    }

    public function test_set_contact_reply_mode_override()
    {
        Cache::put(ContactReplyModeService::GLOBAL_KEY, 'manual', now()->addHour());
        $contact = Contact::factory()->create(['reply_mode_override' => null]);

        $this->patchJson("/api/v1/contacts/{$contact->id}/reply-mode", [
            'mode' => 'autopilot',
        ])->assertStatus(200)
          ->assertJsonPath('data.override', 'autopilot')
          ->assertJsonPath('data.effective', 'autopilot');

        $this->assertDatabaseHas('contacts', [
            'id'                 => $contact->id,
            'reply_mode_override'=> 'autopilot',
        ]);

        // Audit event should have been written
        $this->assertDatabaseHas('contact_audit_events', [
            'contact_id' => $contact->id,
            'action'     => 'reply_mode.changed',
        ]);
    }

    public function test_clear_contact_reply_mode_override()
    {
        $contact = Contact::factory()->create(['reply_mode_override' => 'autopilot']);

        $this->patchJson("/api/v1/contacts/{$contact->id}/reply-mode", [
            'mode' => null,
        ])->assertStatus(200)
          ->assertJsonPath('data.override', null);

        $this->assertDatabaseHas('contacts', [
            'id'                  => $contact->id,
            'reply_mode_override' => null,
        ]);
    }
}
