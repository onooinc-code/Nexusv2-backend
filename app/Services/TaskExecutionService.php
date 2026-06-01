<?php

namespace App\Services;

use App\Models\AgentTask;
use App\Jobs\ExecuteAgentTaskJob;
use App\Services\LogService;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;

/**
 * Service for executing tasks asynchronously via queue jobs
 */
class TaskExecutionService
{
    protected LogService $logService;

    public function __construct(LogService $logService)
    {
        $this->logService = $logService;
    }

    /**
     * Execute a task by dispatching a job to the queue
     */
    public function execute(AgentTask $task, array $options = []): void
    {
        // Validate that the task can be executed
        if (!$this->canExecute($task)) {
            throw new \InvalidArgumentException(
                "Task {$task->id} cannot be executed in its current state ({$task->status})"
            );
        }

        // Check for backpressure/queue overload
        if ($this->isQueueOverloaded()) {
            $this->handleBackpressure($task);
            return;
        }

        // Dispatch the job to the agent-tasks queue
        ExecuteAgentTaskJob::dispatch($task)
            ->onQueue('agent-tasks')
            ->delay($options['delay'] ?? 0);

        // Update task status to indicate it's queued for execution
        $task->update([
            'status' => AgentTask::STATUS_IN_PROGRESS,
        ]);

        $this->logService->info('Task execution dispatched', [
            'channel' => 'task',
            'type' => 'execute',
            'related_id' => $task->id,
            'related_type' => 'App\Models\AgentTask',
            'context' => ['title' => $task->title, 'queue' => 'agent-tasks'],
        ]);
    }

    /**
     * Execute a task immediately (synchronously)
     */
    public function executeNow(AgentTask $task): void
    {
        // Validate that the task can be executed
        if (!$this->canExecute($task)) {
            throw new \InvalidArgumentException(
                "Task {$task->id} cannot be executed in its current state ({$task->status})"
            );
        }

        $this->logService->info('Task execution started synchronously', [
            'channel' => 'task',
            'type' => 'execute_now',
            'related_id' => $task->id,
            'related_type' => 'App\Models\AgentTask',
            'context' => ['title' => $task->title],
        ]);

        $task->update([
            'status' => AgentTask::STATUS_IN_PROGRESS,
            'progress' => 10,
        ]);

        try {
            if ($task->type === 'agent') {
                if (!$task->agent) {
                    throw new \Exception("Agent ID required for agent task execution");
                }
                $agentService = app(\App\Services\AgentExecutionService::class);
                $result = $agentService->runSync($task->agent, $task->payload_data ?? []);
            } elseif ($task->type === 'workflow') {
                if (!$task->workflow) {
                    throw new \Exception("Workflow ID required for workflow task execution");
                }
                $workflowExecutor = app(\App\Services\WorkflowExecutor::class);
                $result = $workflowExecutor->execute($task->workflow, $task->payload_data ?? []);
            } else {
                throw new \Exception("Unsupported task type for synchronous execution: {$task->type}");
            }

            $task->update([
                'status' => AgentTask::STATUS_COMPLETED,
                'progress' => 100,
                'result_data' => $result,
            ]);

            $this->logService->info('Task execution completed synchronously', [
                'channel' => 'task',
                'type' => 'execute_now_complete',
                'related_id' => $task->id,
                'related_type' => 'App\Models\AgentTask',
                'context' => ['title' => $task->title, 'result' => $result],
            ]);
        } catch (\Throwable $e) {
            $task->update([
                'status' => AgentTask::STATUS_FAILED,
                'progress' => 0,
                'result_data' => [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ],
            ]);

            $this->logService->error('Task execution failed synchronously', [
                'channel' => 'task',
                'type' => 'execute_now_failed',
                'related_id' => $task->id,
                'related_type' => 'App\Models\AgentTask',
                'context' => ['title' => $task->title, 'error' => $e->getMessage()],
            ]);
        }
    }

    /**
     * Check if a task can be executed
     */
    public function canExecute(AgentTask $task): bool
    {
        // Only agent and system types can be auto-executed
        if (!in_array($task->type, ['agent', 'system'], true)) {
            return false;
        }

        // Only tasks in todo status can be executed
        return $task->status === AgentTask::STATUS_TODO;
    }

    /**
     * Check if the queue is overloaded (backpressure)
     */
    public function isQueueOverloaded(): bool
    {
        // Get queue stats - this would typically come from Redis/Horizon
        // For now, we'll simulate with a simple check
        // In a real implementation, this would check queue depth, worker count, etc.
        
        // Placeholder implementation - always return false for now
        // In production, this would check actual queue metrics
        return false;
    }

    /**
     * Handle backpressure when queue is overloaded
     */
    protected function handleBackpressure(AgentTask $task): void
    {
        $this->logService->warning('Task execution delayed due to queue backpressure', [
            'channel' => 'task',
            'type' => 'backpressure',
            'related_id' => $task->id,
            'related_type' => 'App\Models\AgentTask',
            'context' => ['title' => $task->title],
        ]);

        // Optionally, we could delay the task or move it to a different queue
        // For now, we'll just log it and let the caller decide what to do
    }

    /**
     * Retry a failed task execution
     */
    public function retry(AgentTask $task): void
    {
        if (!$this->canRetry($task)) {
            throw new \InvalidArgumentException(
                "Task {$task->id} cannot be retried"
            );
        }

        // Reset task status to allow retry
        $task->update([
            'status' => AgentTask::STATUS_TODO,
            'progress' => 0,
        ]);

        // Re-execute the task
        $this->execute($task);
    }

    /**
     * Check if a task can be retried
     */
    public function canRetry(AgentTask $task): bool
    {
        return in_array($task->status, [
            AgentTask::STATUS_FAILED,
            AgentTask::STATUS_CANCELLED,
        ], true);
    }
}