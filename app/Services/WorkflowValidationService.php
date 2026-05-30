<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class WorkflowValidationService
{
    protected array $requiredStepFields = ['name', 'action'];
    protected array $validActions = ['process', 'delay', 'log', 'condition', 'agent'];

    public function validateWorkflow(array $workflowData): array
    {
        $errors = [];

        if (empty($workflowData['name'])) {
            $errors[] = 'Workflow name is required';
        }

        if (empty($workflowData['steps']) || !is_array($workflowData['steps'])) {
            $errors[] = 'Workflow must have at least one step';
        } else {
            $stepErrors = $this->validateSteps($workflowData['steps']);
            $errors = array_merge($errors, $stepErrors);
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    public function validateStep(array $step, array $context = []): array
    {
        $errors = [];

        if (empty($step['name']) && empty($step['title'])) {
            $errors[] = 'Step must have a name or title';
        }

        $action = $step['action'] ?? $step['type'] ?? 'process';
        if (!in_array($action, $this->validActions)) {
            $errors[] = "Invalid step action: {$action}";
        }

        if ($action === 'agent' && empty($step['agent_type'])) {
            $errors[] = 'Agent step must specify agent_type';
        }

        if ($action === 'condition' && empty($step['condition'])) {
            $errors[] = 'Condition step must have a condition definition';
        }

        if ($action === 'delay' && (!isset($step['duration']) || $step['duration'] < 0)) {
            $errors[] = 'Delay step must have a non-negative duration';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'step' => $step,
            'action' => $action,
        ];
    }

    public function validateSteps(array $steps): array
    {
        $errors = [];

        foreach ($steps as $index => $step) {
            $result = $this->validateStep($step);
            if (!$result['valid']) {
                foreach ($result['errors'] as $error) {
                    $errors[] = "Step " . ($index + 1) . ": {$error}";
                }
            }
        }

        return $errors;
    }

    public function validateExecutionContext(array $context, array $required = []): array
    {
        $errors = [];

        foreach ($required as $field) {
            if (!array_key_exists($field, $context)) {
                $errors[] = "Missing required context field: {$field}";
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    public function getValidActions(): array
    {
        return $this->validActions;
    }

    public function getRequiredStepFields(): array
    {
        return $this->requiredStepFields;
    }
}
