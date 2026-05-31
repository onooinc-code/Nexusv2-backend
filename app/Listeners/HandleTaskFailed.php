<?php

namespace App\Listeners;

use App\Events\TaskFailedEvent;
use App\Services\LogService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

/**
 * Handle task failed events
 */
class HandleTaskFailed implements ShouldQueue
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
    public function handle(TaskFailedEvent $event): void
    {
        $task = $event->task;
        $error = $event->error;

        $this->logService->error('Task failed event handled', [
            'channel' => 'task',
            'type' => 'failed_event',
            'related_id' => $task->id,
            'related_type' => 'App\Models\AgentTask',
            'context' => ['title' => $task->title, 'error' => $error],
        ]);

        // TODO: Add any additional logic needed when a task fails
        // For example: trigger alerts, notify administrators, initiate retries, etc.
    }
}