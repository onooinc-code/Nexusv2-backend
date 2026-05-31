<?php

namespace App\Services;

use App\Models\Contact;
use App\Models\ContactAuditEvent;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class ContactAuditService
{
    /**
     * Log a contact-related audit event.
     *
     * @param Contact $contact
     * @param string $action
     * @param array|null $before
     * @param array|null $after
     * @param string|null $traceId
     * @return ContactAuditEvent
     */
    public function logEvent(
        Contact $contact,
        string $action,
        ?array $before = null,
        ?array $after = null,
        ?string $traceId = null
    ): ContactAuditEvent {
        $actor = Auth::user();
        
        return ContactAuditEvent::create([
            'contact_id' => $contact->id,
            'actor_type' => $actor ? get_class($actor) : 'system',
            'actor_id' => $actor ? $actor->id : null,
            'action' => $action,
            'before_state' => $before,
            'after_state' => $after,
            'trace_id' => $traceId ?: Request::header('X-Trace-Id'),
            'ip_address' => Request::ip(),
        ]);
    }

    /**
     * Retrieve audit events for a contact.
     *
     * @param Contact $contact
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getEvents(Contact $contact, int $limit = 50)
    {
        return ContactAuditEvent::where('contact_id', $contact->id)
            ->latest()
            ->limit($limit)
            ->get();
    }
}
