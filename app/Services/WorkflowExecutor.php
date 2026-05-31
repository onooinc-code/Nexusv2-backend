<?php

namespace App\Services;

use App\Jobs\ExecuteWorkflowJob;
use App\Models\Workflow;
use App\Models\WorkflowExecution;
use App\Services\Workflows\WorkflowInterpreter;
use App\Services\Workflows\WorkflowPolicyGuard;
use App\Services\Workflows\WorkflowRegistry;
use App\Services\Workflows\WorkflowStateManager;
use App\Services\Workflows\WorkflowTaskDispatcher;

class WorkflowExecutor
{
    public function __construct(
        protected WorkflowRegistry $registry,
        protected WorkflowStateManager $stateManager,
        protected WorkflowPolicyGuard $policyGuard,
        protected WorkflowInterpreter $interpreter,
        protected WorkflowTaskDispatcher $dispatcher
    ) {}

    public function execute(Workflow $workflow, array $context = [], string $runMode = 'sync', ?\App\Models\User $user = null): array
    {
        $version = $this->registry->getExecutableVersion($workflow);
        $this->policyGuard->assertCanExecute($user, $workflow, $version->definition);

        $execution = $this->stateManager->createExecution(
            $workflow,
            $version,
            $user,
            $runMode,
            $context,
            'manual'
        );

        if ($runMode === 'async') {
            ExecuteWorkflowJob::dispatch($execution->id);

            return [
                'success' => true,
                'queued' => true,
                'execution_id' => $execution->id,
                'workflow_id' => $workflow->id,
                'status' => $execution->status,
            ];
        }

        $execution = $this->interpreter->run($execution);

        return [
            'success' => $execution->status === WorkflowExecution::STATUS_COMPLETED,
            'execution_id' => $execution->id,
            'workflow_id' => $workflow->id,
            'status' => $execution->status,
            'output' => $execution->output,
            'error' => $execution->error,
            'step_results' => $execution->stepLogs()->orderBy('created_at')->get()->toArray(),
        ];
    }

    public function getStepResults(): array
    {
        return [];
    }
}
