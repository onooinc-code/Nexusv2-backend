<?php

namespace App\Http\Controllers;

use App\Events\MessageSent;
use App\Jobs\ProcessAiInferenceJob;
use App\Models\AIModel;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Http\Request;

class ConversationController extends Controller
{
    public function index()
    {
        return response()->json(['data' => []]);
    }

    public function store(Request $request)
    {
        return response()->json(['message' => 'conversation created'], 201);
    }

    public function show($id)
    {
        return response()->json(['data' => ['id' => $id]]);
    }

    public function update(Request $request, $id)
    {
        return response()->json(['message' => 'conversation updated', 'id' => $id]);
    }

    public function destroy($id)
    {
        return response()->json(['message' => 'conversation deleted', 'id' => $id]);
    }

    public function getMessages($id)
    {
        return response()->json([
            'data' => [
                'conversation_id' => $id,
                'messages' => [
                    [
                        'id' => 1,
                        'conversation_id' => $id,
                        'sender' => 'user',
                        'channel' => 'chat',
                        'thread_id' => null,
                        'content' => 'This is a sample message',
                        'metadata' => ['source' => 'conversation_api'],
                        'created_at' => now()->toDateTimeString(),
                    ],
                ],
            ],
        ]);
    }

    public function sendMessage(Request $request, $id)
    {
        $validated = $request->validate([
            'sender' => 'sometimes|string',
            'content' => 'required|string',
            'provider' => 'sometimes|string',
            'model_id' => 'sometimes|integer',
            'channel' => 'sometimes|string',
            'thread_id' => 'sometimes|string',
            'metadata' => 'sometimes|array',
        ]);

        $conversation = Conversation::findOrFail($id);
        $senderRole = $validated['sender'] ?? 'user';
        $senderName = $request->user()?->name ?? ($validated['sender'] ?? 'User');

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender' => $senderRole,
            'sender_name' => $senderName,
            'channel' => $validated['channel'] ?? 'chat',
            'thread_id' => $validated['thread_id'] ?? null,
            'content' => $validated['content'],
            'metadata' => $validated['metadata'] ?? [],
            'status' => 'pending',
        ]);

        $selectedProvider = $validated['provider'] ?? config('services.ai.default_provider', 'google_gemini');
        $selectedModelId = $validated['model_id'] ?? AIModel::where('provider', $selectedProvider)
            ->where('status', 'active')
            ->value('id');

        if (! $selectedModelId) {
            return response()->json(['message' => 'No active AI model available for the selected provider.'], 422);
        }

        ProcessAiInferenceJob::dispatch(
            $conversation->id,
            $message->id,
            $validated['content'],
            $selectedModelId,
            $selectedProvider
        );

        event(new MessageSent(
            $conversation->id,
            $message->id,
            $senderRole,
            $senderName,
            $validated['content'],
            $validated['channel'] ?? 'chat',
            $validated['thread_id'] ?? null,
            $validated['metadata'] ?? []
        ));

        return response()->json([
            'message' => 'message queued',
            'data' => [
                'conversation_id' => $conversation->id,
                'id' => $message->id,
                'sender' => $message->sender,
                'sender_name' => $message->sender_name,
                'channel' => $message->channel,
                'thread_id' => $message->thread_id,
                'content' => $message->content,
                'metadata' => $message->metadata,
                'status' => $message->status,
                'created_at' => $message->created_at?->toDateTimeString(),
            ],
        ], 202);
    }
}
