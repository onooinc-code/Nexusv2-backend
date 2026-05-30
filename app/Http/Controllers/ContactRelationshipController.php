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
            ->with('relatedContact')
            ->orderBy('relationship_type')
            ->get();

        return response()->json(['data' => $relationships]);
    }

    /**
     * Store a new relationship.
     */
    public function store(Request $request, Contact $contact)
    {
        $data = $request->validate([
            'related_contact_id' => ['required', 'exists:contacts,id'],
            'relationship_type' => ['required', Rule::in(ContactRelationship::TYPES)],
            'mention_count' => ['nullable', 'integer', 'min:1'],
            'confidence' => ['nullable', 'numeric', 'min:0', 'max:1'],
        ]);

        $data['mention_count'] = $data['mention_count'] ?? 1;
        $data['confidence'] = $data['confidence'] ?? 1.0;

        $relationship = $contact->relationships()->create($data);

        $this->logService->info('Contact relationship created', [
            'channel' => 'contact',
            'type' => 'relationship_create',
            'related_id' => $contact->id,
            'related_type' => Contact::class,
            'user_id' => $request->user()?->id,
        ]);

        return response()->json(['data' => $relationship->load('relatedContact')], 201);
    }

    /**
     * Show a specific relationship.
     */
    public function show(Contact $contact, $relationshipId)
    {
        $relationship = $contact->relationships()
            ->with('relatedContact')
            ->findOrFail($relationshipId);

        return response()->json(['data' => $relationship]);
    }

    /**
     * Update a relationship.
     */
    public function update(Request $request, Contact $contact, $relationshipId)
    {
        $relationship = $contact->relationships()->findOrFail($relationshipId);

        $data = $request->validate([
            'related_contact_id' => ['sometimes', 'exists:contacts,id'],
            'relationship_type' => ['sometimes', Rule::in(ContactRelationship::TYPES)],
            'mention_count' => ['nullable', 'integer', 'min:1'],
            'confidence' => ['nullable', 'numeric', 'min:0', 'max:1'],
        ]);

        $relationship->update($data);

        $this->logService->info('Contact relationship updated', [
            'channel' => 'contact',
            'type' => 'relationship_update',
            'related_id' => $contact->id,
            'related_type' => Contact::class,
            'user_id' => $request->user()?->id,
        ]);

        return response()->json(['data' => $relationship->load('relatedContact')]);
    }

    /**
     * Delete a relationship.
     */
    public function destroy(Contact $contact, $relationshipId)
    {
        $relationship = $contact->relationships()->findOrFail($relationshipId);
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