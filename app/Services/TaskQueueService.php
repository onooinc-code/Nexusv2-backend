<?php

namespace App\Services;

use App\Models\AgentTask;
use App\Models\Workflow;
use App\Services\LogService;
use Illuminate\Support\Str;

class TaskQueueService
{
    protected array $queue = [];
    protected array $processing = [];
    protected array $completed = [];
    protected array $failed = [];
    protected LogService $logService;

    public function __construct(LogService $logService)
    {
        $this->logService = $logService;
    }

    public function enqueue(AgentTask $task, array $options = []): AgentTask
    {
        $id = $task->id;
        $task->update([
            'status' => 'pending',
            'metadata' => array_merge($task->metadata ?? [], [
                'queued_at' => now()->toISOString(),
                'queue_options' => $options,
            ]),
        ]);

        // ensure we operate on the fresh model instance
        $fresh = AgentTask::find($id);

        $this->queue[] = $fresh->id;

        $this->logService->info('Task enqueued', [
            'channel' => 'task',
            'type' => 'queue',
            'related_id' => $fresh->id,
            'related_type' => 'App\Models\AgentTask',
            'context' => ['title' => $fresh->title],
        ]);

        return $fresh;
    }

    public function dequeue(): ?AgentTask
    {
        if (empty($this->queue)) {
            return null;
        }

        $taskId = array_shift($this->queue);
        $task = AgentTask::find($taskId);

        if ($task) {
            $task->update(['status' => 'running']);
            $this->processing[] = $taskId;

            $this->logService->info('Task dequeued', [
                'channel' => 'task',
                'type' => 'dequeue',
                'related_id' => $task->id,
                'related_type' => 'App\Models\AgentTask',
                'context' => ['title' => $task->title],
            ]);
        }

        return $task;
    }

    public function complete(AgentTask $task, array $result = []): AgentTask
    {
        $task->update([
            'status' => 'completed',
            'progress' => 100,
            'metadata' => array_merge($task->metadata ?? [], [
                'completed_at' => now()->toISOString(),
                'result' => $result,
            ]),
        ]);

        $this->processing = array_filter($this->processing, fn($id) => $id !== $task->id);
        $this->completed[] = $task->id;

        $this->logService->info('Task completed', [
            'channel' => 'task',
            'type' => 'complete',
            'related_id' => $task->id,
            'related_type' => 'App\Models\AgentTask',
            'context' => ['title' => $task->title, 'result' => $result],
        ]);

        return $task;
    }

    public function fail(AgentTask $task, string $error = null): AgentTask
    {
        $task->update([
            'status' => 'failed',
            'metadata' => array_merge($task->metadata ?? [], [
                'failed_at' => now()->toISOString(),
                'error' => $error,
            ]),
        ]);

        $this->processing = array_filter($this->processing, fn($id) => $id !== $task->id);
        $this->failed[] = $task->id;

        $this->logService->error('Task failed', [
            'channel' => 'task',
            'type' => 'fail',
            'related_id' => $task->id,
            'related_type' => 'App\Models\AgentTask',
            'context' => ['title' => $task->title, 'error' => $error],
        ]);

        return $task;
    }

    public function cancel(AgentTask $task): AgentTask
    {
        $id = $task->id;
        $task->update(['status' => 'cancelled']);

        $this->queue = array_filter($this->queue, fn($qid) => $qid !== $id);
        $this->processing = array_filter($this->processing, fn($pid) => $pid !== $id);

        $fresh = AgentTask::find($id);

        if ($fresh) {
            $this->logService->info('Task cancelled', [
                'channel' => 'task',
                'type' => 'cancel',
                'related_id' => $fresh->id,
                'related_type' => 'App\Models\AgentTask',
                'context' => ['title' => $fresh->title],
            ]);
            return $fresh;
        }

        $this->logService->warning('Task cancelled but fresh model could not be retrieved', [
            'channel' => 'task',
            'type' => 'cancel',
            'related_id' => $id,
            'related_type' => 'App\Models\AgentTask',
            'context' => ['original_task' => $task->toArray()],
        ]);

        return $task;
    }

    public function pause(AgentTask $task): AgentTask
    {
        $task->update(['status' => 'paused']);

        $this->logService->info('Task paused', [
            'channel' => 'task',
            'type' => 'pause',
            'related_id' => $task->id,
            'related_type' => 'App\Models\AgentTask',
            'context' => ['title' => $task->title],
        ]);

        return $task;
    }

    public function resume(AgentTask $task): AgentTask
    {
        $task->update(['status' => 'pending']);
        $this->enqueue($task);

        $this->logService->info('Task resumed', [
            'channel' => 'task',
            'type' => 'resume',
            'related_id' => $task->id,
            'related_type' => 'App\Models\AgentTask',
            'context' => ['title' => $task->title],
        ]);

        return $task;
    }

    public function getQueueSize(): int
    {
        return count($this->queue);
    }

    public function getProcessingSize(): int
    {
        return count($this->processing);
    }

    public function getCompletedCount(): int
    {
        return count($this->completed);
    }

    public function getFailedCount(): int
    {
        return count($this->failed);
    }

    public function getStats(): array
    {
        return [
            'queued' => $this->getQueueSize(),
            'processing' => $this->getProcessingSize(),
            'completed' => $this->getCompletedCount(),
            'failed' => $this->getFailedCount(),
            'total' => $this->getQueueSize() + $this->getProcessingSize() + $this->getCompletedCount() + $this->getFailedCount(),
        ];
    }

    public function clearQueue(): void
    {
        $this->queue = [];

        $this->logService->info('Task queue cleared', [
            'channel' => 'task',
            'type' => 'clear',
        ]);
    }

    public function clearCompleted(): void
    {
        $this->completed = [];

        $this->logService->info('Completed tasks cleared', [
            'channel' => 'task',
            'type' => 'clear',
        ]);
    }

    public function clearFailed(): void
    {
        $this->failed = [];

        $this->logService->info('Failed tasks cleared', [
            'channel' => 'task',
            'type' => 'clear',
        ]);
    }

    public function getQueuedTaskIds(): array
    {
        return $this->queue;
    }

    public function getProcessingTaskIds(): array
    {
        return $this->processing;
    }
}
