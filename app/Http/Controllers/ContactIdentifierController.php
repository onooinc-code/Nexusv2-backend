<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\ContactIdentifier;
use App\Services\LogService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ContactIdentifierController extends Controller
{
    public function __construct(
        protected LogService $logService
    ) {}

    /**
     * List identifiers for a contact.
     */
    public function index(Contact $contact)
    {
        $identifiers = $contact->identifiers()->orderBy('type')->get();
        return response()->json(['data' => $identifiers]);
    }

    /**
     * Store a new identifier for a contact.
     */
    public function store(Request $request, Contact $contact)
    {
        $data = $request->validate([
            'type' => ['required', Rule::in(ContactIdentifier::TYPES)],
            'value' => ['required', 'string', 'max:255'],
            'is_primary' => ['nullable', 'boolean'],
        ]);

        $data['value'] = ContactIdentifier::normalize($data['type'], $data['value']);

        $identifier = $contact->identifiers()->create($data);

        $this->logService->info('Contact identifier created', [
            'channel' => 'contact',
            'type' => 'identifier_create',
            'related_id' => $contact->id,
            'related_type' => Contact::class,
            'user_id' => $request->user()?->id,
        ]);

        return response()->json(['data' => $identifier], 201);
    }

    /**
     * Show a specific identifier.
     */
    public function show(Contact $contact, $identifierId)
    {
        $identifier = $contact->identifiers()->findOrFail($identifierId);
        return response()->json(['data' => $identifier]);
    }

    /**
     * Update an identifier.
     */
    public function update(Request $request, Contact $contact, $identifierId)
    {
        $identifier = $contact->identifiers()->findOrFail($identifierId);

        $data = $request->validate([
            'type' => ['sometimes', Rule::in(ContactIdentifier::TYPES)],
            'value' => ['sometimes', 'string', 'max:255'],
            'is_primary' => ['nullable', 'boolean'],
        ]);

        if (isset($data['value'])) {
            $data['value'] = ContactIdentifier::normalize(
                $data['type'] ?? $identifier->type,
                $data['value']
            );
        }

        $identifier->update($data);

        $this->logService->info('Contact identifier updated', [
            'channel' => 'contact',
            'type' => 'identifier_update',
            'related_id' => $contact->id,
            'related_type' => Contact::class,
            'user_id' => $request->user()?->id,
        ]);

        return response()->json(['data' => $identifier]);
    }

    /**
     * Delete an identifier.
     */
    public function destroy(Contact $contact, $identifierId)
    {
        $identifier = $contact->identifiers()->findOrFail($identifierId);
        $identifier->delete();

        $this->logService->info('Contact identifier deleted', [
            'channel' => 'contact',
            'type' => 'identifier_delete',
            'related_id' => $contact->id,
            'related_type' => Contact::class,
        ]);

        return response()->json(['message' => 'Identifier deleted']);
    }
}