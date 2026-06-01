<?php

namespace App\Listeners;

use App\Events\TaskMovedToDLQEvent;
use App\Services\LogService;
use App\Services\DeadLetterQueueService;

class LogDeadLetterTask
{
    public function __construct(
        protected LogService $logService,
        protected DeadLetterQueueService $dlqService
    ) {}

    public function handle(TaskMovedToDLQEvent $event): void
    {
        $this->logService->error('Task moved to Dead Letter Queue', [
            'channel' => 'task',
            'type' => 'dead_letter',
            'related_id' => $event->task->id,
            'related_type' => get_class($event->task),
            'context' => [
                'error' => $event->exception->getMessage(),
                'trace' => $event->exception->getTraceAsString()
            ],
        ]);

        try {
            $this->dlqService->log($event->task, $event->exception);
        } catch (\Throwable $e) {
            $this->logService->error('Failed to save dead letter task record in listener', [
                'error' => $e->getMessage()
            ]);
        }
    }
}
