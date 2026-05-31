<?php

namespace App\Http\Controllers;

use App\Models\AgentTask;
use App\Models\Workflow;
use App\Services\LogService;
use App\Services\TaskQueueService;
use App\Services\TaskRoutingService;
use App\Services\TaskManagementService;
use App\Services\TaskExecutionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TaskController extends Controller
{
    public function __construct(
        protected LogService $logService,
        protected TaskQueueService $queue,
        protected TaskRoutingService $router,
        protected TaskManagementService $taskManagementService,
        protected TaskExecutionService $taskExecutionService
    ) {}

    public function index(Request $request)
    {
        $query = AgentTask::query();

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('agent_id')) {
            $query->where('agent_id', $request->agent_id);
        }

        if ($request->has('workflow_id')) {
            $query->where('workflow_id', $request->workflow_id);
        }

        if ($request->has('priority')) {
            $query->where('priority', $request->priority);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Filter by metadata token (used for optimistic creation correlation)
        if ($request->has('metadata_token')) {
            $token = $request->metadata_token;
            $query->where('metadata->client_token', $token);
        }

        $tasks = $query->with(['agent', 'workflow'])
            ->orderBy('priority', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 20);

        return response()->json($tasks);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'agent_id' => 'nullable|exists:agents,id',
            'workflow_id' => 'nullable|exists:workflows,id',
            'priority' => 'nullable|integer|min:0|max:10',
            'due_at' => 'nullable|date',
            'metadata' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $data['status'] = 'pending';
        $data['progress'] = 0;

        $task = AgentTask::create($data);
        // reload to ensure fresh attributes (id, timestamps) are present
        $task = AgentTask::find($task->id);
        $this->queue->enqueue($task);

        $this->logService->info('Task created', [
            'channel' => 'task',
            'type' => 'create',
            'related_id' => $task->id,
            'related_type' => 'App\Models\AgentTask',
            'user_id' => $request->user()?->id,
            'context' => ['title' => $task->title, 'status' => $task->status],
        ]);

        return response()->json(['data' => $task, 'message' => 'Task created and queued'], 201);
    }

    public function show(AgentTask $task)
    {
        $task->load(['agent', 'workflow', 'steps']);

        return response()->json(['data' => $task]);
    }

    public function update(Request $request, AgentTask $task)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'status' => 'sometimes|string|in:pending,running,paused,completed,failed,cancelled',
            'progress' => 'nullable|integer|min:0|max:100',
            'priority' => 'nullable|integer|min:0|max:10',
            'due_at' => 'nullable|date',
            'metadata' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $task->update($validator->validated());

        $this->logService->info('Task updated', [
            'channel' => 'task',
            'type' => 'update',
            'related_id' => $task->id,
            'related_type' => 'App\Models\AgentTask',
            'user_id' => $request->user()?->id,
            'context' => ['changes' => $validator->validated()],
        ]);

        return response()->json(['data' => $task, 'message' => 'Task updated successfully']);
    }

    public function destroy(AgentTask $task)
    {
        $taskId = $task->id;
        $this->queue->cancel($task);
        $task->delete();

        $this->logService->info('Task deleted', [
            'channel' => 'task',
            'type' => 'delete',
            'related_id' => $taskId,
            'related_type' => 'App\Models\AgentTask',
            'user_id' => request()->user()?->id,
        ]);

        return response()->json(['message' => 'Task deleted successfully']);
    }

    public function cancel(AgentTask $task)
    {
        $task = $this->queue->cancel($task);

        $this->logService->warning('Task cancelled', [
            'channel' => 'task',
            'type' => 'cancel',
            'related_id' => $task->id,
            'related_type' => 'App\Models\AgentTask',
            'user_id' => request()->user()?->id,
        ]);

        return response()->json(['data' => $task, 'message' => 'Task cancelled']);
    }

    public function pause(AgentTask $task)
    {
        $this->queue->pause($task);

        $this->logService->info('Task paused', [
            'channel' => 'task',
            'type' => 'pause',
            'related_id' => $task->id,
            'related_type' => 'App\Models\AgentTask',
            'user_id' => request()->user()?->id,
        ]);

        return response()->json(['data' => $task, 'message' => 'Task paused']);
    }

    public function resume(AgentTask $task)
    {
        $this->queue->resume($task);

        $this->logService->info('Task resumed', [
            'channel' => 'task',
            'type' => 'resume',
            'related_id' => $task->id,
            'related_type' => 'App\Models\AgentTask',
            'user_id' => request()->user()?->id,
        ]);

        return response()->json(['data' => $task, 'message' => 'Task resumed']);
    }

    public function getStats(Request $request)
    {
        $query = AgentTask::query();

        if ($request->has('agent_id')) {
            $query->where('agent_id', $request->agent_id);
        }

        if ($request->has('workflow_id')) {
            $query->where('workflow_id', $request->workflow_id);
        }

        $stats = [
            'total' => (clone $query)->count(),
            'pending' => (clone $query)->where('status', 'pending')->count(),
            'running' => (clone $query)->where('status', 'running')->count(),
            'completed' => (clone $query)->where('status', 'completed')->count(),
            'failed' => (clone $query)->where('status', 'failed')->count(),
            'cancelled' => (clone $query)->where('status', 'cancelled')->count(),
            'paused' => (clone $query)->where('status', 'paused')->count(),
            'queue_stats' => $this->queue->getStats(),
        ];

        return response()->json(['data' => $stats]);
    }

    public function getActive(Request $request)
    {
        $activeTasks = AgentTask::with(['agent', 'workflow'])
            ->whereIn('status', ['pending', 'running'])
            ->orderBy('priority', 'desc')
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json(['data' => $activeTasks]);
    }

    public function getQueueStats()
    {
        return response()->json(['data' => $this->queue->getStats()]);
    }

    public function getRoutingStats()
    {
        return response()->json(['data' => $this->router->getStats()]);
    }

    /**
     * Manually force execution of a task
     */
    public function execute(Request $request, AgentTask $task)
    {
        try {
            $this->taskExecutionService->execute($task);
            
            return response()->json([
                'data' => $task->refresh(),
                'message' => 'Task execution initiated'
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 422);
        } catch (\Throwable $e) {
            $this->logService->error('Error executing task', [
                'channel' => 'task',
                'type' => 'execute_error',
                'related_id' => $task->id,
                'related_type' => 'App\Models\AgentTask',
                'context' => ['error' => $e->getMessage()],
            ]);
            
            return response()->json([
                'error' => 'Failed to execute task'
            ], 500);
        }
    }

    /**
     * Get execution logs for a task
     */
    public function logs(AgentTask $task)
    {
        $limit = $request->query('limit', 100);
        $logs = $this->taskLogService->getLogs($task->id, $limit);
        
        return response()->json([
            'data' => $logs
        ]);
    }

    /**
     * Update task status via state machine
     */
    public function updateStatus(Request $request, AgentTask $task)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:todo,in-progress,blocked,completed,failed,cancelled',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $newStatus = $validator->validated()['status'];
        
        try {
            // Validate the transition
            $this->taskManagementService->validateStatusTransition($task->status, $newStatus);
            
            // Update the task
            $task->update(['status' => $newStatus]);
            
            $this->logService->info('Task status updated via state machine', [
                'channel' => 'task',
                'type' => 'status_update',
                'related_id' => $task->id,
                'related_type' => 'App\Models\AgentTask',
                'user_id' => $request->user()?->id,
                'context' => [
                    'from_status' => $task->getOriginal('status'),
                    'to_status' => $newStatus
                ],
            ]);
            
            return response()->json([
                'data' => $task->refresh(),
                'message' => 'Task status updated successfully'
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 422);
        } catch (\Throwable $e) {
            $this->logService->error('Error updating task status', [
                'channel' => 'task',
                'type' => 'status_update_error',
                'related_id' => $task->id,
                'related_type' => 'App\Models\AgentTask',
                'context' => ['error' => $e->getMessage()],
            ]);
            
            return response()->json([
                'error' => 'Failed to update task status'
            ], 500);
        }
    }

    /**
     * Create a manual task
     */
    public function createManual(Request $request)
    {
        try {
            $data = $request->all();
            $data['type'] = 'manual';
            
            $task = $this->taskManagementService->create($data, $request->user()?->id);
            
            return response()->json([
                'data' => $task,
                'message' => 'Manual task created successfully'
            ], 201);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Throwable $e) {
            $this->logService->error('Error creating manual task', [
                'channel' => 'task',
                'type' => 'create_manual_error',
                'context' => ['error' => $e->getMessage()],
            ]);
            
            return response()->json([
                'error' => 'Failed to create manual task'
            ], 500);
        }
    }

    /**
     * Create an agentic task (auto-execute)
     */
    public function createAgent(Request $request)
    {
        try {
            $data = $request->all();
            $data['type'] = 'agent';
            
            $task = $this->taskManagementService->create($data, $request->user()?->id);
            
            // Agent tasks are queued for execution automatically
            $this->taskExecutionService->execute($task);
            
            return response()->json([
                'data' => $task,
                'message' => 'Agentic task created and queued for execution'
            ], 201);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 422);
        } catch (\Throwable $e) {
            $this->logService->error('Error creating agentic task', [
                'channel' => 'task',
                'type' => 'create_agent_error',
                'context' => ['error' => $e->getMessage()],
            ]);
            
            return response()->json([
                'error' => 'Failed to create agentic task'
            ], 500);
        }
    }

    /**
     * Create a system task (auto-execute)
     */
    public function createSystem(Request $request)
    {
        try {
            $data = $request->all();
            $data['type'] = 'system';
            
            $task = $this->taskManagementService->create($data, $request->user()?->id);
            
            // System tasks start execution immediately
            $this->taskExecutionService->executeNow($task);
            
            return response()->json([
                'data' => $task,
                'message' => 'System task created and executed'
            ], 201);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 422);
        } catch (\Throwable $e) {
            $this->logService->error('Error creating system task', [
                'channel' => 'task',
                'type' => 'create_system_error',
                'context' => ['error' => $e->getMessage()],
            ]);
            
            return response()->json([
                'error' => 'Failed to create system task'
            ], 500);
        }
    }

    /**
     * Get tasks by type
     */
    public function getByType(string $type)
    {
        // Validate task type
        if (!in_array($type, ['manual', 'agent', 'system'], true)) {
            return response()->json([
                'error' => 'Invalid task type'
            ], 422);
        }
        
        $tasks = $this->taskManagementService->getByType($type);
        
        return response()->json([
            'data' => $tasks
        ]);
    }

    /**
     * Get task statistics by type
     */
    public function getStatsByType()
    {
        $stats = $this->taskManagementService->getStatsByType();
        
        return response()->json([
            'data' => $stats
        ]);
    }
}
