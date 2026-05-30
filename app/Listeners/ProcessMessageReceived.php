<?php
namespace App\Listeners;
use App\Events\MessageReceived;
use App\Events\MemoryIndexed;
use Illuminate\Contracts\Queue\ShouldQueue;
class ProcessMessageReceived extends Listener implements ShouldQueue
{
    public bool $shouldQueue = true;
    public string $queue = 'messages';

    public function handle(MessageReceived $event): void
    {
        try {
            $this->log("Processing message received for conversation {$event->conversationId}");
            // Real-time chat messages are handled through broadcast events.
            // Additional memory extraction or indexing is now managed by background jobs.
        } catch (\Exception $e) {
            $this->log("Error processing message: " . $e->getMessage(), 'error');
            throw $e;
        }
    }
}
