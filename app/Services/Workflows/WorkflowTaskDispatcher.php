<?php

namespace App\Services\Workflows;

use App\Models\Agent;
use App\Models\AgentTask;
use App\Models\WorkflowExecution;
use App\Services\AgentExecutionService;
use App\Services\LogService;

class WorkflowTaskDispatcher
{
    public function __construct(
        protected AgentExecutionService $agentExecution,
        protected LogService $logService
    ) {}

    public function dispatch(WorkflowExecution $execution, array $step, array $variables): array
    {
        $type = strtolower((string) ($step['type'] ?? 'action'));

        return match ($type) {
            'agent' => $this->runAgentStep($step, $variables),
            'task' => $this->createTaskStep($execution, $step, $variables),
            'log' => $this->logStep($execution, $step, $variables),
            'action' => $this->runActionStep($step, $variables),
            'code' => $this->runCodeStep($step, $variables),
            default => [
                'success' => true,
                'output' => ['message' => "Step {$step['name']} processed.", 'type' => $type],
            ],
        };
    }

    protected function runAgentStep(array $step, array $variables): array
    {
        $agent = null;
        if (! empty($step['agent_id'])) {
            $agent = Agent::find($step['agent_id']);
        }

        if (! $agent && ! empty($step['agent_type'])) {
            $agent = Agent::where('type', $step['agent_type'])->where('is_active', true)->first();
        }

        if (! $agent) {
            return ['success' => false, 'error' => 'No active agent matched this workflow step.'];
        }

        return $this->agentExecution->runSync($agent, [
            'task' => $step['task'] ?? $step['name'],
            'workflow_variables' => $variables,
            'input' => $step['input'] ?? [],
        ]);
    }

    protected function createTaskStep(WorkflowExecution $execution, array $step, array $variables): array
    {
        $type = $step['assignee_type'] ?? $step['type'] ?? 'agent';
        if (!in_array($type, ['manual', 'agent', 'system'])) {
            $type = 'agent';
        }

        $taskData = [
            'workflow_id' => $execution->workflow_id,
            'agent_id' => $step['agent_id'] ?? null,
            'title' => $step['task_title'] ?? $step['name'],
            'description' => $step['description'] ?? null,
            'priority' => (int)($step['priority'] ?? 3),
            'type' => $type,
            'contact_id' => $step['contact_id'] ?? null,
            'conversation_id' => $step['conversation_id'] ?? null,
            'payload_data' => json_encode([
                'workflow_execution_id' => $execution->id,
                'step_id' => $step['id'],
                'variables' => $variables,
            ]),
        ];

        $taskManagementService = app(\App\Services\TaskManagementService::class);
        $task = $taskManagementService->create($taskData, $execution->user_id ?? null);

        return [
            'success' => true,
            'pause' => (bool) ($step['pause_until_completed'] ?? true),
            'waiting_for' => ['type' => 'task', 'task_id' => $task->id],
            'output' => ['task_id' => $task->id],
        ];
    }

    protected function logStep(WorkflowExecution $execution, array $step, array $variables): array
    {
        $this->logService->info($step['message'] ?? $step['name'], [
            'channel' => 'workflow',
            'type' => 'step_log',
            'related_id' => $execution->workflow_id,
            'related_type' => 'App\Models\Workflow',
            'context' => ['execution_id' => $execution->id, 'step_id' => $step['id'], 'variables' => $variables],
        ]);

        return ['success' => true, 'output' => ['logged' => true]];
    }

    protected function runActionStep(array $step, array $variables): array
    {
        $action = strtolower((string) ($step['action_name'] ?? $step['action'] ?? 'action'));
        $input = $step['input'] ?? [];

        // 1. AI Actions
        if ($action === 'summarize' || $action === 'generate') {
            $gateway = app(\App\Services\AiModelsHub\UniversalAiGatewayService::class);
            $agent = \App\Models\Agent::where('is_active', true)->first() ?? new \App\Models\Agent();

            $systemPrompt = $action === 'summarize'
                ? 'You are an AI assistant tasked with summarizing content clearly and concisely.'
                : 'You are an AI generator. Complete the task as requested.';

            $promptInput = $input['prompt'] ?? $input['text'] ?? $input['content'] ?? null;
            if (empty($promptInput)) {
                $promptInput = is_string($input) ? $input : json_encode($input ?: $variables);
            }

            if ($action === 'summarize') {
                $prompt = "Summarize the following: " . $promptInput;
            } else {
                $prompt = $promptInput;
            }

            $context = [
                'input' => $prompt,
                'system_prompt' => $systemPrompt,
            ];

            try {
                $result = $gateway->executeWithAgent($agent, $context);
                return [
                    'success' => true,
                    'output' => [
                        'action' => $action,
                        'result' => $result['text'] ?? $result['output'] ?? '',
                        'used_model' => $result['used_model'] ?? 'unknown',
                        'used_provider' => $result['used_provider'] ?? 'unknown',
                    ],
                ];
            } catch (\Throwable $e) {
                return [
                    'success' => false,
                    'error' => 'AI execution failed: ' . $e->getMessage(),
                    'output' => ['action' => $action, 'variables_snapshot' => $variables],
                ];
            }
        }

        // 2. ContactHub Actions
        if (in_array($action, ['create_contact', 'update_contact', 'enrich_contact'])) {
            $contactHub = app(\App\Services\ContactHubService::class);
            try {
                if ($action === 'create_contact') {
                    $contact = \App\Models\Contact::create([
                        'name' => $input['name'] ?? 'New Workflow Contact',
                        'email' => $input['email'] ?? null,
                        'phone' => $input['phone'] ?? null,
                        'type' => $input['type'] ?? 'individual',
                        'metadata' => $input['metadata'] ?? [],
                        'attributes' => $input['attributes'] ?? [],
                    ]);
                    $contactHub->syncContactDetails($contact);
                    return [
                        'success' => true,
                        'output' => [
                            'action' => $action,
                            'contact_id' => $contact->id,
                            'contact_name' => $contact->name,
                        ],
                    ];
                }

                if ($action === 'update_contact') {
                    $contactId = $input['contact_id'] ?? $variables['contact_id'] ?? null;
                    if (!$contactId) {
                        throw new \InvalidArgumentException('Missing contact_id for update_contact action.');
                    }
                    $contact = \App\Models\Contact::findOrFail($contactId);
                    $contact->update(array_filter([
                        'name' => $input['name'] ?? null,
                        'email' => $input['email'] ?? null,
                        'phone' => $input['phone'] ?? null,
                        'type' => $input['type'] ?? null,
                    ]));
                    if (isset($input['metadata'])) {
                        $contact->metadata = array_merge($contact->metadata ?? [], $input['metadata']);
                        $contact->save();
                    }
                    $contactHub->syncContactDetails($contact);
                    return [
                        'success' => true,
                        'output' => [
                            'action' => $action,
                            'contact_id' => $contact->id,
                        ],
                    ];
                }

                if ($action === 'enrich_contact') {
                    $contactId = $input['contact_id'] ?? $variables['contact_id'] ?? null;
                    if (!$contactId) {
                        throw new \InvalidArgumentException('Missing contact_id for enrich_contact action.');
                    }
                    $contact = \App\Models\Contact::findOrFail($contactId);
                    $enriched = $contactHub->enrichContact($contact, $input, 'workflow');
                    return [
                        'success' => true,
                        'output' => [
                            'action' => $action,
                            'contact_id' => $enriched->id,
                        ],
                    ];
                }
            } catch (\Throwable $e) {
                return [
                    'success' => false,
                    'error' => 'Contact operation failed: ' . $e->getMessage(),
                    'output' => ['action' => $action, 'variables_snapshot' => $variables],
                ];
            }
        }

        return [
            'success' => true,
            'output' => [
                'action' => $step['action_name'] ?? $step['action'] ?? 'action',
                'input' => $step['input'] ?? [],
                'variables_snapshot' => $variables,
            ],
        ];
    }

    protected function runCodeStep(array $step, array $variables): array
    {
        return [
            'success' => false,
            'error' => 'Code step execution requires a dedicated sandbox and is disabled in this runtime.',
            'output' => ['variables_snapshot' => $variables],
        ];
    }
}
