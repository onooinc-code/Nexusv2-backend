<?php

namespace App\Agents;

use App\Models\Agent;
use App\Models\AgentTask;
use App\Services\AgentLifecycleService;
use App\Services\AgentConfigurationService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AutonomousAgent
{
    protected Agent $agent;
    protected AgentLifecycleService $lifecycle;
    protected AgentConfigurationService $config;
    protected array $executionLog = [];
    protected bool $shouldStop = false;

    public function __construct(Agent $agent)
    {
        $this->agent = $agent;
        $this->lifecycle = app(AgentLifecycleService::class);
        $this->config = app(AgentConfigurationService::class);
    }

    public function execute(array $context = []): array
    {
        $this->lifecycle->initialize($this->agent);
        $this->shouldStop = false;

        try {
            $task = $context['task'] ?? null;
            $maxIterations = $this->config->get($this->agent, 'max_execution_time', 10);
            $iteration = 0;

            while ($iteration < $maxIterations && !$this->shouldStop) {
                $iteration++;
                Log::info("Autonomous agent iteration {$iteration} for {$this->agent->name}");

                $result = $this->executeIteration($task, $context, $iteration);
                $this->executionLog[] = $result;

                if ($result['should_stop'] ?? false) {
                    $this->shouldStop = true;
                }

                if ($result['success'] ?? false) {
                    $this->lifecycle->complete($this->agent);
                    return [
                        'success' => true,
                        'iterations' => $iteration,
                        'log' => $this->executionLog,
                        'final_result' => $result,
                    ];
                }
            }

            if ($this->shouldStop) {
                $this->lifecycle->complete($this->agent);
            } else {
                $this->lifecycle->fail($this->agent, 'Max iterations reached');
            }

            return [
                'success' => !$this->shouldStop,
                'iterations' => $iteration,
                'log' => $this->executionLog,
                'final_result' => $this->executionLog[count($this->executionLog) - 1] ?? null,
            ];
        } catch (\Throwable $e) {
            $this->lifecycle->fail($this->agent, $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'log' => $this->executionLog,
            ];
        }
    }

    protected function executeIteration(?string $task, array $context, int $iteration): array
    {
        $startTime = microtime(true);

        try {
            $decision = $this->makeDecision($task, $context, $iteration);

            if ($decision['action'] === 'complete') {
                return [
                    'iteration' => $iteration,
                    'success' => true,
                    'should_stop' => true,
                    'action' => 'complete',
                    'result' => $decision['result'] ?? null,
                    'duration_ms' => round((microtime(true) - $startTime) * 1000, 2),
                ];
            }

            if ($decision['action'] === 'stop') {
                return [
                    'iteration' => $iteration,
                    'success' => false,
                    'should_stop' => true,
                    'action' => 'stop',
                    'reason' => $decision['reason'] ?? 'Unknown',
                    'duration_ms' => round((microtime(true) - $startTime) * 1000, 2),
                ];
            }

            $executionResult = $this->executeAction($decision, $context);

            return [
                'iteration' => $iteration,
                'success' => $executionResult['success'] ?? false,
                'should_stop' => false,
                'action' => $decision['action'],
                'decision' => $decision,
                'result' => $executionResult,
                'duration_ms' => round((microtime(true) - $startTime) * 1000, 2),
            ];
        } catch (\Throwable $e) {
            return [
                'iteration' => $iteration,
                'success' => false,
                'should_stop' => true,
                'action' => 'error',
                'error' => $e->getMessage(),
                'duration_ms' => round((microtime(true) - $startTime) * 1000, 2),
            ];
        }
    }

    protected function makeDecision(?string $task, array $context, int $iteration): array
    {
        if ($task === null) {
            return ['action' => 'stop', 'reason' => 'No task provided'];
        }

        $taskLower = Str::lower($task);

        if (Str::contains($taskLower, 'complete') || Str::contains($taskLower, 'finish')) {
            return ['action' => 'complete', 'result' => 'Task completed autonomously'];
        }

        if (Str::contains($taskLower, 'fail') || Str::contains($taskLower, 'error')) {
            return ['action' => 'stop', 'reason' => 'Task indicates failure condition'];
        }

        return ['action' => 'process', 'task' => $task, 'iteration' => $iteration];
    }

    protected function executeAction(array $decision, array $context): array
    {
        $action = $decision['action'];

        return match ($action) {
            'process' => [
                'success' => true,
                'action' => 'processed',
                'output' => "Processed: {$decision['task']}",
            ],
            default => [
                'success' => false,
                'action' => 'unknown',
                'error' => "Unknown action: {$action}",
            ],
        };
    }

    public function stop(): void
    {
        $this->shouldStop = true;
    }

    public function getExecutionLog(): array
    {
        return $this->executionLog;
    }

    public function getAgent(): Agent
    {
        return $this->agent;
    }
}
