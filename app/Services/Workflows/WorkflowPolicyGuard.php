<?php

namespace App\Services\Workflows;

use App\Models\User;
use App\Models\Workflow;
use Illuminate\Validation\ValidationException;

class WorkflowPolicyGuard
{
    protected array $dangerousStepTypes = ['code'];

    public function assertCanManage(?User $user, Workflow $workflow): void
    {
        if ($workflow->owner_id && $user && (int) $workflow->owner_id !== (int) $user->id) {
            throw ValidationException::withMessages([
                'workflow' => 'You do not have permission to manage this workflow.',
            ]);
        }
    }

    public function assertCanDelete(Workflow $workflow): void
    {
        if ($workflow->is_system) {
            throw ValidationException::withMessages([
                'workflow' => 'System workflows cannot be deleted.',
            ]);
        }
    }

    public function assertCanExecute(?User $user, Workflow $workflow, array $definition): void
    {
        if (! $workflow->canExecute()) {
            throw ValidationException::withMessages([
                'workflow' => "Workflow cannot be executed in status: {$workflow->status}.",
            ]);
        }

        if ($workflow->owner_id && $user && (int) $workflow->owner_id !== (int) $user->id) {
            throw ValidationException::withMessages([
                'workflow' => 'You do not have permission to execute this workflow.',
            ]);
        }

        $allowCode = (bool) data_get($workflow->settings, 'allow_code_steps', false);
        foreach ($definition['steps'] ?? [] as $step) {
            $type = strtolower((string) ($step['type'] ?? $step['action'] ?? 'action'));
            if (in_array($type, $this->dangerousStepTypes, true) && ! $allowCode) {
                throw ValidationException::withMessages([
                    'steps' => 'Code steps are disabled unless workflow.settings.allow_code_steps is true.',
                ]);
            }
        }
    }
}
