<?php

namespace App\Services;

use App\Models\AgentTask;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Workflow;
use App\Services\LogService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Service for managing task lifecycle, validation, and state transitions
 */
class TaskManagementService
{
    protected LogService $logService;

    public function __construct(LogService $logService)
    {
        $this->logService = $logService;
    }

    /**
     * Validate task creation data
     */
    public function validateCreate(array $data): array
    {
        $validator = Validator::make($data, [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'agent_id' => 'nullable|exists:agents,id',
            'workflow_id' => 'nullable|exists:workflows,id',
            'contact_id' => 'nullable|exists:contacts,id',
            'conversation_id' => 'nullable|exists:conversations,id',
            'priority' => 'nullable|integer|min:0|max:10',
            'due_date' => 'nullable|date',
            'type' => 'required|in:manual,agent,system',
            'payload_data' => 'nullable|json',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }

    /**
     * Validate task update data
     */
    public function validateUpdate(array $data): array
    {
        $validator = Validator::make($data, [
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'agent_id' => 'sometimes|exists:agents,id',
            'workflow_id' => 'sometimes|exists:workflows,id',
            'contact_id' => 'sometimes|exists:contacts,id',
            'conversation_id' => 'sometimes|exists:conversations,id',
            'priority' => 'sometimes|integer|min:0|max:10',
            'due_date' => 'sometimes|date',
            'status' => 'sometimes|in:todo,in-progress,blocked,completed,failed,cancelled',
            'progress' => 'sometimes|integer|min:0|max:100',
            'payload_data' => 'sometimes|json',
            'result_data' => 'sometimes|json',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }

    /**
     * Create a new task
     */
    public function create(array $data, $userId = null): AgentTask
    {
        $validatedData = $this->validateCreate($data);
        
        // Set default values
        $validatedData['status'] = $this->getInitialStatus($validatedData['type']);
        $validatedData['progress'] = 0;
        
        // Create the task
        $task = AgentTask::create($validatedData);
        
        // Log the creation
        $this->logService->info('Task created via TaskManagementService', [
            'channel' => 'task',
            'type' => 'create',
            'related_id' => $task->id,
            'related_type' => 'App\Models\AgentTask',
            'user_id' => $userId,
            'context' => ['title' => $task->title, 'type' => $task->type],
        ]);

        return $task->refresh();
    }

    /**
     * Update an existing task
     */
    public function update(AgentTask $task, array $data, $userId = null): AgentTask
    {
        $validatedData = $this->validateUpdate($data);
        
        // Check if status transition is valid
        if (isset($validatedData['status'])) {
            $this->validateStatusTransition($task->status, $validatedData['status']);
        }
        
        // Update the task
        $task->update($validatedData);
        
        // Log the update
        $this->logService->info('Task updated via TaskManagementService', [
            'channel' => 'task',
            'type' => 'update',
            'related_id' => $task->id,
            'related_type' => 'App\Models\AgentTask',
            'user_id' => $userId,
            'context' => ['changes' => $validatedData],
        ]);

        return $task->refresh();
    }

    /**
     * Delete a task (soft delete)
     */
    public function delete(AgentTask $task, $userId = null): bool
    {
        $this->logService->info('Task deleted via TaskManagementService', [
            'channel' => 'task',
            'type' => 'delete',
            'related_id' => $task->id,
            'related_type' => 'App\Models\AgentTask',
            'user_id' => $userId,
            'context' => ['title' => $task->title],
        ]);

        return $task->delete();
    }

    /**
     * Get the initial status based on task type
     */
    protected function getInitialStatus(string $type): string
    {
        return match ($type) {
            'manual' => AgentTask::STATUS_TODO,
            'agent' => AgentTask::STATUS_TODO, // Will be queued for execution
            'system' => AgentTask::STATUS_IN_PROGRESS, // System tasks start immediately
            default => AgentTask::STATUS_TODO,
        };
    }

    /**
     * Validate if a status transition is allowed
     */
    public function validateStatusTransition(string $fromStatus, string $toStatus): void
    {
        $allowedTransitions = [
            AgentTask::STATUS_TODO => [
                AgentTask::STATUS_IN_PROGRESS,
                AgentTask::STATUS_BLOCKED,
                AgentTask::STATUS_CANCELLED,
            ],
            AgentTask::STATUS_IN_PROGRESS => [
                AgentTask::STATUS_TODO,
                AgentTask::STATUS_BLOCKED,
                AgentTask::STATUS_COMPLETED,
                AgentTask::STATUS_FAILED,
                AgentTask::STATUS_CANCELLED,
            ],
            AgentTask::STATUS_BLOCKED => [
                AgentTask::STATUS_TODO,
                AgentTask::STATUS_IN_PROGRESS,
                AgentTask::STATUS_CANCELLED,
            ],
            AgentTask::STATUS_COMPLETED => [
                AgentTask::STATUS_TODO, // Allow restarting completed tasks
            ],
            AgentTask::STATUS_FAILED => [
                AgentTask::STATUS_TODO,
                AgentTask::STATUS_IN_PROGRESS,
                AgentTask::STATUS_CANCELLED,
            ],
            AgentTask::STATUS_CANCELLED => [
                AgentTask::STATUS_TODO, // Allow restarting cancelled tasks
            ],
        ];

        if (!isset($allowedTransitions[$fromStatus]) || 
            !in_array($toStatus, $allowedTransitions[$fromStatus], true)) {
            throw new \InvalidArgumentException(
                "Invalid status transition from {$fromStatus} to {$toStatus}"
            );
        }
    }

    /**
     * Get tasks by type
     */
    public function getByType(string $type): \Illuminate\Database\Eloquent\Collection
    {
        return AgentTask::where('type', $type)->get();
    }

    /**
     * Get task statistics by type
     */
    public function getStatsByType(): array
    {
        $types = ['manual', 'agent', 'system'];
        $stats = [];

        foreach ($types as $type) {
            $stats[$type] = [
                'total' => AgentTask::where('type', $type)->count(),
                'todo' => AgentTask::where('type', $type)->where('status', AgentTask::STATUS_TODO)->count(),
                'in_progress' => AgentTask::where('type', $type)->where('status', AgentTask::STATUS_IN_PROGRESS)->count(),
                'blocked' => AgentTask::where('type', $type)->where('status', AgentTask::STATUS_BLOCKED)->count(),
                'completed' => AgentTask::where('type', $type)->where('status', AgentTask::STATUS_COMPLETED)->count(),
                'failed' => AgentTask::where('type', $type)->where('status', AgentTask::STATUS_FAILED)->count(),
                'cancelled' => AgentTask::where('type', $type)->where('status', AgentTask::STATUS_CANCELLED)->count(),
            ];
        }

        return $stats;
    }
}