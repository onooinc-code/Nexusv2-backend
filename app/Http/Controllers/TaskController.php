<?php

namespace App\Http\Controllers;

use App\Models\AgentTask;
use App\Models\Workflow;
use App\Services\LogService;
use App\Services\TaskQueueService;
use App\Services\TaskRoutingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TaskController extends Controller
{
    public function __construct(
        protected LogService $logService,
        protected TaskQueueService $queue,
        protected TaskRoutingService $router
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
}
