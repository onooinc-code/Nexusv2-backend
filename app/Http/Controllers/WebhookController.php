<?php

namespace App\Http\Controllers;

use App\Events\MessageSent;
use App\Jobs\ProcessAiInferenceJob;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function handleWahaWebhook(Request $request)
    {
        $validated = $request->validate([
            'conversation_id' => 'required|integer|exists:conversations,id',
            'message' => 'required|string',
            'sender' => 'sometimes|string',
            'channel' => 'sometimes|string',
            'metadata' => 'sometimes|array',
        ]);

        $conversation = Conversation::find($validated['conversation_id']);
        if (! $conversation) {
            return response()->json(['message' => 'Conversation not found'], 404);
        }

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender' => $validated['sender'] ?? 'webhook',
            'sender_name' => $validated['sender'] ?? 'Webhook',
            'channel' => $validated['channel'] ?? 'whatsapp',
            'thread_id' => null,
            'content' => $validated['message'],
            'metadata' => $validated['metadata'] ?? [],
            'status' => 'pending',
        ]);

        ProcessAiInferenceJob::dispatch(
            $conversation->id,
            $message->id,
            $validated['message'],
            $conversation->model_id ?? null,
            'webhook'
        );

        event(new MessageSent(
            $conversation->id,
            $message->id,
            $message->sender,
            $message->sender_name,
            $message->content,
            $message->channel,
            null,
            $message->metadata
        ));

        Log::info('WAHA webhook queued message for processing', ['conversation_id' => $conversation->id, 'message_id' => $message->id]);

        return response()->json([
            'message' => 'Webhook message queued for processing',
            'data' => ['conversation_id' => $conversation->id, 'message_id' => $message->id],
        ], 202);
    }
}
