<?php

namespace App\Services\Workflows;

use App\Models\User;
use App\Models\Workflow;
use App\Models\WorkflowExecution;
use App\Models\WorkflowVersion;
use Illuminate\Support\Str;

class WorkflowStateManager
{
    public function createExecution(
        Workflow $workflow,
        WorkflowVersion $version,
        ?User $user,
        string $runMode,
        array $inputPayload,
        string $triggerSource = 'manual'
    ): WorkflowExecution {
        return WorkflowExecution::create([
            'id' => (string) Str::uuid(),
            'workflow_id' => $workflow->id,
            'workflow_version_id' => $version->id,
            'user_id' => $user?->id,
            'trigger_source' => $triggerSource,
            'run_mode' => $runMode,
            'status' => WorkflowExecution::STATUS_PENDING,
            'input_payload' => $inputPayload,
            'runtime_state' => [
                'current_step_index' => 0,
                'variables' => $inputPayload,
                'depth' => 0,
                'waiting_for' => null,
            ],
        ]);
    }

    public function markRunning(WorkflowExecution $execution): WorkflowExecution
    {
        $execution->update([
            'status' => WorkflowExecution::STATUS_RUNNING,
            'started_at' => $execution->started_at ?? now(),
            'paused_at' => null,
        ]);

        return $execution->fresh();
    }

    public function pause(WorkflowExecution $execution, array $state): WorkflowExecution
    {
        $execution->update([
            'status' => WorkflowExecution::STATUS_PAUSED,
            'runtime_state' => $state,
            'paused_at' => now(),
        ]);

        return $execution->fresh();
    }

    public function complete(WorkflowExecution $execution, array $output, array $state): WorkflowExecution
    {
        $execution->update([
            'status' => WorkflowExecution::STATUS_COMPLETED,
            'output' => $output,
            'runtime_state' => $state,
            'completed_at' => now(),
            'paused_at' => null,
        ]);

        return $execution->fresh();
    }

    public function fail(WorkflowExecution $execution, string $error, array $state = []): WorkflowExecution
    {
        $execution->update([
            'status' => WorkflowExecution::STATUS_FAILED,
            'error' => $error,
            'runtime_state' => $state ?: $execution->runtime_state,
            'completed_at' => now(),
            'paused_at' => null,
        ]);

        return $execution->fresh();
    }

    public function cancel(WorkflowExecution $execution): WorkflowExecution
    {
        $execution->update([
            'status' => WorkflowExecution::STATUS_CANCELLED,
            'cancelled_at' => now(),
        ]);

        return $execution->fresh();
    }

    public function mergeResumePayload(WorkflowExecution $execution, array $payload): WorkflowExecution
    {
        $state = $execution->runtime_state ?? [];
        $state['variables'] = array_merge($state['variables'] ?? [], $payload);
        $state['waiting_for'] = null;

        $execution->update([
            'runtime_state' => $state,
            'status' => WorkflowExecution::STATUS_PENDING,
            'paused_at' => null,
        ]);

        return $execution->fresh();
    }
}
