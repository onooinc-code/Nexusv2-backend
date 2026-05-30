<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\ContactNote;
use App\Services\LogService;
use Illuminate\Http\Request;

class ContactNoteController extends Controller
{
    public function __construct(
        protected LogService $logService
    ) {}

    /**
     * List notes for a contact.
     */
    public function index(Contact $contact)
    {
        $notes = $contact->notes()->orderBy('created_at', 'desc')->get();
        return response()->json(['data' => $notes]);
    }

    /**
     * Store a new note for a contact.
     */
    public function store(Request $request, Contact $contact)
    {
        $data = $request->validate([
            'note' => ['required', 'string'],
            'summary' => ['nullable', 'string', 'max:255'],
            'is_pinned' => ['nullable', 'boolean'],
            'metadata' => ['nullable', 'array'],
        ]);

        $data['user_id'] = $request->user()?->id;

        $note = $contact->notes()->create($data);

        $this->logService->info('Contact note created', [
            'channel' => 'contact',
            'type' => 'note_create',
            'related_id' => $contact->id,
            'related_type' => Contact::class,
            'user_id' => $request->user()?->id,
        ]);

        return response()->json(['data' => $note], 201);
    }

    /**
     * Show a specific note.
     */
    public function show(Contact $contact, $noteId)
    {
        $note = $contact->notes()->findOrFail($noteId);
        return response()->json(['data' => $note]);
    }

    /**
     * Update a note.
     */
    public function update(Request $request, Contact $contact, $noteId)
    {
        $note = $contact->notes()->findOrFail($noteId);

        $data = $request->validate([
            'note' => ['sometimes', 'string'],
            'summary' => ['nullable', 'string', 'max:255'],
            'is_pinned' => ['nullable', 'boolean'],
            'metadata' => ['nullable', 'array'],
        ]);

        $note->update($data);

        $this->logService->info('Contact note updated', [
            'channel' => 'contact',
            'type' => 'note_update',
            'related_id' => $contact->id,
            'related_type' => Contact::class,
            'user_id' => $request->user()?->id,
        ]);

        return response()->json(['data' => $note]);
    }

    /**
     * Delete a note.
     */
    public function destroy(Contact $contact, $noteId)
    {
        $note = $contact->notes()->findOrFail($noteId);
        $note->delete();

        $this->logService->info('Contact note deleted', [
            'channel' => 'contact',
            'type' => 'note_delete',
            'related_id' => $contact->id,
            'related_type' => Contact::class,
        ]);

        return response()->json(['message' => 'Note deleted']);
    }
}
