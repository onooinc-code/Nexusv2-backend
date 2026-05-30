<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\ContactIdentifier;
use App\Services\ContactHubService;
use App\Services\LogService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Facades\Cache;

class ContactController extends Controller
{
    public function __construct(
        protected ContactHubService $contactHubService,
        protected LogService $logService
    ) {
    }

    public function index(Request $request)
    {
        $query = Contact::query();

        if ($request->filled('search')) {
            $query->search($request->query('search'));
        }

        if ($request->filled('type')) {
            $query->ofType($request->query('type'));
        }

        if ($request->has('is_active')) {
            $query->where('is_active', filter_var($request->query('is_active'), FILTER_VALIDATE_BOOLEAN));
        }

        $contacts = $query->orderBy('name')
            ->paginate($request->integer('per_page', 20));

        return response()->json(['data' => $contacts]);
    }

    public function store(Request $request)
    {
        // Idempotency: if X-Idempotency-Key present, return previously created contact
        $idempotencyKey = $request->header('X-Idempotency-Key');
        if ($idempotencyKey) {
            $cacheKey = "contacts:idempotency:{$idempotencyKey}";
            if (Cache::has($cacheKey)) {
                $existingId = Cache::get($cacheKey);
                $existing = Contact::find($existingId);
                if ($existing) {
                    return response()->json(['data' => $existing], 200);
                }
            }
        }

        $data = $request->validate([
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'phone' => ['nullable', 'string', 'max:32'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'emails' => ['nullable', 'array'],
            'emails.*' => ['nullable', 'email', 'max:255'],
            'phones' => ['nullable', 'array'],
            'phones.*' => ['nullable', 'string', 'max:32'],
            'external_ids' => ['nullable', 'array'],
            'external_ids.*' => ['nullable', 'string', 'max:255'],
            'type' => ['nullable', Rule::in(Contact::getAvailableTypes())],
            'title' => ['nullable', 'string', 'max:255'],
            'company' => ['nullable', 'string', 'max:255'],
            'avatar_url' => ['nullable', 'url', 'max:2048'],
            'metadata' => ['nullable', 'array'],
            'attributes' => ['nullable', 'array'],
            'is_active' => ['nullable', 'boolean'],
            'last_seen_at' => ['nullable', 'date'],
            'identifiers' => ['nullable', 'array'],
            'identifiers.*.type' => ['required_with:identifiers', Rule::in(ContactIdentifier::TYPES)],
            'identifiers.*.value' => ['required_with:identifiers', 'string', 'max:255'],
            'identifiers.*.trusted' => ['nullable', 'boolean'],
        ]);

        $identifierCandidates = collect();

        if (!empty($data['email'])) {
            $identifierCandidates->push(['type' => ContactIdentifier::TYPE_EMAIL, 'value' => $data['email'], 'trusted' => true]);
        }

        if (!empty($data['phone'])) {
            $identifierCandidates->push(['type' => ContactIdentifier::TYPE_PHONE, 'value' => $data['phone'], 'trusted' => true]);
        }

        foreach ($data['emails'] ?? [] as $email) {
            if ($email) {
                $identifierCandidates->push(['type' => ContactIdentifier::TYPE_EMAIL, 'value' => $email, 'trusted' => true]);
            }
        }

        foreach ($data['phones'] ?? [] as $phone) {
            if ($phone) {
                $identifierCandidates->push(['type' => ContactIdentifier::TYPE_PHONE, 'value' => $phone, 'trusted' => true]);
            }
        }

        foreach ($data['external_ids'] ?? [] as $externalId) {
            if ($externalId) {
                $identifierCandidates->push(['type' => ContactIdentifier::TYPE_EXTERNAL_ID, 'value' => $externalId, 'trusted' => true]);
            }
        }

        foreach ($request->input('identifiers', []) as $identifier) {
            if (isset($identifier['type'], $identifier['value'])) {
                $identifierCandidates->push([
                    'type' => $identifier['type'],
                    'value' => $identifier['value'],
                    'trusted' => $identifier['trusted'] ?? true,
                ]);
            }
        }

        $identifierCandidates = $identifierCandidates->unique(function ($item) {
            return $item['type'] . ':' . ContactIdentifier::normalize($item['type'], $item['value']);
        })->values();

        $existingContact = Contact::findByIdentifiers($identifierCandidates->toArray());

        $contactAttributes = array_filter([
            'user_id' => $data['user_id'] ?? null,
            'phone' => $data['phone'] ?? null,
            'name' => $data['name'],
            'email' => $data['email'] ?? null,
            'type' => $data['type'] ?? Contact::TYPE_CONTACT,
            'title' => $data['title'] ?? null,
            'company' => $data['company'] ?? null,
            'avatar_url' => $data['avatar_url'] ?? null,
            'metadata' => $data['metadata'] ?? [],
            'attributes' => $data['attributes'] ?? [],
            'is_active' => $data['is_active'] ?? true,
            'last_seen_at' => $data['last_seen_at'] ?? null,
            'canonical_name' => strtolower(trim($data['name'])),
        ], fn ($value) => $value !== null && $value !== '');

        if ($existingContact) {
            $existingContact->update($contactAttributes);
            $contact = $existingContact;
        } else {
            $contact = Contact::create(array_merge($contactAttributes, ['uuid' => Contact::generateUuid()]));
        }

        if ($identifierCandidates->isNotEmpty()) {
            $this->syncContactIdentifiers($contact, $identifierCandidates->toArray());
        }

        $this->contactHubService->syncContactDetails($contact);

        $this->logService->info('Contact created', [
            'channel' => 'contact',
            'type' => 'create',
            'related_id' => $contact->id,
            'related_type' => Contact::class,
            'user_id' => $request->user()?->id,
        ]);

        if (!empty($cacheKey) && isset($contact)) {
            Cache::put($cacheKey, $contact->id, 300);
        }

        try {
            event(new \App\Events\ContactCreated($contact));
        } catch (\Throwable $e) {
            // don't break flow if no listeners
        }

        if (!$existingContact) {
            \App\Models\NotificationLog::create([
                'contact_id' => $contact->id,
                'channel' => 'system',
                'recipient' => 'system',
                'subject' => 'Contact Registered',
                'body' => "Initial profile configuration completed.",
                'status' => 'completed',
            ]);
        }

        $status = $existingContact ? 200 : 201;

        return response()->json(['data' => $contact], $status);
    }

    protected function syncContactIdentifiers(Contact $contact, array $identifiers): void
    {
        foreach ($identifiers as $identifier) {
            if (empty($identifier['type']) || empty($identifier['value'])) {
                continue;
            }

            $normalized = ContactIdentifier::normalize($identifier['type'], $identifier['value']);
            $exists = $contact->identifiers()
                ->where('type', $identifier['type'])
                ->where('value', $normalized)
                ->exists();

            if (!$exists) {
                $contact->identifiers()->create([
                    'type' => $identifier['type'],
                    'value' => $normalized,
                    'trusted' => $identifier['trusted'] ?? true,
                ]);
            }
        }
    }

    public function show($id)
    {
        $contact = Contact::with([
            'conversations',
            'notes',
            'tags',
            'rules',
            'customFields',
            'memories',
            'identifiers',
            'relationships.relatedContact',
            'preferences',
            'aliases',
        ])->findOrFail($id);

        return response()->json(['data' => $contact]);
    }

    public function update(Request $request, $id)
    {
        $contact = Contact::findOrFail($id);

        $data = $request->validate([
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'phone' => ['nullable', 'string', 'max:32'],
            'name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'type' => ['nullable', Rule::in(Contact::getAvailableTypes())],
            'title' => ['nullable', 'string', 'max:255'],
            'company' => ['nullable', 'string', 'max:255'],
            'avatar_url' => ['nullable', 'url', 'max:2048'],
            'metadata' => ['nullable', 'array'],
            'attributes' => ['nullable', 'array'],
            'is_active' => ['nullable', 'boolean'],
            'last_seen_at' => ['nullable', 'date'],
        ]);

        $contact->update($data);
        $this->contactHubService->syncContactDetails($contact);

        $this->logService->info('Contact updated', [
            'channel' => 'contact',
            'type' => 'update',
            'related_id' => $contact->id,
            'related_type' => Contact::class,
            'user_id' => $request->user()?->id,
        ]);

        try {
            event(new \App\Events\ContactUpdated($contact));
        } catch (\Throwable $e) {
        }

        return response()->json(['data' => $contact]);
    }

    public function merge(Request $request, $id)
    {
        $contact = Contact::findOrFail($id);

        $data = $request->validate([
            'source_contact_id' => ['required', 'integer', 'exists:contacts,id'],
            'strategy' => ['required', Rule::in(['prefer_new', 'prefer_trusted', 'manual'])],
        ]);

        $sourceContact = Contact::findOrFail($data['source_contact_id']);

        $merged = $this->contactHubService->mergeContacts($contact, $sourceContact, $data['strategy']);

        return response()->json(['data' => $merged]);
    }

    public function erase($id)
    {
        $contact = Contact::findOrFail($id);

        $this->contactHubService->eraseContact($contact);

        $this->logService->info('Contact erased', [
            'channel' => 'contact',
            'type' => 'erase',
            'related_id' => $contact->id,
            'related_type' => Contact::class,
            'user_id' => request()->user()?->id,
        ]);

        return response()->json(['message' => 'contact erased', 'id' => $id]);
    }

    public function enrich(Request $request, $id)
    {
        $contact = Contact::findOrFail($id);

        $data = $request->validate([
            'profile_data' => ['required', 'array'],
            'source' => ['nullable', 'string', 'max:255'],
        ]);

        $updated = $this->contactHubService->enrichContact($contact, $data['profile_data'], $data['source'] ?? null);

        return response()->json(['data' => $updated]);
    }

    public function destroy($id)
    {
        $contact = Contact::findOrFail($id);

        $this->logService->info('Contact deleted', [
            'channel' => 'contact',
            'type' => 'delete',
            'related_id' => $contact->id,
            'related_type' => Contact::class,
            'user_id' => request()->user()?->id,
        ]);

        $contact->delete();

        try {
            event(new \App\Events\ContactDeleted($contact));
        } catch (\Throwable $e) {
        }

        return response()->json(['message' => 'contact deleted', 'id' => $id]);
    }

    public function getMemory($id)
    {
        $contact = Contact::with('memories')->findOrFail($id);

        return response()->json(['data' => ['contact_id' => $id, 'memories' => $contact->memories]]);
    }

    public function getRules($id)
    {
        $contact = Contact::with('rules')->findOrFail($id);

        return response()->json(['data' => ['contact_id' => $id, 'rules' => $contact->rules]]);
    }

    public function getAnalytics(Request $request, $id)
    {
        $contact = Contact::findOrFail($id);
        $days = max(1, (int) $request->query('days', 7));
        $analytics = $this->contactHubService->getContactAnalyticsWithOptions($contact, $days);

        return response()->json(['data' => ['contact_id' => $id, 'analytics' => $analytics]]);
    }

    public function import(Request $request)
    {
        $payload = [];

        if ($request->has('contacts') && is_array($request->input('contacts'))) {
            $payload = $request->input('contacts');
        } elseif ($request->hasFile('file') && $request->file('file')->isValid()) {
            $payload = $this->parseCsv($request->file('file')->getRealPath());
        } else {
            abort(422, 'Provide a contacts array or an uploaded CSV file.');
        }

        $created = 0;
        foreach ($payload as $row) {
            $data = $this->normalizeImportRow($row);
            $contact = Contact::create(array_merge($data, ['uuid' => Contact::generateUuid(), 'type' => $data['type'] ?? Contact::TYPE_CONTACT]));
            $this->contactHubService->syncContactDetails($contact);
            $created++;
        }

        $this->logService->info('Contacts imported', [
            'channel' => 'contact',
            'type' => 'import',
            'user_id' => $request->user()?->id,
            'context' => ['count' => $created],
        ]);

        return response()->json(['message' => 'Contacts imported successfully', 'created' => $created]);
    }

    public function export(Request $request)
    {
        $contacts = Contact::orderBy('name')->get();
        $rows = [];
        $rows[] = ['uuid', 'name', 'email', 'phone', 'type', 'title', 'company', 'avatar_url', 'is_active', 'last_seen_at'];

        foreach ($contacts as $contact) {
            $rows[] = [
                $contact->uuid,
                $contact->name,
                $contact->email,
                $contact->phone,
                $contact->type,
                $contact->title,
                $contact->company,
                $contact->avatar_url,
                $contact->is_active ? '1' : '0',
                optional($contact->last_seen_at)->toDateTimeString(),
            ];
        }

        $csv = '';
        foreach ($rows as $row) {
            $csv .= implode(',', array_map(fn ($item) => '"' . str_replace('"', '""', (string) ($item ?? '')) . '"', $row)) . "\n";
        }

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="contacts.csv"',
        ]);
    }

    protected function parseCsv(string $path): array
    {
        $rows = [];
        $handle = fopen($path, 'r');

        if ($handle === false) {
            return [];
        }

        $header = null;

        while (($data = fgetcsv($handle, 0, ',')) !== false) {
            if ($header === null) {
                $header = array_map('trim', $data);
                continue;
            }

            if (count($data) !== count($header)) {
                continue;
            }

            $rows[] = array_combine($header, $data);
        }

        fclose($handle);

        return $rows;
    }

    protected function normalizeImportRow(array $row): array
    {
        return [
            'name' => $row['name'] ?? $row['full_name'] ?? $row['contact_name'] ?? null,
            'email' => $row['email'] ?? null,
            'phone' => $row['phone'] ?? null,
            'type' => $row['type'] ?? null,
            'title' => $row['title'] ?? null,
            'company' => $row['company'] ?? null,
            'avatar_url' => $row['avatar_url'] ?? null,
            'metadata' => isset($row['metadata']) ? json_decode($row['metadata'], true) : null,
            'attributes' => isset($row['attributes']) ? json_decode($row['attributes'], true) : null,
            'is_active' => isset($row['is_active']) ? filter_var($row['is_active'], FILTER_VALIDATE_BOOLEAN) : true,
            'last_seen_at' => $row['last_seen_at'] ?? null,
        ];
    }

    public function timeline(Request $request, $id)
    {
        $contact = Contact::findOrFail($id);

        $events = collect();

        // 1. Fetch Notification Logs
        $logs = $contact->notificationLogs()->orderBy('created_at', 'desc')->take(50)->get();
        foreach ($logs as $log) {
            $events->push([
                'id' => 'log_' . $log->id,
                'type' => $log->channel === 'email' ? 'email' : ($log->channel === 'sms' ? 'call' : 'task'),
                'title' => $log->subject ?? ('Notification via ' . $log->channel),
                'description' => $log->body ?? 'System notification triggered.',
                'date' => $log->created_at->toIso8601String(),
                'status' => $log->status === 'sent' || $log->status === 'delivered' ? 'completed' : 'pending',
                'source' => 'notification',
            ]);
        }

        // 2. Fetch Memories
        $memories = $contact->memories()->orderBy('created_at', 'desc')->take(50)->get();
        foreach ($memories as $memory) {
            $events->push([
                'id' => 'mem_' . $memory->id,
                'type' => 'meeting',
                'title' => 'Memory Recorded',
                'description' => substr($memory->content, 0, 100) . (strlen($memory->content) > 100 ? '...' : ''),
                'date' => $memory->created_at->toIso8601String(),
                'status' => 'completed',
                'source' => 'memory',
            ]);
        }

        // 3. Fetch Contact Notes
        $notes = $contact->notes()->orderBy('created_at', 'desc')->take(50)->get();
        foreach ($notes as $note) {
            $events->push([
                'id' => 'note_' . $note->id,
                'type' => 'task',
                'title' => 'Note Added',
                'description' => $note->summary ?? substr($note->note, 0, 100) . (strlen($note->note) > 100 ? '...' : ''),
                'date' => $note->created_at->toIso8601String(),
                'status' => 'completed',
                'source' => 'note',
            ]);
        }

        // Sort by date descending
        $sortedEvents = $events->sortByDesc('date')->values()->all();

        return response()->json(['data' => $sortedEvents]);
    }
}
