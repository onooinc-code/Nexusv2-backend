<?php

namespace App\Services;

use App\Models\Contact;
use App\Models\ContactAuditEvent;
use App\Models\ContactChannel;
use App\Models\ContactIdentifier;
use App\Models\ContactAlias;
use App\Models\ContactMessageThread;
use App\Models\ContactMessage;
use App\Models\ContactAnalysisRun;
use App\Models\ContactAnalysisFinding;
use App\Models\ContactMemory;
use App\Models\ContactMemoryVersion;
use App\Models\ContactRelationship;
use App\Models\ContactPreference;
use App\Models\ContactReplyRule;
use App\Models\ContactTopic;
use App\Models\ContactTopicMention;
use App\Models\ContactProfileSnapshot;
use Illuminate\Support\Facades\DB;

class ContactPrivacyService
{
    public function __construct(
        protected ContactProfileAssembler $profileAssembler,
        protected ContactAuditService $auditService
    ) {}

    /**
     * Export all personal data relating to a contact.
     */
    public function exportProfile(Contact $contact): array
    {
        $this->auditService->logEvent($contact, 'privacy.export');
        
        return $this->profileAssembler->assemble($contact, false);
    }

    /**
     * Completely delete or anonymize all records for a contact.
     */
    public function eraseProfile(Contact $contact, bool $hardDelete = false): void
    {
        DB::transaction(function () use ($contact, $hardDelete) {
            // Log the erasure action first
            $this->auditService->logEvent($contact, 'privacy.erase', [
                'name' => $contact->name,
                'email' => $contact->email,
            ]);

            // 1. Delete associated nested records
            ContactChannel::where('contact_id', $contact->id)->delete();
            ContactIdentifier::where('contact_id', $contact->id)->delete();
            ContactAlias::where('contact_id', $contact->id)->delete();
            
            // Delete messages and threads
            $threadIds = ContactMessageThread::where('contact_id', $contact->id)->pluck('id');
            ContactMessage::whereIn('thread_id', $threadIds)->orWhere('contact_id', $contact->id)->delete();
            ContactMessageThread::where('contact_id', $contact->id)->delete();

            // Delete memories and versions
            $memoryIds = ContactMemory::where('contact_id', $contact->id)->pluck('id');
            ContactMemoryVersion::whereIn('memory_id', $memoryIds)->delete();
            ContactMemory::where('contact_id', $contact->id)->delete();

            // Delete analysis runs and findings
            $runIds = ContactAnalysisRun::where('contact_id', $contact->id)->pluck('id');
            ContactAnalysisFinding::whereIn('analysis_run_id', $runIds)->orWhere('contact_id', $contact->id)->delete();
            ContactAnalysisRun::where('contact_id', $contact->id)->delete();

            // Delete relationships (both directions)
            ContactRelationship::where('source_contact_id', $contact->id)
                ->orWhere('target_contact_id', $contact->id)
                ->delete();

            ContactPreference::where('contact_id', $contact->id)->delete();
            ContactReplyRule::where('contact_id', $contact->id)->delete();
            
            // Delete topics and mentions
            $topicIds = ContactTopic::where('contact_id', $contact->id)->pluck('id');
            ContactTopicMention::whereIn('topic_id', $topicIds)->delete();
            ContactTopic::where('contact_id', $contact->id)->delete();

            ContactProfileSnapshot::where('contact_id', $contact->id)->delete();

            // 2. Anonymize or Hard Delete the contact record
            if ($hardDelete) {
                // Delete audit events as well if hard deleting
                ContactAuditEvent::where('contact_id', $contact->id)->delete();
                $contact->forceDelete();
            } else {
                $contact->update([
                    'name' => 'Erased Contact',
                    'display_name' => null,
                    'alternate_name' => null,
                    'canonical_name' => null,
                    'email' => null,
                    'phone' => null,
                    'whatsapp_number' => null,
                    'avatar_url' => null,
                    'company' => null,
                    'title' => null,
                    'metadata' => [
                        'erased_at' => now()->toDateTimeString(),
                        'erasure' => true,
                    ],
                    'attributes' => [],
                    'is_active' => false,
                    'profile_confidence' => 0,
                ]);
                $contact->delete(); // Soft delete
            }
        });
    }
}
