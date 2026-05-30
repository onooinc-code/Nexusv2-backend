<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\NotificationLog;
use App\Models\NotificationTemplate;
use App\Models\Contact;
use App\Services\LogService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class NotificationController extends Controller
{
    public function __construct(
        protected LogService $logService
    ) {}

    /**
     * List notification templates.
     */
    public function indexTemplates(Request $request)
    {
        $templates = NotificationTemplate::orderBy('name')
            ->paginate($request->integer('per_page', 20));

        return response()->json(['data' => $templates]);
    }

    /**
     * Store a new notification template.
     */
    public function storeTemplate(Request $request)
    {
        $data = $request->validate([
            'key' => ['required', 'string', 'max:255', 'unique:notification_templates,key'],
            'name' => ['required', 'string', 'max:255'],
            'subject' => ['nullable', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'channels' => ['required', 'array'],
            'channels.*' => ['string', Rule::in(NotificationTemplate::CHANNELS)],
        ]);

        $template = NotificationTemplate::create($data);

        $this->logService->info('Notification template created', [
            'channel' => 'notification',
            'type' => 'template_create',
            'related_id' => $template->id,
            'related_type' => NotificationTemplate::class,
            'user_id' => $request->user()?->id,
        ]);

        return response()->json(['data' => $template], 201);
    }

    /**
     * Show a notification template.
     */
    public function showTemplate($id)
    {
        $template = NotificationTemplate::findOrFail($id);
        return response()->json(['data' => $template]);
    }

    /**
     * Update a notification template.
     */
    public function updateTemplate(Request $request, $id)
    {
        $template = NotificationTemplate::findOrFail($id);

        $data = $request->validate([
            'key' => ['sometimes', 'string', 'max:255', Rule::unique('notification_templates', 'key')->ignore($template->id)],
            'name' => ['sometimes', 'string', 'max:255'],
            'subject' => ['nullable', 'string', 'max:255'],
            'body' => ['sometimes', 'string'],
            'channels' => ['sometimes', 'array'],
            'channels.*' => ['string', Rule::in(NotificationTemplate::CHANNELS)],
        ]);

        $template->update($data);

        $this->logService->info('Notification template updated', [
            'channel' => 'notification',
            'type' => 'template_update',
            'related_id' => $template->id,
            'related_type' => NotificationTemplate::class,
            'user_id' => $request->user()?->id,
        ]);

        return response()->json(['data' => $template]);
    }

    /**
     * Delete a notification template.
     */
    public function destroyTemplate($id)
    {
        $template = NotificationTemplate::findOrFail($id);
        $template->delete();

        $this->logService->info('Notification template deleted', [
            'channel' => 'notification',
            'type' => 'template_delete',
            'related_id' => $id,
            'related_type' => NotificationTemplate::class,
        ]);

        return response()->json(['message' => 'Template deleted']);
    }

    /**
     * List notification logs.
     */
    public function indexLogs(Request $request)
    {
        $query = NotificationLog::with('contact');

        if ($request->filled('contact_id')) {
            $query->where('contact_id', $request->query('contact_id'));
        }

        if ($request->filled('channel')) {
            $query->where('channel', $request->query('channel'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        $logs = $query->orderBy('created_at', 'desc')
            ->paginate($request->integer('per_page', 20));

        return response()->json(['data' => $logs]);
    }

    /**
     * Send a notification.
     */
    public function send(Request $request)
    {
        $data = $request->validate([
            'contact_id' => ['nullable', 'exists:contacts,id'],
            'channel' => ['required', Rule::in(NotificationTemplate::CHANNELS)],
            'recipient' => ['required', 'string', 'max:255'],
            'template_key' => ['nullable', 'string', 'exists:notification_templates,key'],
            'subject' => ['nullable', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'payload' => ['nullable', 'array'],
        ]);

        $contact = $data['contact_id'] ? Contact::find($data['contact_id']) : null;

        // Render template if provided
        if ($data['template_key']) {
            $template = NotificationTemplate::where('key', $data['template_key'])->first();
            if ($template) {
                $variables = $data['payload'] ?? [];
                $data['body'] = $template->render($variables);
                if ($template->subject) {
                    $data['subject'] = $template->renderSubject($variables);
                }
            }
        }

        $log = NotificationLog::create([
            'contact_id' => $contact?->id,
            'channel' => $data['channel'],
            'recipient' => $data['recipient'],
            'template_key' => $data['template_key'],
            'subject' => $data['subject'] ?? null,
            'body' => $data['body'],
            'payload' => $data['payload'] ?? null,
            'status' => NotificationLog::STATUS_PENDING,
        ]);

        $this->logService->info('Notification queued for sending', [
            'channel' => 'notification',
            'type' => 'send',
            'related_id' => $log->id,
            'related_type' => NotificationLog::class,
            'user_id' => $request->user()?->id,
        ]);

        // Dispatch real-time event
        event(new \App\Events\NotificationCreated($log));

        // TODO: Dispatch notification job for actual sending
        // SendNotificationJob::dispatch($log);

        return response()->json(['data' => $log], 201);
    }

    /**
     * Retry a failed notification.
     */
    public function retry($id)
    {
        $log = NotificationLog::findOrFail($id);

        if (!$log->canRetry()) {
            return response()->json(['message' => 'Notification cannot be retried'], 422);
        }

        $log->update([
            'status' => NotificationLog::STATUS_PENDING,
            'error_message' => null,
        ]);

        $this->logService->info('Notification retry queued', [
            'channel' => 'notification',
            'type' => 'retry',
            'related_id' => $log->id,
            'related_type' => NotificationLog::class,
        ]);

        // TODO: Dispatch notification job for actual sending
        // SendNotificationJob::dispatch($log);

        return response()->json(['data' => $log]);
    }
}