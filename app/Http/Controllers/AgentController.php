<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Services\AgentConfigurationService;
use App\Services\AgentLifecycleService;
use App\Services\AgentExecutionService;
use App\Services\AgentSimulationService;
use App\Services\AgentQuarantineService;
use App\Services\AgentRateLimiter;
use App\Services\LogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AgentController extends Controller
{
    public function __construct(
        protected AgentLifecycleService $lifecycle,
        protected AgentConfigurationService $config,
        protected AgentExecutionService $executionService,
        protected AgentSimulationService $simulationService,
        protected AgentQuarantineService $quarantineService,
        protected AgentRateLimiter $rateLimiter,
        protected LogService $logService
    ) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', Agent::class);

        $query = Agent::query();

        if ($request->has('type')) {
            $query->byType($request->type);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $agents = $query->with(['tools', 'skills', 'persona', 'owner'])->paginate($request->per_page ?? 20);

        return response()->json($agents);
    }

    public function store(Request $request)
    {
        $this->authorize('create', Agent::class);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'key' => 'required|string|max:255|unique:agents,key',
            'description' => 'nullable|string',
            'type' => 'required|string|in:reflection,team,autonomous,specialized,supervisor',
            'provider' => 'nullable|string|max:255',
            'settings' => 'nullable|array',
            'metadata' => 'nullable|array',
            'owner_id' => 'nullable|exists:users,id',
            'persona_id' => 'nullable|exists:agent_personas,id',
            'rate_limit_per_minute' => 'nullable|integer|min:1',
            'is_system' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $agent = Agent::create($validator->validated());
        $this->lifecycle->register($agent);

        return response()->json(['data' => $agent, 'message' => 'Agent created successfully'], 201);
    }

    public function show(Agent $agent)
    {
        $this->authorize('view', $agent);

        $agent->load(['tools', 'skills', 'tasks', 'persona', 'owner', 'mcpServers']);
        $agent->config = $this->config->load($agent);

        return response()->json(['data' => $agent]);
    }

    public function update(Request $request, Agent $agent)
    {
        $this->authorize('update', $agent);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'type' => 'sometimes|string|in:reflection,team,autonomous,specialized,supervisor',
            'provider' => 'nullable|string|max:255',
            'settings' => 'nullable|array',
            'metadata' => 'nullable|array',
            'is_active' => 'sometimes|boolean',
            'owner_id' => 'nullable|exists:users,id',
            'persona_id' => 'nullable|exists:agent_personas,id',
            'rate_limit_per_minute' => 'nullable|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $agent->update($validator->validated());

        $this->logService->info('Agent updated', [
            'channel' => 'agent',
            'type' => 'update',
            'related_id' => $agent->id,
            'related_type' => Agent::class,
            'user_id' => $request->user()?->id,
        ]);

        return response()->json(['data' => $agent, 'message' => 'Agent updated successfully']);
    }

    public function destroy(Agent $agent)
    {
        $this->authorize('delete', $agent);

        if ($agent->is_system) {
            return response()->json(['message' => 'System agents cannot be deleted'], 403);
        }

        $agent->update(['is_active' => false]);
        $this->lifecycle->deactivate($agent);

        return response()->json(['message' => 'Agent deactivated successfully']);
    }

    public function run(Request $request, Agent $agent)
    {
        $this->authorize('run', $agent);

        $validator = Validator::make($request->all(), [
            'input' => 'required',
            'async' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $this->quarantineService->guardExecution($agent);
            $this->rateLimiter->attempt($agent);

            $this->lifecycle->initialize($agent);

            $input = $validator->validated()['input'];
            $isAsync = $request->boolean('async', false);

            if ($isAsync) {
                $result = $this->executionService->runAsync($agent, $input);
            } else {
                $result = $this->executionService->runSync($agent, $input);
            }

            return response()->json($result, $result['success'] ? 200 : 500);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to execute agent',
                'error'   => $e->getMessage()
            ], 400);
        }
    }

    public function simulate(Request $request, Agent $agent)
    {
        $this->authorize('run', $agent);

        $validator = Validator::make($request->all(), [
            'input' => 'required',
            'mock_tools' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            // Guard checks but no rate-limiting for simulations
            $this->quarantineService->guardExecution($agent);

            $data = $validator->validated();
            $result = $this->simulationService->simulate($agent, $data['input'], $data['mock_tools'] ?? []);

            return response()->json($result, $result['success'] ? 200 : 500);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Simulation failed',
                'error'   => $e->getMessage()
            ], 400);
        }
    }

    public function quarantine(Request $request, Agent $agent)
    {
        $this->authorize('quarantine', $agent);

        $reason = $request->input('reason', 'Manual quarantine');
        $result = $this->quarantineService->quarantine($agent, $reason);

        return response()->json($result, $result['success'] ? 200 : 400);
    }

    public function unquarantine(Agent $agent)
    {
        $this->authorize('quarantine', $agent);

        $result = $this->quarantineService->unquarantine($agent);
        $this->rateLimiter->clear($agent); // Reset limits so it can resume

        return response()->json($result, $result['success'] ? 200 : 400);
    }

    public function getStatus(Agent $agent)
    {
        $this->authorize('view', $agent);

        $agent->load(['tools', 'skills']);

        return response()->json([
            'data' => [
                'id' => $agent->id,
                'name' => $agent->name,
                'type' => $agent->type,
                'status' => $agent->status,
                'is_active' => $agent->is_active,
                'is_quarantined' => $agent->isQuarantined(),
                'has_error' => $agent->hasError(),
                'success_rate' => $agent->getSuccessRate(),
                'execution_count' => $agent->execution_count,
                'success_count' => $agent->success_count,
                'error_count' => $agent->error_count,
                'last_executed_at' => $agent->last_executed_at,
                'available_transitions' => $this->lifecycle->getAvailableTransitions($agent),
                'config' => $this->config->load($agent),
                'rate_limit' => $this->rateLimiter->check($agent),
            ]
        ]);
    }

    public function getLogs(Request $request, Agent $agent)
    {
        $this->authorize('view', $agent);

        $logs = \App\Models\AgentRuntimeLog::where('agent_id', $agent->id)
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 20);

        return response()->json($logs);
    }
}