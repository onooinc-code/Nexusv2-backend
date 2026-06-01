<?php

namespace App\Jobs;

use App\Models\AgentTask;
use App\Services\LogService;
use App\Services\TaskLogService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job to execute an agent task asynchronously
 */
class ExecuteAgentTaskJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 300; // 5 minutes
    public int $backoff = 60; // Start with 1 minute backoff

    /**
     * Get the middleware the job should pass through.
     */
    public function middleware(): array
    {
        return [new \App\Jobs\Middleware\TaskRateLimiting];
    }

    protected AgentTask $task;
    protected LogService $logService;
    protected TaskLogService $taskLogService;

    /**
     * Create a new job instance.
     */
    public function __construct(AgentTask $task)
    {
        $this->task = $task;
        $this->logService = app(LogService::class);
        $this->taskLogService = app(TaskLogService::class);
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Refresh the task from database to get latest state
        $this->task->refresh();

        // Validate that the task is still executable
        if (!$this->isExecutable()) {
            $this->logService->warning('Task is no longer executable, skipping job', [
                'channel' => 'task',
                'type' => 'job_skip',
                'related_id' => $this->task->id,
                'related_type' => 'App\Models\AgentTask',
                'context' => ['title' => $this->task->title, 'status' => $this->task->status],
            ]);

            return;
        }

        // Mark task as running
        $this->task->update([
            'status' => \App\Models\AgentTask::STATUS_IN_PROGRESS,
            'progress' => 10, // Show some progress
        ]);

        $this->logService->info('Task execution started', [
            'channel' => 'task',
            'type' => 'job_start',
            'related_id' => $this->task->id,
            'related_type' => 'App\Models\AgentTask',
            'context' => ['title' => $this->task->title],
        ]);

        $this->taskLogService->info($this->task, 'Task execution started');

        try {
            // Dispatch to the correct engine based on task type
            $result = $this->executeTask();

            // Mark task as completed
            $this->task->update([
                'status' => \App\Models\AgentTask::STATUS_COMPLETED,
                'progress' => 100,
                'result_data' => $result,
            ]);

            $this->logService->info('Task execution completed successfully', [
                'channel' => 'task',
                'type' => 'job_success',
                'related_id' => $this->task->id,
                'related_type' => 'App\Models\AgentTask',
                'context' => ['title' => $this->task->title, 'result' => $result],
            ]);

            $this->taskLogService->info($this->task, 'Task execution completed successfully', [
                'result' => $result,
            ]);
        } catch (\Throwable $e) {
            // Handle task execution failure
            $this->handleTaskFailure($e);
        }
    }

    /**
     * Execute task via AgentExecutionService or WorkflowEngine
     */
    protected function executeTask(): array
    {
        if ($this->task->type === 'agent') {
            if (!$this->task->agent) {
                throw new \Exception("Agent ID required for agent task execution");
            }
            $agentService = app(\App\Services\AgentExecutionService::class);
            return $agentService->runSync($this->task->agent, $this->task->payload_data ?? []);
        } elseif ($this->task->type === 'workflow') {
            if (!$this->task->workflow) {
                throw new \Exception("Workflow ID required for workflow task execution");
            }
            $workflowExecutor = app(\App\Services\WorkflowExecutor::class);
            return $workflowExecutor->execute($this->task->workflow, $this->task->payload_data ?? []);
        }

        throw new \Exception("Unsupported task type: {$this->task->type}");
    }

    /**
     * Handle task execution failure
     */
    protected function handleTaskFailure(\Throwable $e): void
    {
        $errorMessage = $e->getMessage();
        $errorCode = $e->getCode();

        $this->logService->error('Task execution failed', [
            'channel' => 'task',
            'type' => 'job_failed',
            'related_id' => $this->task->id,
            'related_type' => 'App\Models\AgentTask',
            'context' => ['title' => $this->task->title, 'error' => $errorMessage, 'code' => $errorCode],
        ]);

        $this->taskLogService->error($this->task, 'Task execution failed', [
            'error' => $errorMessage,
            'code' => $errorCode,
            'trace' => $e->getTraceAsString(),
        ]);

        // Update task with failure status
        $this->task->update([
            'status' => \App\Models\AgentTask::STATUS_FAILED,
            'progress' => 0,
            'result_data' => [
                'error' => $errorMessage,
                'code' => $errorCode,
                'failed_at' => now()->toISOString(),
            ],
        ]);

        // TODO: Implement retry logic based on task metadata
        // For now, we'll just let the job fail and be handled by the queue's retry mechanism
    }

    /**
     * Check if the task is still in a state where it can be executed
     */
    protected function isExecutable(): bool
    {
        return in_array($this->task->status, [
            \App\Models\AgentTask::STATUS_TODO,
            \App\Models\AgentTask::STATUS_IN_PROGRESS, // Allow retry of in-progress tasks
        ], true);
    }

    /**
     * Define the queue connection and queue name
     */
    public function queue(): string
    {
        return 'agent-tasks';
    }

    /**
     * Calculate backoff delay for retries
     */
    public function backoff(): int
    {
        return $this->backoff * (2 ** ($this->attempts() - 1));
    }

    /**
     * Determine if the job should be retried based on the exception
     */
    public function failed(\Throwable $e): void
    {
        // This is called when the job has exceeded its maximum attempts
        $this->logService->error('Task execution job failed permanently', [
            'channel' => 'task',
            'type' => 'job_permanently_failed',
            'related_id' => $this->task->id,
            'related_type' => 'App\Models\AgentTask',
            'context' => ['title' => $this->task->title, 'error' => $e->getMessage()],
        ]);

        $this->taskLogService->error($this->task, 'Task execution job failed permanently', [
            'error' => $e->getMessage(),
        ]);

        // Ensure task is marked as failed
        $this->task->update([
            'status' => \App\Models\AgentTask::STATUS_FAILED,
        ]);

        // Push to Dead Letter Queue
        event(new \App\Events\TaskMovedToDLQEvent($this->task, $e));
    }
}