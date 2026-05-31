<?php

namespace App\Http\Controllers;

use App\Http\Requests\ExecuteWorkflowRequest;
use App\Http\Requests\ResumeWorkflowExecutionRequest;
use App\Http\Requests\StoreWorkflowRequest;
use App\Http\Requests\UpdateWorkflowRequest;
use App\Http\Resources\WorkflowExecutionResource;
use App\Http\Resources\WorkflowResource;
use App\Jobs\ExecuteWorkflowJob;
use App\Models\Workflow;
use App\Models\WorkflowExecution;
use App\Services\LogService;
use App\Services\WorkflowExecutor;
use App\Services\Workflows\WorkflowInterpreter;
use App\Services\Workflows\WorkflowPolicyGuard;
use App\Services\Workflows\WorkflowRegistry;
use App\Services\Workflows\WorkflowStateManager;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class WorkflowController extends Controller
{
    public function __construct(
        protected WorkflowExecutor $executor,
        protected WorkflowRegistry $registry,
        protected WorkflowStateManager $stateManager,
        protected WorkflowPolicyGuard $policyGuard,
        protected WorkflowInterpreter $interpreter,
        protected LogService $logService
    ) {}

    public function index(Request $request)
    {
        $query = Workflow::query()->withCount('executions');

        if ($request->filled('status')) {
            $query->byStatus($request->string('status'));
        }

        if ($request->filled('trigger_type')) {
            $query->byTriggerType($request->string('trigger_type'));
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->has('include_system') && ! $request->boolean('include_system')) {
            $query->where('is_system', false);
        }

        if ($request->filled('search')) {
            $search = (string) $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('key', 'like', "%{$search}%");
            });
        }

        $perPage = min((int) $request->query('limit', $request->query('per_page', 20)), 100);
        $workflows = $query->latest()->paginate($perPage);

        return WorkflowResource::collection($workflows);
    }

    public function store(StoreWorkflowRequest $request)
    {
        $workflow = $this->registry->create($request->validated(), $request->user());

        $this->logService->info('Workflow created', [
            'channel' => 'workflow',
            'type' => 'create',
            'related_id' => $workflow->id,
            'related_type' => Workflow::class,
            'user_id' => $request->user()?->id,
        ]);

        return (new WorkflowResource($workflow))->response()->setStatusCode(201);
    }

    public function show(Workflow $workflow)
    {
        $workflow->load(['versions' => fn ($query) => $query->latest('version_number'), 'executions' => fn ($query) => $query->latest()->limit(5)]);

        return new WorkflowResource($workflow);
    }

    public function update(UpdateWorkflowRequest $request, Workflow $workflow)
    {
        if ($workflow->is_system && $request->has('steps')) {
            throw ValidationException::withMessages([
                'steps' => 'System workflow definitions are immutable. Update schedule, settings, or variables only.',
            ]);
        }

        $this->policyGuard->assertCanManage($request->user(), $workflow);
        $workflow = $this->registry->update($workflow, $request->validated(), $request->user());

        $this->logService->info('Workflow updated', [
            'channel' => 'workflow',
            'type' => 'update',
            'related_id' => $workflow->id,
            'related_type' => Workflow::class,
            'user_id' => $request->user()?->id,
        ]);

        return new WorkflowResource($workflow);
    }

    public function destroy(Workflow $workflow)
    {
        $this->policyGuard->assertCanDelete($workflow);
        $workflow->update(['is_active' => false, 'status' => Workflow::STATUS_CANCELLED]);

        $this->logService->info('Workflow deactivated', [
            'channel' => 'workflow',
            'type' => 'deactivate',
            'related_id' => $workflow->id,
            'related_type' => Workflow::class,
            'user_id' => request()->user()?->id,
        ]);

        return response()->json(['data' => ['id' => $workflow->id], 'message' => 'Workflow deactivated successfully']);
    }

    public function execute(ExecuteWorkflowRequest $request, Workflow $workflow)
    {
        if ($workflow->isRunning()) {
            return response()->json([
                'code' => 'workflow_running',
                'message' => 'Workflow is already running',
            ], 409);
        }

        $runMode = $request->validated('run_mode') ?? 'async';
        $result = $this->executor->execute($workflow, $request->inputPayload(), $runMode, $request->user());

        $execution = WorkflowExecution::with('stepLogs')->find($result['execution_id']);

        return response()->json([
            'data' => new WorkflowExecutionResource($execution),
            'message' => $runMode === 'async' ? 'Workflow execution queued' : 'Workflow execution completed',
        ], $runMode === 'async' ? 202 : 200);
    }

    public function getProgress(Workflow $workflow)
    {
        $latestExecution = $workflow->executions()->with('stepLogs')->latest()->first();

        return response()->json([
            'data' => [
                'workflow' => new WorkflowResource($workflow),
                'latest_execution' => $latestExecution ? new WorkflowExecutionResource($latestExecution) : null,
            ],
        ]);
    }

    public function showExecution(WorkflowExecution $execution)
    {
        $execution->load(['workflow', 'version', 'stepLogs' => fn ($query) => $query->orderBy('created_at')]);

        return new WorkflowExecutionResource($execution);
    }

    public function resume(ResumeWorkflowExecutionRequest $request, WorkflowExecution $execution)
    {
        if ($execution->status !== WorkflowExecution::STATUS_PAUSED) {
            return response()->json([
                'code' => 'execution_not_paused',
                'message' => 'Only paused workflow executions can be resumed.',
            ], 409);
        }

        if ($request->validated('decision') === 'deny') {
            $execution = $this->stateManager->cancel($execution);
            return new WorkflowExecutionResource($execution->load('stepLogs'));
        }

        $execution = $this->stateManager->mergeResumePayload($execution, $request->validated('input_payload') ?? []);

        if ($execution->run_mode === 'async') {
            ExecuteWorkflowJob::dispatch($execution->id);
            return (new WorkflowExecutionResource($execution->load('stepLogs')))->response()->setStatusCode(202);
        }

        $execution = $this->interpreter->run($execution);

        return new WorkflowExecutionResource($execution->load('stepLogs'));
    }

    public function cancel(WorkflowExecution $execution)
    {
        if ($execution->isTerminal()) {
            return response()->json([
                'code' => 'execution_terminal',
                'message' => 'Terminal workflow executions cannot be cancelled.',
            ], 409);
        }

        $execution = $this->stateManager->cancel($execution);

        return new WorkflowExecutionResource($execution->load('stepLogs'));
    }

    public function getTemplates(Request $request)
    {
        $templates = $this->getWorkflowTemplates();

        if ($request->filled('category')) {
            $templates = array_filter($templates, fn ($template) => $template['category'] === $request->query('category'));
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
                    ['id' => 'create_profile', 'name' => 'Create contact profile', 'type' => 'agent', 'agent_type' => 'autonomous'],
                    ['id' => 'send_welcome', 'name' => 'Send welcome message', 'type' => 'agent', 'agent_type' => 'autonomous'],
                    ['id' => 'log_onboarding', 'name' => 'Log onboarding', 'type' => 'log', 'message' => 'Contact onboarded'],
                ],
            ],
            [
                'id' => 'daily-summary',
                'name' => 'Daily Summary',
                'description' => 'Generate daily summary of activities',
                'category' => 'reporting',
                'steps' => [
                    ['id' => 'collect_data', 'name' => 'Collect daily data', 'type' => 'agent', 'agent_type' => 'autonomous'],
                    ['id' => 'generate_summary', 'name' => 'Generate summary', 'type' => 'agent', 'agent_type' => 'reflection'],
                    ['id' => 'approval_gate', 'name' => 'Review summary', 'type' => 'wait', 'wait_for' => 'approval'],
                    ['id' => 'send_notification', 'name' => 'Send notification', 'type' => 'agent', 'agent_type' => 'autonomous'],
                ],
            ],
            [
                'id' => 'error-recovery',
                'name' => 'Error Recovery',
                'description' => 'Automated error detection and recovery',
                'category' => 'maintenance',
                'steps' => [
                    ['id' => 'detect_error', 'name' => 'Detect error', 'type' => 'decision', 'condition' => ['field' => 'status', 'operator' => '==', 'value' => 'error'], 'then' => 'retry_operation', 'else' => 'log_clean'],
                    ['id' => 'retry_operation', 'name' => 'Retry operation', 'type' => 'agent', 'agent_type' => 'autonomous'],
                    ['id' => 'log_clean', 'name' => 'Log no-op', 'type' => 'log', 'message' => 'No recovery needed'],
                ],
            ],
        ];
    }
}
