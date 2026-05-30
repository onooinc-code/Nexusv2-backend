<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

/**
 * MemoryIndexed Event - Broadcast when memory is indexed in Pinecone
 *
 * Fired after vector is successfully stored in Pinecone.
 * Allows UI to mark memory as fully processed and searchable.
 */
class MemoryIndexed extends Event implements ShouldBroadcast
{
    use InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $memoryId,
        public string $pineconeId,
    ) {
        parent::__construct();
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel("memory.{$this->memoryId}");
    }

    public function broadcastWith(): array
    {
        return [
            'memory_id' => $this->memoryId,
            'indexed' => true,
            'pinecone_id' => $this->pineconeId,
            'timestamp' => $this->timestamp->toDateTimeString(),
        ];
    }
}
