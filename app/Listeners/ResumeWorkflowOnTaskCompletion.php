<?php

namespace App\Listeners;

use App\Events\TaskCompletedEvent;
use App\Events\TaskFailedEvent;
use App\Models\WorkflowExecution;
use App\Services\LogService;
use App\Services\Workflows\WorkflowStateManager;
use App\Jobs\ExecuteWorkflowJob;
use Illuminate\Contracts\Queue\ShouldQueue;

class ResumeWorkflowOnTaskCompletion implements ShouldQueue
{
    public function __construct(
        protected WorkflowStateManager $stateManager,
        protected LogService $logService
    ) {}

    public function handle($event): void
    {
        $task = $event->task ?? null;
        if (!$task || empty($task->payload_data['workflow_execution_id'])) {
            return;
        }

        $executionId = $task->payload_data['workflow_execution_id'];

        $execution = WorkflowExecution::find($executionId);
        if (!$execution || $execution->status !== WorkflowExecution::STATUS_PAUSED) {
            return;
        }

        $runtimeState = $execution->runtime_state ?? [];
        $waitingFor = $runtimeState['waiting_for'] ?? [];

        if (($waitingFor['type'] ?? '') !== 'task' || ($waitingFor['task_id'] ?? null) != $task->id) {
            return;
        }

        $isSuccess = $event instanceof TaskCompletedEvent;

        $payload = [
            'task_result' => [
                'success' => $isSuccess,
                'output' => $task->result_data ?? [],
                'error' => $isSuccess ? null : 'Task failed or cancelled',
            ]
        ];

        $this->stateManager->mergeResumePayload($execution, $payload);
        
        $this->logService->info('Resuming workflow after task completion', [
            'execution_id' => $execution->id,
            'task_id' => $task->id,
            'success' => $isSuccess
        ]);

        ExecuteWorkflowJob::dispatch($execution->id);
    }
}
