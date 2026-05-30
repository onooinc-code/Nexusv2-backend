<?php

namespace App\Services\Routing;

use App\Models\Task;
use App\Models\Agent;
use App\Models\Workflow;
use Illuminate\Support\Facades\Log;

class TaskRouter
{
    protected array $agentRegistry = [];
    protected array $workflowRegistry = [];
    protected array $defaultRoutes = [];

    public function registerAgent(string $type, Agent $agent): void
    {
        $this->agentRegistry[$type] = $agent;
    }

    public function registerWorkflow(string $triggerType, Workflow $workflow): void
    {
        $this->workflowRegistry[$triggerType][] = $workflow;
    }

    public function setDefaultRoute(string $taskType, string $target, string $targetId): void
    {
        $this->defaultRoutes[$taskType] = [
            'target' => $target,
            'target_id' => $targetId,
        ];
    }

    public function route(Task $task): array
    {
        $taskType = $task->type ?? 'default';
        $payload = $task->payload ?? [];

        if (isset($this->defaultRoutes[$taskType])) {
            $route = $this->defaultRoutes[$taskType];
            return $this->resolveRoute($route['target'], $route['target_id'], $task);
        }

        if (isset($payload['agent_type']) && isset($this->agentRegistry[$payload['agent_type']])) {
            return $this->resolveRoute('agent', $this->agentRegistry[$payload['agent_type']]->id, $task);
        }

        if (isset($payload['workflow_id'])) {
            $workflow = Workflow::find($payload['workflow_id']);
            if ($workflow) {
                return $this->resolveRoute('workflow', $workflow->id, $task);
            }
        }

        if (isset($payload['workflow_trigger'])) {
            foreach (($this->workflowRegistry[$payload['workflow_trigger']] ?? []) as $workflow) {
                return $this->resolveRoute('workflow', $workflow->id, $task);
            }
        }

        return [
            'success' => false,
            'error' => "No route found for task type: {$taskType}",
            'task_id' => $task->id,
        ];
    }

    protected function resolveRoute(string $target, $targetId, Task $task): array
    {
        return [
            'success' => true,
            'target' => $target,
            'target_id' => $targetId,
            'task' => $task,
            'task_id' => $task->id,
            'task_type' => $task->type,
        ];
    }

    public function getRegisteredAgents(): array
    {
        return array_keys($this->agentRegistry);
    }

    public function getRegisteredWorkflows(): array
    {
        return array_keys($this->workflowRegistry);
    }

    public function getDefaultRoutes(): array
    {
        return $this->defaultRoutes;
    }
}
