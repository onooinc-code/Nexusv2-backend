<?php

namespace App\Services;

use App\Models\AgentTask;
use App\Services\TaskExecutionService;
use App\Services\LogService;

class TaskSchedulingService
{
    public function __construct(
        protected TaskExecutionService $executionService,
        protected LogService $logService
    ) {}

    /**
     * Process tasks that are due for execution
     */
    public function processDueTasks(): void
    {
        $this->logService->info('Starting scheduled tasks evaluation', [
            'channel' => 'task_scheduler',
        ]);

        // Find tasks that are due and still in TODO state
        $dueTasks = AgentTask::where('status', AgentTask::STATUS_TODO)
            ->whereNotNull('due_date')
            ->where('due_date', '<=', now())
            ->get();

        foreach ($dueTasks as $task) {
            try {
                $this->logService->info('Dispatching due task', [
                    'channel' => 'task_scheduler',
                    'related_id' => $task->id,
                    'related_type' => AgentTask::class,
                ]);

                // Dispatch execution
                $this->executionService->executeTask($task);
                
            } catch (\Exception $e) {
                $this->logService->error('Failed to dispatch due task', [
                    'channel' => 'task_scheduler',
                    'related_id' => $task->id,
                    'related_type' => AgentTask::class,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if ($dueTasks->count() > 0) {
            $this->logService->info('Completed scheduled tasks evaluation', [
                'channel' => 'task_scheduler',
                'tasks_processed' => $dueTasks->count(),
            ]);
        }
    }
}
