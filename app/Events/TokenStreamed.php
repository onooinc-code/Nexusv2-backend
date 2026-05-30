<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

/**
 * TokenStreamed Event - Broadcast individual tokens from LLM streaming
 *
 * Fired during token-by-token streaming of LLM responses.
 * Allows frontend to display text as it arrives in real-time.
 */
class TokenStreamed extends Event implements ShouldBroadcastNow
{
    use InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $conversationId,
        public string $messageId,
        public string $token,
    ) {
        parent::__construct();
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel("conversation.{$this->conversationId}");
    }

    public function broadcastWith(): array
    {
        return [
            'message_id' => $this->messageId,
            'token' => $this->token,
            'timestamp' => $this->timestamp->toDateTimeString(),
        ];
    }
}
