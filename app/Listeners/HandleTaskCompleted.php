<?php

namespace App\Listeners;

use App\Events\TaskCompletedEvent;
use App\Services\LogService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

/**
 * Handle task completed events
 */
class HandleTaskCompleted implements ShouldQueue
{
    use InteractsWithQueue;

    protected LogService $logService;

    /**
     * Create the event listener.
     */
    public function __construct(LogService $logService)
    {
        $this->logService = $logService;
    }

    /**
     * Handle the event.
     */
    public function handle(TaskCompletedEvent $event): void
    {
        $task = $event->task;

        $this->logService->info('Task completed event handled', [
            'channel' => 'task',
            'type' => 'completed_event',
            'related_id' => $task->id,
            'related_type' => 'App\Models\AgentTask',
            'context' => ['title' => $task->title],
        ]);

        // TODO: Add any additional logic needed when a task completes
        // For example: trigger workflows, send notifications, update analytics, etc.
    }
}