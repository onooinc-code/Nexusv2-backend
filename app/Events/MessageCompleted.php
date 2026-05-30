<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

/**
 * MessageCompleted Event - Broadcast when LLM message finishes streaming
 *
 * Fired when the complete response is ready.
 * Allows frontend to finalize the message display.
 */
class MessageCompleted extends Event implements ShouldBroadcastNow
{
    use InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $conversationId,
        public string $messageId,
        public string $finalMessage,
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
            'complete' => true,
            'final_message' => $this->finalMessage,
            'timestamp' => $this->timestamp->toDateTimeString(),
        ];
    }
}
