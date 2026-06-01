<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\ContactImportBatch;
use App\Services\Contact\ContactImportPipeline;
use App\Events\ContactImportCompleted;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ContactImportController extends Controller
{
    public function __construct(protected ContactImportPipeline $importPipeline)
    {
    }

    public function preview(Request $request)
    {
        $data = $request->validate([
            'contact_id' => ['required', 'integer', 'exists:contacts,id'],
            'source' => ['required', Rule::in(['whatsapp', 'facebook'])],
            'format' => ['required', Rule::in(['txt', 'json'])],
            'content' => ['nullable', 'string'],
            'timezone' => ['nullable', 'string'],
            'file' => ['nullable', 'file'],
        ]);

        $contact = Contact::findOrFail($data['contact_id']);
        $content = $this->resolveContent($request, $data['content'] ?? null);

        $preview = $this->importPipeline->preview(
            $contact,
            $data['source'],
            $content,
            $data['format'],
            $data['timezone'] ?? 'UTC'
        );

        return response()->json(['data' => $preview]);
    }

    public function importWhatsApp(Request $request)
    {
        return $this->importMessages($request, 'whatsapp');
    }

    public function importFacebook(Request $request)
    {
        return $this->importMessages($request, 'facebook');
    }

    public function importWaha(Request $request)
    {
        $data = $request->validate([
            'contact_id' => ['required', 'integer', 'exists:contacts,id'],
            'session' => ['required', 'string'],
            'chat_id' => ['required', 'string'],
            'limit' => ['nullable', 'integer'],
        ]);

        $contact = Contact::findOrFail($data['contact_id']);
        
        $content = json_encode([
            'session' => $data['session'],
            'chatId' => $data['chat_id'],
            'limit' => $data['limit'] ?? 100
        ]);

        $result = $this->importPipeline->commit(
            $contact,
            'whatsapp_waha',
            $content,
            'api',
            'UTC'
        );

        if (! $result['success']) {
            return response()->json(['error' => $result['error']], 422);
        }

        event(new ContactImportCompleted($contact, $result['messages_imported'] ?? $result['batch']->messages()->count(), 'whatsapp_waha'));

        return response()->json(['data' => $result]);
    }

    protected function importMessages(Request $request, string $source)
    {
        $data = $request->validate([
            'contact_id' => ['required', 'integer', 'exists:contacts,id'],
            'format' => ['required', Rule::in(['txt', 'json'])],
            'content' => ['nullable', 'string'],
            'timezone' => ['nullable', 'string'],
            'file' => ['nullable', 'file'],
        ]);

        $contact = Contact::findOrFail($data['contact_id']);
        $content = $this->resolveContent($request, $data['content'] ?? null);

        $result = $this->importPipeline->commit(
            $contact,
            $source,
            $content,
            $data['format'],
            $data['timezone'] ?? 'UTC'
        );

        if (! $result['success']) {
            return response()->json(['error' => $result['error']], 422);
        }

        event(new ContactImportCompleted($contact, $result['messages_imported'] ?? clone $result['batch']->messages()->count(), $source));

        return response()->json(['data' => $result]);
    }

    public function listBatches(Request $request)
    {
        $query = ContactImportBatch::query();

        if ($request->filled('contact_id')) {
            $query->where('contact_id', $request->integer('contact_id'));
        }

        if ($request->filled('source')) {
            $query->where('source', $request->query('source'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        $batches = $query->with('messages')->orderBy('created_at', 'desc')
            ->paginate($request->integer('per_page', 20));

        return response()->json(['data' => $batches]);
    }

    public function showBatch($batchId)
    {
        $batch = ContactImportBatch::with('messages')->findOrFail($batchId);

        return response()->json(['data' => $batch]);
    }

    public function rollbackBatch($batchId)
    {
        $batch = ContactImportBatch::findOrFail($batchId);

        $result = $this->importPipeline->rollback($batch);

        if (! $result['success']) {
            return response()->json(['error' => $result['error']], 422);
        }

        return response()->json(['data' => $result]);
    }

    protected function resolveContent(Request $request, ?string $content): string
    {
        if ($request->hasFile('file') && $request->file('file')->isValid()) {
            return file_get_contents($request->file('file')->getRealPath());
        }

        if (!empty($content)) {
            return $content;
        }

        abort(422, 'Provide file content or raw content string.');
    }
}
