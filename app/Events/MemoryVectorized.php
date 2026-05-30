<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

/**
 * MemoryVectorized Event - Broadcast when memory has been vectorized
 *
 * Fired after embedding vector is generated for a memory.
 * Allows UI to track memory processing progress.
 */
class MemoryVectorized extends Event implements ShouldBroadcast
{
    use InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $memoryId,
        public int $vectorDimension,
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
            'vectorized' => true,
            'dimensions' => $this->vectorDimension,
            'timestamp' => $this->timestamp->toDateTimeString(),
        ];
    }
}
