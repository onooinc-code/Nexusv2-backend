<?php

namespace App\Http\Controllers;

use App\Models\Workflow;
use App\Services\LogService;
use App\Services\WorkflowExecutor;
use App\Services\WorkflowValidationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class WorkflowController extends Controller
{
    public function __construct(
        protected WorkflowExecutor $executor,
        protected WorkflowValidationService $validator,
        protected LogService $logService
    ) {}

    public function index(Request $request)
    {
        $query = Workflow::query();

        if ($request->has('status')) {
            $query->byStatus($request->status);
        }

        if ($request->has('trigger_type')) {
            $query->byTriggerType($request->trigger_type);
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

        $workflows = $query->with(['tasks'])->paginate($request->per_page ?? 20);

        return response()->json($workflows);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'key' => 'required|string|max:255|unique:workflows,key',
            'description' => 'nullable|string',
            'steps' => 'required|array|min:1',
            'steps.*.name' => 'required|string',
            'steps.*.action' => 'required|string',
            'trigger_type' => 'required|string|in:manual,scheduled,event,webhook',
            'trigger_config' => 'nullable|array',
            'settings' => 'nullable|array',
            'metadata' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $workflowData = $validator->validated();
        $validation = $this->validator->validateWorkflow($workflowData);

        if (!$validation['valid']) {
            return response()->json(['errors' => $validation['errors']], 422);
        }

        $workflow = Workflow::create($workflowData);

        $this->logService->info('Workflow created', [
            'channel' => 'workflow',
            'type' => 'create',
            'related_id' => $workflow->id,
            'related_type' => Workflow::class,
            'user_id' => $request->user()?->id,
        ]);

        return response()->json(['data' => $workflow, 'message' => 'Workflow created successfully'], 201);
    }

    public function show(Workflow $workflow)
    {
        $workflow->load(['tasks']);

        return response()->json([
            'data' => $workflow,
            'progress' => $workflow->progress,
            'total_steps' => $workflow->total_steps,
            'completed_steps' => $workflow->completed_steps,
        ]);
    }

    public function update(Request $request, Workflow $workflow)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'steps' => 'sometimes|array|min:1',
            'steps.*.name' => 'required_with:steps|string',
            'steps.*.action' => 'required_with:steps|string',
            'trigger_type' => 'sometimes|string|in:manual,scheduled,event,webhook',
            'trigger_config' => 'nullable|array',
            'settings' => 'nullable|array',
            'metadata' => 'nullable|array',
            'is_active' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $updateData = $validator->validated();

        if (isset($updateData['steps'])) {
            $stepValidation = $this->validator->validateSteps($updateData['steps']);
            if (!empty($stepValidation)) {
                return response()->json(['errors' => $stepValidation], 422);
            }
        }

        $workflow->update($updateData);

        $this->logService->info('Workflow updated', [
            'channel' => 'workflow',
            'type' => 'update',
            'related_id' => $workflow->id,
            'related_type' => Workflow::class,
            'user_id' => $request->user()?->id,
        ]);

        return response()->json(['data' => $workflow, 'message' => 'Workflow updated successfully']);
    }

    public function destroy(Workflow $workflow)
    {
        $workflow->update(['is_active' => false]);

        $this->logService->info('Workflow deactivated', [
            'channel' => 'workflow',
            'type' => 'deactivate',
            'related_id' => $workflow->id,
            'related_type' => Workflow::class,
            'user_id' => request()->user()?->id,
        ]);

        return response()->json(['message' => 'Workflow deactivated successfully']);
    }

    public function execute(Request $request, Workflow $workflow)
    {
        if ($workflow->isRunning()) {
            return response()->json(['message' => 'Workflow is already running'], 409);
        }

        $context = $request->validate([
            'context' => 'nullable|array',
        ])['context'] ?? [];

        try {
            $this->logService->info('Workflow execution started', [
                'channel' => 'workflow',
                'type' => 'execute',
                'related_id' => $workflow->id,
                'related_type' => Workflow::class,
                'user_id' => $request->user()?->id,
                'context' => $context,
            ]);

            $result = $this->executor->execute($workflow, $context);

            $this->logService->info('Workflow execution completed', [
                'channel' => 'workflow',
                'type' => 'execute',
                'related_id' => $workflow->id,
                'related_type' => Workflow::class,
                'user_id' => $request->user()?->id,
                'context' => ['status' => $result['status'] ?? 'completed'],
            ]);

            return response()->json([
                'message' => 'Workflow execution completed',
                'data' => $result,
            ]);
        } catch (\Throwable $e) {
            $this->logService->error('Workflow execution failed', [
                'channel' => 'workflow',
                'type' => 'execute',
                'related_id' => $workflow->id,
                'related_type' => Workflow::class,
                'user_id' => $request->user()?->id,
                'context' => ['error' => $e->getMessage()],
            ]);

            return response()->json([
                'message' => 'Workflow execution failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getProgress(Workflow $workflow)
    {
        $workflow->load(['tasks']);

        return response()->json([
            'data' => [
                'id' => $workflow->id,
                'name' => $workflow->name,
                'status' => $workflow->status,
                'status_label' => $workflow->status_label,
                'progress' => $workflow->progress,
                'total_steps' => $workflow->total_steps,
                'completed_steps' => $workflow->completed_steps,
                'execution_count' => $workflow->execution_count,
                'success_rate' => $workflow->getSuccessRate(),
                'last_executed_at' => $workflow->last_executed_at,
                'step_results' => $this->executor->getStepResults(),
            ]
        ]);
    }

    public function getTemplates(Request $request)
    {
        $templates = $this->getWorkflowTemplates();

        if ($request->has('category')) {
            $templates = array_filter($templates, fn($t) => $t['category'] === $request->category);
        }

        return response()->json(['data' => array_values($templates)]);
    }

    protected function getWorkflowTemplates(): array
    {
        return [
            [
                'id' => 'contact-onboarding',
                'name' => 'Contact Onboarding',
                'description' => 'Automated workflow for new contact onboarding',
                'category' => 'contacts',
                'steps' => [
                    ['name' => 'Create contact profile', 'action' => 'agent', 'agent_type' => 'autonomous'],
                    ['name' => 'Send welcome message', 'action' => 'agent', 'agent_type' => 'autonomous'],
                    ['name' => 'Log onboarding', 'action' => 'log', 'message' => 'Contact onboarded'],
                ],
            ],
            [
                'id' => 'daily-summary',
                'name' => 'Daily Summary',
                'description' => 'Generate daily summary of activities',
                'category' => 'reporting',
                'steps' => [
                    ['name' => 'Collect daily data', 'action' => 'agent', 'agent_type' => 'autonomous'],
                    ['name' => 'Generate summary', 'action' => 'agent', 'agent_type' => 'reflection'],
                    ['name' => 'Send notification', 'action' => 'agent', 'agent_type' => 'autonomous'],
                ],
            ],
            [
                'id' => 'error-recovery',
                'name' => 'Error Recovery',
                'description' => 'Automated error detection and recovery',
                'category' => 'maintenance',
                'steps' => [
                    ['name' => 'Detect error', 'action' => 'condition', 'condition' => ['field' => 'status', 'operator' => '==', 'value' => 'error']],
                    ['name' => 'Retry operation', 'action' => 'agent', 'agent_type' => 'autonomous'],
                    ['name' => 'Alert if failed', 'action' => 'log', 'message' => 'Recovery failed'],
                ],
            ],
            [
                'id' => 'contact-analysis',
                'name' => 'Contact Analysis',
                'description' => 'Deep analysis of contact interactions',
                'category' => 'analytics',
                'steps' => [
                    ['name' => 'Gather contact data', 'action' => 'agent', 'agent_type' => 'autonomous'],
                    ['name' => 'Analyze sentiment', 'action' => 'agent', 'agent_type' => 'reflection'],
                    ['name' => 'Generate insights', 'action' => 'agent', 'agent_type' => 'specialized'],
                ],
            ],
        ];
    }
}