<?php

namespace App\Services;

use App\Models\AgentTask;
use App\Models\DeadLetterTask;
use App\Services\TaskExecutionService;
use App\Services\LogService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class DeadLetterQueueService
{
    public function __construct(
        protected TaskExecutionService $executionService,
        protected LogService $logService
    ) {}

    /**
     * Log a failed task to the DLQ
     */
    public function log(AgentTask $task, \Throwable $exception, string $queue = 'agent-tasks'): DeadLetterTask
    {
        $this->logService->error('Logging task execution failure to Dead Letter Queue', [
            'channel' => 'dlq',
            'task_id' => $task->id,
            'queue' => $queue,
            'error' => $exception->getMessage()
        ]);

        return DeadLetterTask::create([
            'task_id' => $task->id,
            'queue' => $queue,
            'exception_message' => $exception->getMessage(),
            'exception_trace' => $exception->getTraceAsString(),
            'failed_at' => now(),
        ]);
    }

    /**
     * Retrieve paginated dead letter tasks
     */
    public function list(int $perPage = 20): LengthAwarePaginator
    {
        return DeadLetterTask::with('task')->latest('failed_at')->paginate($perPage);
    }

    /**
     * Retry a dead letter task
     */
    public function retry(int $id): bool
    {
        $dlqTask = DeadLetterTask::findOrFail($id);
        $task = $dlqTask->task;

        if (!$task) {
            $this->logService->error('Attempted to retry DLQ task but associated task was not found', [
                'channel' => 'dlq',
                'dlq_id' => $id
            ]);
            return false;
        }

        $this->logService->info('Retrying failed task from DLQ', [
            'channel' => 'dlq',
            'task_id' => $task->id,
            'dlq_id' => $id
        ]);

        // Reset status to todo and progress to 0
        $task->update([
            'status' => AgentTask::STATUS_TODO,
            'progress' => 0,
        ]);

        // Execute task again
        $this->executionService->execute($task);

        // Delete from DLQ
        return $dlqTask->delete();
    }

    /**
     * Discard a dead letter task
     */
    public function delete(int $id): bool
    {
        $dlqTask = DeadLetterTask::findOrFail($id);

        $this->logService->warning('Dismissing failed task from DLQ', [
            'channel' => 'dlq',
            'dlq_id' => $id,
            'task_id' => $dlqTask->task_id
        ]);

        return $dlqTask->delete();
    }

    /**
     * Batch retry multiple dead letter tasks
     */
    public function batchRetry(array $ids): array
    {
        $success = 0;
        $failed = 0;

        foreach ($ids as $id) {
            try {
                if ($this->retry((int) $id)) {
                    $success++;
                } else {
                    $failed++;
                }
            } catch (\Throwable $e) {
                $failed++;
            }
        }

        return [
            'success' => $success,
            'failed' => $failed,
        ];
    }
}
