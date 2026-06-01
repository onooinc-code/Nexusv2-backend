<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\ContactAnalysisRun;
use App\Models\ContactImportBatch;
use App\Models\ContactIdentifier;
use App\Models\ContactMemoryMaintenanceRun;
use App\Models\ContactMessage;
use App\Models\ContactMessageThread;
use App\Models\ContactReplyRule;
use App\Models\ContactTopic;
use App\Services\ContactHubService;
use App\Services\LogService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Facades\Cache;
use App\Http\Resources\ContactResource;
use Illuminate\Support\Str;
use App\Jobs\AnalyzeContactMessagesJob;
use App\Jobs\RunContactMemoryMaintenanceJob;
use App\Services\Contact\ContactMemoryMaintenancePipeline;

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

        return ContactResource::collection($contacts);
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
                    return response()->json(['data' => new ContactResource($existing)], 200);
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
            'identifiers.*.is_primary' => ['nullable', 'boolean'],
        ]);

        $identifierCandidates = collect();

        if (!empty($data['email'])) {
            $identifierCandidates->push(['type' => ContactIdentifier::TYPE_EMAIL, 'value' => $data['email'], 'is_primary' => true]);
        }

        if (!empty($data['phone'])) {
            $identifierCandidates->push(['type' => ContactIdentifier::TYPE_PHONE, 'value' => $data['phone'], 'is_primary' => true]);
        }

        foreach ($data['emails'] ?? [] as $email) {
            if ($email) {
                $identifierCandidates->push(['type' => ContactIdentifier::TYPE_EMAIL, 'value' => $email, 'is_primary' => false]);
            }
        }

        foreach ($data['phones'] ?? [] as $phone) {
            if ($phone) {
                $identifierCandidates->push(['type' => ContactIdentifier::TYPE_PHONE, 'value' => $phone, 'is_primary' => false]);
            }
        }

        foreach ($data['external_ids'] ?? [] as $externalId) {
            if ($externalId) {
                $identifierCandidates->push(['type' => ContactIdentifier::TYPE_EXTERNAL_ID, 'value' => $externalId, 'is_primary' => false]);
            }
        }

        foreach ($request->input('identifiers', []) as $identifier) {
            if (isset($identifier['type'], $identifier['value'])) {
                $identifierCandidates->push([
                    'type' => $identifier['type'],
                    'value' => $identifier['value'],
                    'is_primary' => $identifier['is_primary'] ?? false,
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

        return response()->json(['data' => new ContactResource($contact)], $status);
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
                try {
                    $contact->identifiers()->create([
                        'type'       => $identifier['type'],
                        'value'      => $normalized,
                        'is_primary' => $identifier['is_primary'] ?? false,
                    ]);
                } catch (\Illuminate\Database\QueryException $e) {
                    // Swallow unique-constraint violations: another contact already holds
                    // this identifier value. We skip rather than crash contact creation.
                    $this->logService->warning('Identifier already held by another contact; skipping.', [
                        'contact_id' => $contact->id,
                        'type'       => $identifier['type'],
                        'value'      => $normalized,
                        'error'      => $e->getMessage(),
                    ]);
                }
            }
        }
    }

    public function show($id)
    {
        $contact = Contact::with([
            'conversations',
            'notes',
            'tags',
            'replyRules',
            'customFields',
            'memories',
            'identifiers',
            'relationships.targetContact',
            'preferences',
            'aliases',
        ])->findOrFail($id);

        return response()->json(['data' => new ContactResource($contact)]);
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

        return response()->json(['data' => new ContactResource($contact)]);
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

        return response()->json(['data' => new ContactResource($merged)]);
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

        return response()->json(['data' => new ContactResource($updated)]);
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
        $contact = Contact::with('replyRules')->findOrFail($id);

        return response()->json(['data' => ['contact_id' => $id, 'rules' => $contact->replyRules]]);
    }

    public function getAnalytics(Request $request, $id)
    {
        $contact = Contact::findOrFail($id);
        $days = max(1, (int) $request->query('days', 7));
        
        $analytics = Cache::remember("contact_{$id}_analytics_days_{$days}", 300, function () use ($contact, $days) {
            return $this->contactHubService->getContactAnalyticsWithOptions($contact, $days);
        });

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

    public function messages(Request $request, $id)
    {
        Contact::findOrFail($id);

        $cacheKey = "contact_{$id}_messages_" . md5(json_encode($request->query()));
        $data = Cache::remember($cacheKey, 60, function () use ($request, $id) {
            return $this->filteredMessages($request, (int) $id)->paginate($request->integer('per_page', 25));
        });

        return response()->json([
            'data' => $data,
        ]);
    }

    public function whatsappMessages(Request $request, $id)
    {
        Contact::findOrFail($id);

        return response()->json([
            'data' => $this->filteredMessages($request, (int) $id)
                ->where('channel', 'whatsapp')
                ->paginate($request->integer('per_page', 25)),
        ]);
    }

    public function facebookMessages(Request $request, $id)
    {
        Contact::findOrFail($id);

        return response()->json([
            'data' => $this->filteredMessages($request, (int) $id)
                ->where('channel', 'facebook_messenger')
                ->paginate($request->integer('per_page', 25)),
        ]);
    }

    public function threads(Request $request, $id)
    {
        Contact::findOrFail($id);

        $threads = ContactMessageThread::query()
            ->withCount('messages')
            ->where('contact_id', $id)
            ->when($request->filled('source'), fn ($query) => $query->where('source', $request->query('source')))
            ->when($request->filled('channel'), fn ($query) => $query->where('channel', $request->query('channel')))
            ->orderByDesc('updated_at')
            ->paginate($request->integer('per_page', 20));

        return response()->json(['data' => $threads]);
    }

    public function showThread(Request $request, $id, $thread)
    {
        Contact::findOrFail($id);

        $threadModel = ContactMessageThread::query()
            ->where('contact_id', $id)
            ->findOrFail($thread);

        $messages = $this->filteredMessages($request, (int) $id)
            ->where('thread_id', $threadModel->id)
            ->paginate($request->integer('per_page', 25));

        return response()->json([
            'data' => [
                'thread' => $threadModel,
                'messages' => $messages,
            ],
        ]);
    }

    public function audit(Request $request, $id)
    {
        Contact::findOrFail($id);

        $events = \App\Models\ContactAuditEvent::query()
            ->where('contact_id', $id)
            ->orderByDesc('created_at')
            ->paginate($request->integer('per_page', 25));

        return response()->json(['data' => $events]);
    }

    public function exportBundle($id)
    {
        $contact = Contact::with([
            'identifiers',
            'aliases',
            'preferences',
            'relationships',
            'replyRules',
            'topics',
            'messages',
            'analysisFindings',
            'auditEvents',
        ])->findOrFail($id);

        return response()->json([
            'data' => [
                'exported_at' => now()->toISOString(),
                'schema_version' => 1,
                'contact' => new ContactResource($contact),
                'identifiers' => $contact->identifiers,
                'aliases' => $contact->aliases,
                'preferences' => $contact->preferences,
                'relationships' => $contact->relationships,
                'reply_rules' => $contact->replyRules,
                'topics' => $contact->topics,
                'messages' => $contact->messages,
                'analysis_findings' => $contact->analysisFindings,
                'audit_events' => $contact->auditEvents,
            ],
        ]);
    }

    public function listReplyRules($id)
    {
        Contact::findOrFail($id);

        return response()->json([
            'data' => ContactReplyRule::where('contact_id', $id)->orderByDesc('created_at')->get(),
        ]);
    }

    public function storeReplyRule(Request $request, $id)
    {
        Contact::findOrFail($id);

        $data = $request->validate([
            'rule' => ['required', 'string'],
            'is_active' => ['nullable', 'boolean'],
            'source_type' => ['nullable', 'string', 'max:255'],
            'source_id' => ['nullable', 'integer'],
        ]);

        $rule = ContactReplyRule::create(array_merge($data, [
            'contact_id' => $id,
            'is_active' => $data['is_active'] ?? true,
        ]));

        return response()->json(['data' => $rule], 201);
    }

    public function updateReplyRule(Request $request, $id, $rule)
    {
        Contact::findOrFail($id);

        $ruleModel = ContactReplyRule::where('contact_id', $id)->findOrFail($rule);
        $data = $request->validate([
            'rule' => ['sometimes', 'required', 'string'],
            'is_active' => ['sometimes', 'boolean'],
            'source_type' => ['nullable', 'string', 'max:255'],
            'source_id' => ['nullable', 'integer'],
        ]);

        $ruleModel->update($data);

        return response()->json(['data' => $ruleModel]);
    }

    public function destroyReplyRule($id, $rule)
    {
        Contact::findOrFail($id);

        $ruleModel = ContactReplyRule::where('contact_id', $id)->findOrFail($rule);
        $ruleModel->delete();

        return response()->json(['message' => 'reply rule deleted']);
    }

    public function topics($id)
    {
        Contact::findOrFail($id);

        return response()->json([
            'data' => ContactTopic::withCount('mentions')
                ->where('contact_id', $id)
                ->orderBy('topic')
                ->get(),
        ]);
    }

    public function intelligence($id)
    {
        $contact = Contact::with(['analysisFindings', 'topics', 'preferences', 'replyRules'])->findOrFail($id);

        return response()->json([
            'data' => [
                'contact_id' => (int) $id,
                'profile_confidence' => $contact->profile_confidence,
                'memory_freshness' => $contact->memory_freshness,
                'summary' => $contact->metadata['ai_summary'] ?? null,
                'findings' => $contact->analysisFindings,
                'topics' => $contact->topics,
                'preferences' => $contact->preferences,
                'reply_rules' => $contact->replyRules,
            ],
        ]);
    }

    public function persona($id)
    {
        $contact = Contact::findOrFail($id);

        return response()->json([
            'data' => [
                'contact_id' => (int) $id,
                'persona' => $contact->metadata['persona'] ?? null,
                'profile_confidence' => $contact->profile_confidence,
                'last_validated_at' => $contact->memory_freshness,
            ],
        ]);
    }

    public function talkSpecs($id)
    {
        $contact = Contact::with(['preferences', 'replyRules'])->findOrFail($id);

        return response()->json([
            'data' => [
                'contact_id' => (int) $id,
                'preferred_language' => $contact->metadata['preferred_language'] ?? null,
                'tone_guidance' => $contact->metadata['tone_guidance'] ?? null,
                'preferences' => $contact->preferences,
                'reply_rules' => $contact->replyRules,
            ],
        ]);
    }

    public function emotionalBaseline($id)
    {
        $contact = Contact::findOrFail($id);

        return response()->json([
            'data' => [
                'contact_id' => (int) $id,
                'baseline' => $contact->metadata['emotional_baseline'] ?? 'unknown',
                'source' => 'contact_metadata',
                'last_interaction_at' => $contact->last_interaction_at,
            ],
        ]);
    }

    public function createAnalysisRun(Request $request, $id)
    {
        Contact::findOrFail($id);

        $data = $request->validate([
            'options' => ['nullable', 'array'],
            'status' => ['nullable', Rule::in(['pending', 'queued', 'running', 'completed', 'failed'])],
        ]);

        $run = ContactAnalysisRun::create([
            'contact_id' => $id,
            'status' => 'queued',
            'options' => $data['options'] ?? [],
            'trace_id' => (string) Str::uuid(),
        ]);

        AnalyzeContactMessagesJob::dispatch($run);

        return response()->json(['data' => $run], 201);
    }

    public function listAnalysisRuns(Request $request, $id)
    {
        Contact::findOrFail($id);

        return response()->json([
            'data' => ContactAnalysisRun::with('findings')
                ->where('contact_id', $id)
                ->orderByDesc('created_at')
                ->paginate($request->integer('per_page', 20)),
        ]);
    }

    public function showAnalysisRun($id, $run)
    {
        Contact::findOrFail($id);

        return response()->json([
            'data' => ContactAnalysisRun::with('findings')
                ->where('contact_id', $id)
                ->findOrFail($run),
        ]);
    }

    public function batchAnalysisRun(Request $request)
    {
        $data = $request->validate([
            'contact_ids' => ['required', 'array', 'min:1'],
            'contact_ids.*' => ['integer', 'exists:contacts,id'],
            'options' => ['nullable', 'array'],
        ]);

        $runs = collect($data['contact_ids'])->map(function ($contactId) use ($data) {
            $run = ContactAnalysisRun::create([
                'contact_id' => $contactId,
                'status' => 'queued',
                'options' => $data['options'] ?? [],
                'trace_id' => (string) Str::uuid(),
            ]);

            // Dispatch job for each run so analysis actually executes
            AnalyzeContactMessagesJob::dispatch($run);

            return $run;
        });

        return response()->json(['data' => $runs], 201);
    }

    public function applyAnalysisRun($run)
    {
        $analysisRun = ContactAnalysisRun::with(['findings', 'contact'])->findOrFail($run);
        $contact = $analysisRun->contact;

        if (!$contact) {
            return response()->json(['error' => 'Contact not found for this analysis run.'], 422);
        }

        $meta = $contact->metadata ?? [];

        foreach ($analysisRun->findings as $finding) {
            $type = $finding->type ?? $finding->finding_type;

            switch ($type) {
                case 'topics':
                    $topics = is_array($finding->content) ? $finding->content : json_decode($finding->content, true);
                    if (is_array($topics)) {
                        foreach ($topics as $topicName) {
                            \App\Models\ContactTopic::updateOrCreate(
                                ['contact_id' => $contact->id, 'topic' => (string) $topicName],
                                ['mention_count' => \DB::raw('mention_count + 1')]
                            );
                        }
                    }
                    break;

                case 'persona':
                    $meta['persona'] = is_string($finding->content) ? $finding->content : json_encode($finding->content);
                    break;

                case 'emotional_baseline':
                    $meta['emotional_baseline'] = is_string($finding->content) ? $finding->content : json_encode($finding->content);
                    break;

                case 'suggested_rules':
                    $rules = is_array($finding->content) ? $finding->content : json_decode($finding->content, true);
                    if (is_array($rules)) {
                        foreach ($rules as $ruleText) {
                            \App\Models\ContactReplyRule::firstOrCreate(
                                ['contact_id' => $contact->id, 'rule' => (string) $ruleText],
                                ['is_active' => true]
                            );
                        }
                    }
                    break;
            }
        }

        $contact->update([
            'metadata' => $meta,
            'profile_confidence' => min(1.0, ($contact->profile_confidence ?? 0) + 0.1),
        ]);

        $analysisRun->update(['status' => 'completed', 'completed_at' => now()]);

        return response()->json(['data' => $analysisRun->fresh()]);
    }

    public function rollbackAnalysisRun($run)
    {
        $analysisRun = ContactAnalysisRun::findOrFail($run);
        $analysisRun->findings()->delete();
        $analysisRun->update(['status' => 'rolled_back']);

        return response()->json(['data' => $analysisRun]);
    }

    public function memoryMaintenance(Request $request, $id = null)
    {
        if ($id !== null) {
            Contact::findOrFail($id);
        }

        $data = $request->validate([
            'operation' => ['required', 'string', 'max:255'],
            'scope' => ['nullable', 'array'],
            'dry_run' => ['nullable', 'boolean'],
        ]);

        $isDryRun = (bool) ($data['dry_run'] ?? false);
        $scope = $data['scope'] ?? [];
        if ($id !== null) {
            $scope['contact_id'] = (int) $id;
        }

        $run = ContactMemoryMaintenanceRun::create([
            'operation' => $data['operation'],
            'scope'     => $scope,
            'status'    => $isDryRun ? 'dry_run' : 'queued',
            'results'   => [
                'message' => $isDryRun ? 'Dry run — no changes will be made.' : 'Maintenance queued for background processing.',
                'dry_run' => $isDryRun,
            ],
        ]);

        if ($isDryRun) {
            // Dry runs execute synchronously for immediate UI feedback
            app(ContactMemoryMaintenancePipeline::class)->process($run);
        } else {
            // Real operations are dispatched asynchronously so the HTTP response returns immediately
            RunContactMemoryMaintenanceJob::dispatch($run);
        }

        return response()->json(['data' => $run->fresh()], 201);
    }

    public function memoryMaintenanceRuns(Request $request)
    {
        return response()->json([
            'data' => ContactMemoryMaintenanceRun::orderByDesc('created_at')
                ->paginate($request->integer('per_page', 20)),
        ]);
    }

    public function showMemoryMaintenanceRun($run)
    {
        return response()->json(['data' => ContactMemoryMaintenanceRun::findOrFail($run)]);
    }

    protected function filteredMessages(Request $request, int $contactId)
    {
        return ContactMessage::query()
            ->with(['thread', 'importBatch', 'senderContact'])
            ->where('contact_id', $contactId)
            ->when($request->filled('source'), fn ($query) => $query->where('source', $request->query('source')))
            ->when($request->filled('channel'), fn ($query) => $query->where('channel', $request->query('channel')))
            ->when($request->filled('direction'), fn ($query) => $query->where('direction', $request->query('direction')))
            ->when($request->filled('language'), fn ($query) => $query->where('language', $request->query('language')))
            ->when($request->filled('sender'), function ($query) use ($request) {
                $sender = $request->query('sender');
                $query->where(function ($inner) use ($sender) {
                    $inner->where('sender_name', 'like', "%{$sender}%")
                        ->orWhere('sender_identifier', 'like', "%{$sender}%");
                });
            })
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->query('search');
                $query->where('body', 'like', "%{$search}%");
            })
            ->when($request->filled('date_from'), fn ($query) => $query->whereDate('source_timestamp', '>=', $request->query('date_from')))
            ->when($request->filled('date_to'), fn ($query) => $query->whereDate('source_timestamp', '<=', $request->query('date_to')))
            ->when($request->boolean('has_attachments'), fn ($query) => $query->whereNotNull('attachments_metadata'))
            ->orderBy('source_timestamp')
            ->orderBy('id');
    }
}
