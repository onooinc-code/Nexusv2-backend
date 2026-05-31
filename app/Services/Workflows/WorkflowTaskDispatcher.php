<?php

namespace App\Services\Workflows;

use App\Models\Agent;
use App\Models\AgentTask;
use App\Models\WorkflowExecution;
use App\Services\AgentExecutionService;
use App\Services\LogService;

class WorkflowTaskDispatcher
{
    public function __construct(
        protected AgentExecutionService $agentExecution,
        protected LogService $logService
    ) {}

    public function dispatch(WorkflowExecution $execution, array $step, array $variables): array
    {
        $type = strtolower((string) ($step['type'] ?? 'action'));

        return match ($type) {
            'agent' => $this->runAgentStep($step, $variables),
            'task' => $this->createTaskStep($execution, $step, $variables),
            'log' => $this->logStep($execution, $step, $variables),
            'action' => $this->runActionStep($step, $variables),
            'code' => $this->runCodeStep($step, $variables),
            default => [
                'success' => true,
                'output' => ['message' => "Step {$step['name']} processed.", 'type' => $type],
            ],
        };
    }

    protected function runAgentStep(array $step, array $variables): array
    {
        $agent = null;
        if (! empty($step['agent_id'])) {
            $agent = Agent::find($step['agent_id']);
        }

        if (! $agent && ! empty($step['agent_type'])) {
            $agent = Agent::where('type', $step['agent_type'])->where('is_active', true)->first();
        }

        if (! $agent) {
            return ['success' => false, 'error' => 'No active agent matched this workflow step.'];
        }

        return $this->agentExecution->runSync($agent, [
            'task' => $step['task'] ?? $step['name'],
            'workflow_variables' => $variables,
            'input' => $step['input'] ?? [],
        ]);
    }

    protected function createTaskStep(WorkflowExecution $execution, array $step, array $variables): array
    {
        $task = AgentTask::create([
            'workflow_id' => $execution->workflow_id,
            'agent_id' => $step['agent_id'] ?? null,
            'title' => $step['task_title'] ?? $step['name'],
            'description' => $step['description'] ?? null,
            'status' => AgentTask::STATUS_TODO,
            'priority' => $step['priority'] ?? 3,
            'type' => $step['assignee_type'] ?? 'agent',
            'payload_data' => [
                'workflow_execution_id' => $execution->id,
                'step_id' => $step['id'],
                'variables' => $variables,
            ],
        ]);

        return [
            'success' => true,
            'pause' => (bool) ($step['pause_until_completed'] ?? true),
            'waiting_for' => ['type' => 'task', 'task_id' => $task->id],
            'output' => ['task_id' => $task->id],
        ];
    }

    protected function logStep(WorkflowExecution $execution, array $step, array $variables): array
    {
        $this->logService->info($step['message'] ?? $step['name'], [
            'channel' => 'workflow',
            'type' => 'step_log',
            'related_id' => $execution->workflow_id,
            'related_type' => 'App\Models\Workflow',
            'context' => ['execution_id' => $execution->id, 'step_id' => $step['id'], 'variables' => $variables],
        ]);

        return ['success' => true, 'output' => ['logged' => true]];
    }

    protected function runActionStep(array $step, array $variables): array
    {
        return [
            'success' => true,
            'output' => [
                'action' => $step['action_name'] ?? $step['action'] ?? 'action',
                'input' => $step['input'] ?? [],
                'variables_snapshot' => $variables,
            ],
        ];
    }

    protected function runCodeStep(array $step, array $variables): array
    {
        return [
            'success' => false,
            'error' => 'Code step execution requires a dedicated sandbox and is disabled in this runtime.',
            'output' => ['variables_snapshot' => $variables],
        ];
    }
}
