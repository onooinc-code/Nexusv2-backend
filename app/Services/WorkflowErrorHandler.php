<?php

namespace App\Services;

use App\Models\Workflow;
use Illuminate\Support\Facades\Log;

class WorkflowErrorHandler
{
    protected array $errorHistory = [];
    protected array $alertRules = [];

    public function __construct()
    {
        $this->registerDefaultAlertRules();
    }

    public function handleStepFailure(Workflow $workflow, array $step, array $stepResult, array $previousResults = []): array
    {
        $error = $stepResult['error'] ?? 'Unknown error';
        $stepName = $step['name'] ?? $step['title'] ?? 'Unknown step';

        $this->recordError($workflow, $step, $stepResult);

        $maxRetries = $step['max_retries'] ?? $workflow->settings['max_retries'] ?? 3;
        $retryCount = $step['retry_count'] ?? 0;
        $canRetry = $retryCount < $maxRetries;

        $shouldAbort = $this->shouldAbortWorkflow($step, $stepResult, $previousResults);

        $this->checkAlertRules($workflow, $step, $stepResult);

        return [
            'should_retry' => $canRetry && !$shouldAbort,
            'should_abort' => $shouldAbort,
            'retry_count' => $retryCount,
            'max_retries' => $maxRetries,
            'error' => $error,
            'step' => $stepName,
            'handled_at' => now()->toISOString(),
        ];
    }

    protected function shouldAbortWorkflow(array $step, array $stepResult, array $previousResults): bool
    {
        if (($step['abort_on_failure'] ?? $step['critical'] ?? false)) {
            return true;
        }

        $consecutiveFailures = $this->countConsecutiveFailures($previousResults);
        $maxConsecutive = $step['max_consecutive_failures'] ?? 3;
        if ($consecutiveFailures >= $maxConsecutive) {
            return true;
        }

        $totalFailures = count(array_filter($previousResults, fn($r) => !$r['success']));
        $failureThreshold = $step['failure_threshold'] ?? 0.5;
        if (count($previousResults) > 0 && ($totalFailures / count($previousResults)) > $failureThreshold) {
            return true;
        }

        return false;
    }

    protected function countConsecutiveFailures(array $results): int
    {
        $count = 0;
        foreach (array_reverse($results) as $result) {
            if (!($result['success'] ?? false)) {
                $count++;
            } else {
                break;
            }
        }
        return $count;
    }

    protected function recordError(Workflow $workflow, array $step, array $stepResult): void
    {
        $this->errorHistory[] = [
            'workflow_id' => $workflow->id,
            'workflow_name' => $workflow->name,
            'step' => $step['name'] ?? $step['title'] ?? 'Unknown',
            'error' => $stepResult['error'] ?? 'Unknown error',
            'timestamp' => now()->toISOString(),
        ];

        Log::error("Workflow error recorded", [
            'workflow_id' => $workflow->id,
            'step' => $step['name'] ?? 'Unknown',
            'error' => $stepResult['error'] ?? 'Unknown',
        ]);
    }

    protected function registerDefaultAlertRules(): void
    {
        $this->alertRules = [
            'high_failure_rate' => [
                'condition' => fn($workflow, $results) =>
                    count($results) > 5 &&
                    (count(array_filter($results, fn($r) => !$r['success'])) / count($results)) > 0.5,
                'message' => 'Workflow has high failure rate',
                'severity' => 'critical',
            ],
            'step_timeout' => [
                'condition' => fn($workflow, $results) =>
                    collect($results)
                        ->where('success', true)
                        ->pluck('duration_ms')
                        ->filter(fn($d) => $d > 30000)
                        ->isNotEmpty(),
                'message' => 'Workflow step exceeded 30s timeout',
                'severity' => 'warning',
            ],
            'workflow_stalled' => [
                'condition' => fn($workflow, $results) =>
                    count($results) > 10 &&
                    collect($results)
                        ->take(-5)
                        ->every(fn($r) => !$r['success']),
                'message' => 'Workflow appears to be stalled',
                'severity' => 'critical',
            ],
        ];
    }

    protected function checkAlertRules(Workflow $workflow, array $step, array $stepResult): void
    {
        foreach ($this->alertRules as $ruleName => $rule) {
            if (($rule['condition'])($workflow, [$stepResult])) {
                Log::warning("Alert triggered: {$ruleName} - {$rule['message']}", [
                    'workflow_id' => $workflow->id,
                    'step' => $step['name'] ?? 'Unknown',
                    'severity' => $rule['severity'],
                ]);
            }
        }
    }

    public function getErrorHistory(): array
    {
        return $this->errorHistory;
    }

    public function clearHistory(): void
    {
        $this->errorHistory = [];
    }

    public function addAlertRule(string $name, array $rule): void
    {
        $this->alertRules[$name] = $rule;
    }

    public function getAlertRules(): array
    {
        return $this->alertRules;
    }
}
