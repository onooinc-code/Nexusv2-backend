<?php

namespace App\Services\Workflows;

use App\Events\WorkflowCompleted;
use App\Events\WorkflowStarted;
use App\Events\WorkflowStepCompleted;
use App\Models\Workflow;
use App\Models\WorkflowExecution;
use App\Models\WorkflowStepLog;
use Illuminate\Support\Arr;

class WorkflowInterpreter
{
    public function __construct(
        protected WorkflowStateManager $stateManager,
        protected WorkflowTaskDispatcher $dispatcher,
        protected WorkflowCompensationEngine $compensationEngine
    ) {}

    public function run(WorkflowExecution $execution): WorkflowExecution
    {
        $execution->loadMissing('workflow', 'version');

        if ($execution->isTerminal()) {
            return $execution;
        }

        $workflow = $execution->workflow;
        $definition = $execution->version?->definition ?? ['steps' => $workflow->steps ?? []];
        $steps = array_values($definition['steps'] ?? []);
        $state = $execution->runtime_state ?? [];
        $state['variables'] ??= $execution->input_payload ?? [];
        $state['current_step_index'] ??= 0;
        $state['depth'] ??= 0;

        $execution = $this->stateManager->markRunning($execution);
        $workflow->setRunning();
        $workflow->incrementExecution();
        $this->emit(new WorkflowStarted((string) $workflow->id, (string) ($execution->user_id ?? ''), $workflow->name));

        try {
            while (($state['current_step_index'] ?? 0) < count($steps)) {
                if (($state['depth'] ?? 0) > (int) data_get($workflow->settings, 'max_execution_depth', 1000)) {
                    throw new \RuntimeException('Workflow execution depth exceeded safety limit.');
                }

                $index = (int) $state['current_step_index'];
                $step = $steps[$index];
                $result = $this->executeStep($execution, $workflow, $step, $state);

                $state['depth']++;
                $state['variables'] = array_merge($state['variables'] ?? [], $result['output'] ?? []);

                if (! ($result['success'] ?? false)) {
                    throw new \RuntimeException($result['error'] ?? "Workflow step failed: {$step['name']}");
                }

                if ($result['pause'] ?? false) {
                    $state['waiting_for'] = $result['waiting_for'] ?? ['type' => 'unknown'];
                    $state['current_step_index'] = $index + 1;
                    return $this->stateManager->pause($execution, $state);
                }

                $state['current_step_index'] = $this->nextStepIndex($steps, $step, $result, $state, $index);
            }

            $execution = $this->stateManager->complete($execution, [
                'variables' => $state['variables'] ?? [],
                'completed_steps' => count($steps),
            ], $state);

            $workflow->recordSuccess();
            $this->emit(new WorkflowCompleted((string) $workflow->id, $execution->output ?? [], ['execution_id' => $execution->id]));

            return $execution;
        } catch (\Throwable $e) {
            $execution = $this->stateManager->fail($execution, $e->getMessage(), $state);
            $workflow->recordError();

            // Trigger compensation rollback
            try {
                $this->compensationEngine->compensate($execution);
            } catch (\Throwable $compEx) {
                // Ignore compensation errors as they are logged inside the engine
            }

            return $execution;
        }
    }

    protected function executeStep(WorkflowExecution $execution, Workflow $workflow, array $step, array $state): array
    {
        $startedAt = microtime(true);
        $log = WorkflowStepLog::create([
            'execution_id' => $execution->id,
            'workflow_id' => $workflow->id,
            'step_id' => $step['id'],
            'step_name' => $step['name'],
            'step_type' => $step['type'],
            'status' => 'running',
            'input' => $state['variables'] ?? [],
            'started_at' => now(),
        ]);

        try {
            $result = $this->runStepByType($execution, $step, $state);
            $durationMs = (int) ((microtime(true) - $startedAt) * 1000);
            $status = ($result['success'] ?? false) ? (($result['pause'] ?? false) ? 'paused' : 'completed') : 'failed';

            $log->update([
                'status' => $status,
                'output' => $result['output'] ?? $result,
                'error' => $result['error'] ?? null,
                'duration_ms' => $durationMs,
                'completed_at' => now(),
            ]);

            $this->emit(new WorkflowStepCompleted(
                (string) $workflow->id,
                (string) $step['id'],
                (string) $step['name'],
                $status,
                $result,
                ['execution_id' => $execution->id, 'duration_ms' => $durationMs]
            ));

            return $result;
        } catch (\Throwable $e) {
            $durationMs = (int) ((microtime(true) - $startedAt) * 1000);
            $log->update([
                'status' => 'failed',
                'error' => $e->getMessage(),
                'duration_ms' => $durationMs,
                'completed_at' => now(),
            ]);

            $this->emit(new WorkflowStepCompleted(
                (string) $workflow->id,
                (string) $step['id'],
                (string) $step['name'],
                'failed',
                ['success' => false, 'error' => $e->getMessage()],
                ['execution_id' => $execution->id, 'duration_ms' => $durationMs]
            ));

            throw $e;
        }
    }

    protected function runStepByType(WorkflowExecution $execution, array $step, array $state): array
    {
        $type = strtolower((string) ($step['type'] ?? 'action'));

        return match ($type) {
            'decision' => $this->runDecision($step, $state),
            'parallel' => $this->runParallel($execution, $step, $state),
            'wait' => $this->runWait($step),
            'loop' => $this->runLoop($execution, $step, $state),
            'compensate' => $this->dispatcher->dispatch($execution, $step, $state['variables'] ?? []),
            default => $this->dispatcher->dispatch($execution, $step, $state['variables'] ?? []),
        };
    }

    protected function runDecision(array $step, array $state): array
    {
        $condition = $step['condition'] ?? [];
        $field = $condition['field'] ?? null;
        $operator = $condition['operator'] ?? '==';
        $value = $condition['value'] ?? null;
        $actual = $field ? Arr::get($state['variables'] ?? [], $field) : null;

        $matched = match ($operator) {
            '==' => $actual == $value,
            '===' => $actual === $value,
            '!=' => $actual != $value,
            '!==' => $actual !== $value,
            '>' => $actual > $value,
            '<' => $actual < $value,
            '>=' => $actual >= $value,
            '<=' => $actual <= $value,
            'contains' => is_string($actual) && str_contains($actual, (string) $value),
            'in' => in_array($actual, (array) $value, true),
            default => false,
        };

        return [
            'success' => true,
            'decision' => $matched,
            'next_step' => $matched ? ($step['then'] ?? $step['next_step'] ?? null) : ($step['else'] ?? $step['else_step'] ?? null),
            'output' => ['decision' => $matched],
        ];
    }

    protected function runParallel(WorkflowExecution $execution, array $step, array $state): array
    {
        $branches = $step['branches'] ?? [];
        $outputs = [];

        foreach ($branches as $branchIndex => $branchStep) {
            $branchStep['id'] ??= $step['id'] . '_branch_' . ($branchIndex + 1);
            $branchStep['name'] ??= $step['name'] . ' Branch ' . ($branchIndex + 1);
            $branchStep['type'] ??= 'action';

            \App\Jobs\ExecuteWorkflowStepJob::dispatch(
                $execution->id,
                $branchStep,
                $state['variables'] ?? []
            );

            $outputs[$branchStep['id']] = [
                'status' => 'queued',
                'step_id' => $branchStep['id']
            ];
        }

        return ['success' => true, 'output' => ['parallel' => $outputs]];
    }

    protected function runWait(array $step): array
    {
        if (($step['approval'] ?? false) || ($step['wait_for'] ?? null) === 'approval') {
            return [
                'success' => true,
                'pause' => true,
                'waiting_for' => ['type' => 'approval', 'step_id' => $step['id']],
                'output' => ['paused_for_approval' => true],
            ];
        }

        return [
            'success' => true,
            'pause' => true,
            'waiting_for' => [
                'type' => 'time',
                'resume_at' => $step['resume_at'] ?? now()->addSeconds((int) ($step['duration'] ?? 60))->toISOString(),
            ],
            'output' => ['paused' => true],
        ];
    }

    protected function runLoop(WorkflowExecution $execution, array $step, array $state): array
    {
        $items = Arr::get($state['variables'] ?? [], $step['collection'] ?? '', []);
        $maxIterations = (int) ($step['max_iterations'] ?? 1000);
        $child = $step['step'] ?? $step['body'] ?? null;
        $outputs = [];

        if (! is_array($items)) {
            return ['success' => false, 'error' => 'Loop collection is not an array.'];
        }

        if (count($items) > $maxIterations) {
            return ['success' => false, 'error' => 'Loop iteration limit exceeded.'];
        }

        if (! $child) {
            return ['success' => true, 'output' => ['loop_count' => count($items)]];
        }

        foreach ($items as $index => $item) {
            $loopVariables = array_merge($state['variables'] ?? [], ['loop_item' => $item, 'loop_index' => $index]);
            $outputs[] = $this->dispatcher->dispatch($execution, $child, $loopVariables);
        }

        return ['success' => true, 'output' => ['loop_results' => $outputs]];
    }

    protected function nextStepIndex(array $steps, array $step, array $result, array $state, int $currentIndex): int
    {
        $target = $result['next_step'] ?? $step['next_step'] ?? null;
        if ($target) {
            foreach ($steps as $index => $candidate) {
                if (($candidate['id'] ?? null) === $target) {
                    return $index;
                }
            }
        }

        return $currentIndex + 1;
    }

    protected function emit(object $event): void
    {
        try {
            event($event);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
