<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\ContactRelationship;
use App\Services\LogService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ContactRelationshipController extends Controller
{
    public function __construct(
        protected LogService $logService
    ) {}

    /**
     * List relationships for a contact.
     */
    public function index(Contact $contact)
    {
        $relationships = $contact->relationships()
            ->with('targetContact')
            ->orderBy('type')
            ->get();

        return response()->json(['data' => $relationships]);
    }

    /**
     * Store a new relationship.
     */
    public function store(Request $request, Contact $contact)
    {
        $data = $request->validate([
            'target_contact_id' => ['required', 'exists:contacts,id'],
            'type' => ['required', 'string', 'max:255'],
            'direction' => ['nullable', 'string', 'max:255'],
            'strength' => ['nullable', 'numeric', 'min:0', 'max:1'],
            'confidence' => ['nullable', 'numeric', 'min:0', 'max:1'],
            'evidence' => ['nullable', 'string'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        $data['source_contact_id'] = $contact->id;
        $data['strength'] = $data['strength'] ?? 1.0;
        $data['confidence'] = $data['confidence'] ?? 1.0;

        $relationship = ContactRelationship::create($data);

        $this->logService->info('Contact relationship created', [
            'channel' => 'contact',
            'type' => 'relationship_create',
            'related_id' => $contact->id,
            'related_type' => Contact::class,
            'user_id' => $request->user()?->id,
        ]);

        return response()->json(['data' => $relationship->load('targetContact')], 201);
    }

    /**
     * Show a specific relationship.
     */
    public function show(Contact $contact, $relationshipId)
    {
        $relationship = ContactRelationship::with('targetContact')
            ->where('source_contact_id', $contact->id)
            ->findOrFail($relationshipId);

        return response()->json(['data' => $relationship]);
    }

    /**
     * Update a relationship.
     */
    public function update(Request $request, Contact $contact, $relationshipId)
    {
        $relationship = ContactRelationship::where('source_contact_id', $contact->id)
            ->findOrFail($relationshipId);

        $data = $request->validate([
            'target_contact_id' => ['sometimes', 'exists:contacts,id'],
            'type' => ['sometimes', 'string', 'max:255'],
            'direction' => ['nullable', 'string', 'max:255'],
            'strength' => ['nullable', 'numeric', 'min:0', 'max:1'],
            'confidence' => ['nullable', 'numeric', 'min:0', 'max:1'],
            'evidence' => ['nullable', 'string'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        $relationship->update($data);

        $this->logService->info('Contact relationship updated', [
            'channel' => 'contact',
            'type' => 'relationship_update',
            'related_id' => $contact->id,
            'related_type' => Contact::class,
            'user_id' => $request->user()?->id,
        ]);

        return response()->json(['data' => $relationship->load('targetContact')]);
    }

    /**
     * Delete a relationship.
     */
    public function destroy(Contact $contact, $relationshipId)
    {
        $relationship = ContactRelationship::where('source_contact_id', $contact->id)
            ->findOrFail($relationshipId);
        $relationship->delete();

        $this->logService->info('Contact relationship deleted', [
            'channel' => 'contact',
            'type' => 'relationship_delete',
            'related_id' => $contact->id,
            'related_type' => Contact::class,
        ]);

        return response()->json(['message' => 'Relationship deleted']);
    }
}