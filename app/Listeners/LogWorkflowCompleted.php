<?php

namespace App\Listeners;

use App\Events\WorkflowCompleted;
use App\Services\LogService;

/**
 * LogWorkflowCompleted
 *
 * Automatically logs when a workflow completes.
 */
class LogWorkflowCompleted
{
    /**
     * Create the event listener.
     */
    public function __construct(protected LogService $logService) {}

    /**
     * Handle the event.
     */
    public function handle(WorkflowCompleted $event): void
    {
        $this->logService->info('Workflow completed', [
            'channel' => 'workflow',
            'type' => 'complete',
            'related_id' => $event->workflowId,
            'related_type' => 'App\Models\Workflow',
            'context' => [
                'result' => $event->result,
                'metadata' => $event->metadata,
            ],
        ]);
    }
}
