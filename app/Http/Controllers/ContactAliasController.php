<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\ContactAlias;
use App\Services\LogService;
use Illuminate\Http\Request;

class ContactAliasController extends Controller
{
    public function __construct(
        protected LogService $logService
    ) {}

    /**
     * List aliases for a contact.
     */
    public function index(Contact $contact)
    {
        $aliases = $contact->aliases()->orderBy('name')->get();
        return response()->json(['data' => $aliases]);
    }

    /**
     * Store a new alias for a contact.
     */
    public function store(Request $request, Contact $contact)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $alias = $contact->aliases()->create($data);

        $this->logService->info('Contact alias created', [
            'channel' => 'contact',
            'type' => 'alias_create',
            'related_id' => $contact->id,
            'related_type' => Contact::class,
            'user_id' => $request->user()?->id,
        ]);

        return response()->json(['data' => $alias], 201);
    }

    /**
     * Show a specific alias.
     */
    public function show(Contact $contact, $aliasId)
    {
        $alias = $contact->aliases()->findOrFail($aliasId);
        return response()->json(['data' => $alias]);
    }

    /**
     * Update an alias.
     */
    public function update(Request $request, Contact $contact, $aliasId)
    {
        $alias = $contact->aliases()->findOrFail($aliasId);

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
        ]);

        $alias->update($data);

        $this->logService->info('Contact alias updated', [
            'channel' => 'contact',
            'type' => 'alias_update',
            'related_id' => $contact->id,
            'related_type' => Contact::class,
            'user_id' => $request->user()?->id,
        ]);

        return response()->json(['data' => $alias]);
    }

    /**
     * Delete an alias.
     */
    public function destroy(Contact $contact, $aliasId)
    {
        $alias = $contact->aliases()->findOrFail($aliasId);
        $alias->delete();

        $this->logService->info('Contact alias deleted', [
            'channel' => 'contact',
            'type' => 'alias_delete',
            'related_id' => $contact->id,
            'related_type' => Contact::class,
        ]);

        return response()->json(['message' => 'Alias deleted']);
    }
}