<?php

namespace App\Services\Workflows;

use App\Models\WorkflowExecution;
use App\Models\WorkflowStepLog;
use App\Services\LogService;

class WorkflowCompensationEngine
{
    public function __construct(
        protected LogService $logService,
        protected WorkflowTaskDispatcher $dispatcher
    ) {}

    public function compensate(WorkflowExecution $execution): void
    {
        // Get all completed steps in reverse order
        $completedSteps = $execution->stepLogs()
            ->where('status', 'completed')
            ->orderBy('completed_at', 'desc')
            ->get();

        foreach ($completedSteps as $stepLog) {
            $this->compensateStep($execution, $stepLog);
        }
        
        $execution->update([
            'status' => 'compensated',
        ]);
        
        $this->logService->info('Workflow compensation completed', [
            'execution_id' => $execution->id,
            'workflow_id' => $execution->workflow_id,
        ]);
    }

    protected function compensateStep(WorkflowExecution $execution, WorkflowStepLog $stepLog): void
    {
        $version = $execution->version;
        if (!$version) {
            return;
        }

        $workflowDef = is_array($version->definition) ? $version->definition : json_decode(json_encode($version->definition), true) ?? [];
        $steps = $workflowDef['steps'] ?? [];
        
        $stepDef = collect($steps)->firstWhere('id', $stepLog->step_id);

        if (!$stepDef || empty($stepDef['compensation'])) {
            // No compensation defined for this step
            return;
        }

        try {
            $this->logService->info('Compensating workflow step', [
                'execution_id' => $execution->id,
                'step_id' => $stepLog->step_id,
                'compensation' => $stepDef['compensation']
            ]);

            $compDef = $stepDef['compensation'];
            // If action is set inside compensation
            if (!isset($compDef['type'])) {
                $compDef['type'] = 'action';
            }
            if (!isset($compDef['id'])) {
                $compDef['id'] = $stepLog->step_id . '_compensation';
            }
            if (!isset($compDef['name'])) {
                $compDef['name'] = 'Compensate ' . $stepLog->step_name;
            }

            // Re-use the dispatcher which runs step definitions
            $this->dispatcher->dispatch($execution, $compDef, $stepLog->output ?? []);

        } catch (\Exception $e) {
            $this->logService->error('Failed to compensate workflow step', [
                'execution_id' => $execution->id,
                'step_id' => $stepLog->step_id,
                'error' => $e->getMessage()
            ]);
        }
    }
}
