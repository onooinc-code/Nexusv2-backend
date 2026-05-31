<?php

namespace App\Listeners;

use App\Events\WorkflowStarted;
use App\Services\LogService;

class LogWorkflowStarted
{
    public function __construct(protected LogService $logService) {}

    public function handle(WorkflowStarted $event): void
    {
        $this->logService->info('Workflow started', [
            'channel' => 'workflow',
            'type' => 'start',
            'related_id' => $event->workflowId,
            'related_type' => 'App\Models\Workflow',
            'user_id' => $event->userId ?: null,
            'context' => ['workflow_name' => $event->workflowName],
        ]);
    }
}
