<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\ContactPreference;
use App\Services\LogService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ContactPreferenceController extends Controller
{
    public function __construct(
        protected LogService $logService
    ) {}

    /**
     * List preferences for a contact.
     */
    public function index(Contact $contact)
    {
        $preferences = $contact->preferences()->orderBy('preference_type')->get();
        return response()->json(['data' => $preferences]);
    }

    /**
     * Store a new preference for a contact.
     */
    public function store(Request $request, Contact $contact)
    {
        $data = $request->validate([
            'preference_type' => ['required', 'string', 'max:255'],
            'value' => ['required', 'string', 'max:255'],
            'confidence' => ['nullable', 'numeric', 'min:0', 'max:1'],
            'inferred_from_count' => ['nullable', 'integer', 'min:0'],
        ]);

        $data['confidence'] = $data['confidence'] ?? 1.0;
        $data['inferred_from_count'] = $data['inferred_from_count'] ?? 0;

        $preference = $contact->preferences()->create($data);

        $this->logService->info('Contact preference created', [
            'channel' => 'contact',
            'type' => 'preference_create',
            'related_id' => $contact->id,
            'related_type' => Contact::class,
            'user_id' => $request->user()?->id,
        ]);

        return response()->json(['data' => $preference], 201);
    }

    /**
     * Show a specific preference.
     */
    public function show(Contact $contact, $preferenceId)
    {
        $preference = $contact->preferences()->findOrFail($preferenceId);
        return response()->json(['data' => $preference]);
    }

    /**
     * Update a preference.
     */
    public function update(Request $request, Contact $contact, $preferenceId)
    {
        $preference = $contact->preferences()->findOrFail($preferenceId);

        $data = $request->validate([
            'preference_type' => ['sometimes', 'string', 'max:255'],
            'value' => ['sometimes', 'string', 'max:255'],
            'confidence' => ['nullable', 'numeric', 'min:0', 'max:1'],
            'inferred_from_count' => ['nullable', 'integer', 'min:0'],
        ]);

        $preference->update($data);

        $this->logService->info('Contact preference updated', [
            'channel' => 'contact',
            'type' => 'preference_update',
            'related_id' => $contact->id,
            'related_type' => Contact::class,
            'user_id' => $request->user()?->id,
        ]);

        return response()->json(['data' => $preference]);
    }

    /**
     * Delete a preference.
     */
    public function destroy(Contact $contact, $preferenceId)
    {
        $preference = $contact->preferences()->findOrFail($preferenceId);
        $preference->delete();

        $this->logService->info('Contact preference deleted', [
            'channel' => 'contact',
            'type' => 'preference_delete',
            'related_id' => $contact->id,
            'related_type' => Contact::class,
        ]);

        return response()->json(['message' => 'Preference deleted']);
    }
}