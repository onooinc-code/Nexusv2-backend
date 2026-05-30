<?php
namespace App\Listeners;
use App\Events\MemoryIndexed;
use Illuminate\Contracts\Queue\ShouldQueue;
class IndexMemory extends Listener implements ShouldQueue
{
    public bool $shouldQueue = true;
    public string $queue = 'memory';
    public int $timeout = 30;
    public function handle(MemoryIndexed $event): void
    { $this->log("Indexing {$event->memoryType} memory for {$event->contact->name}"); }
}
