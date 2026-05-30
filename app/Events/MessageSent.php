<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class MessageSent extends Event implements ShouldBroadcastNow
{
    use InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $conversationId,
        public string $messageId,
        public string $sender,
        public string $senderName,
        public string $content,
        public string $channel = 'chat',
        public ?string $threadId = null,
        public array $metadata = [],
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
            'id' => $this->messageId,
            'sender' => $this->sender,
            'sender_name' => $this->senderName,
            'channel' => $this->channel,
            'thread_id' => $this->threadId,
            'content' => $this->content,
            'metadata' => $this->metadata,
            'timestamp' => $this->timestamp->toDateTimeString(),
        ];
    }
}
