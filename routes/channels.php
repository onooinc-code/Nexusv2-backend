<?php

use App\Models\Conversation;
use App\Models\ConversationSession;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('session.{sessionId}', function (User $user, string $sessionId) {
    $session = ConversationSession::with('conversation.contact')->find($sessionId);

    if (! $session || ! $session->conversation) {
        return false;
    }

    return $user->can('subscribe', $session);
});

Broadcast::channel('conversation.{conversationId}', function (User $user, string $conversationId) {
    $conversation = Conversation::with('contact')->find($conversationId);

    return $conversation?->contact?->user_id === $user->id;
});

Broadcast::channel('presence.users.{conversationId}', function (User $user, string $conversationId) {
    $conversation = Conversation::with('contact')->find($conversationId);

    if (! $conversation || $conversation->contact?->user_id !== $user->id) {
        return false;
    }

    return [
        'id' => $user->id,
        'name' => $user->name,
        'avatar_url' => $user->avatar_url ?? null,
    ];
});

Broadcast::channel('job.batch.{batchId}', function (User $user, string $batchId) {
    return in_array($user->email, config('broadcasting.admin_emails', []), true);
});

Broadcast::channel('admin.dlq', function (User $user) {
    return in_array($user->email, config('broadcasting.admin_emails', []), true);
});
