<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

/**
 * MemoriesExtracted Event - Broadcast when memories extracted from conversation
 *
 * Fired after key memories/facts are extracted and stored.
 * Allows UI to track memory extraction progress.
 */
class MemoriesExtracted extends Event implements ShouldBroadcast
{
    use InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $conversationId,
        public int $extractedCount,
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
            'conversation_id' => $this->conversationId,
            'extracted_count' => $this->extractedCount,
            'timestamp' => $this->timestamp->toDateTimeString(),
        ];
    }
}
