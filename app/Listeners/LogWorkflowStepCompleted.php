<?php

namespace App\Listeners;

use App\Events\WorkflowStepCompleted;
use App\Services\LogService;

/**
 * LogWorkflowStepCompleted
 *
 * Automatically logs when a workflow step completes.
 */
class LogWorkflowStepCompleted
{
    /**
     * Create the event listener.
     */
    public function __construct(protected LogService $logService) {}

    /**
     * Handle the event.
     */
    public function handle(WorkflowStepCompleted $event): void
    {
        $this->logService->info('Workflow step completed', [
            'channel' => 'workflow',
            'type' => 'step',
            'related_id' => $event->workflowId,
            'related_type' => 'App\Models\Workflow',
            'context' => [
                'step_id' => $event->stepId,
                'step_title' => $event->stepTitle,
                'result' => $event->result,
                'metadata' => $event->metadata,
            ],
        ]);
    }
}
