<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\AgentTask;
use App\Services\LogService;

class AgentToolExecutor
{
    protected AgentToolRegistry $registry;
    protected array $executionHistory = [];

    public function __construct(AgentToolRegistry $registry, protected LogService $logService)
    {
        $this->registry = $registry;
    }

    public function executeTool(Agent $agent, string $toolName, array $params = []): array
    {
        $startTime = microtime(true);

        try {
            if (!$this->registry->has($toolName)) {
                throw new \InvalidArgumentException("Tool not registered: {$toolName}");
            }

            $result = $this->registry->execute($toolName, $params);
            $durationMs = round((microtime(true) - $startTime) * 1000, 2);

            $this->executionHistory[] = [
                'agent_id' => $agent->id,
                'tool' => $toolName,
                'params' => $params,
                'success' => true,
                'result' => $result,
                'duration_ms' => $durationMs,
                'executed_at' => now()->toISOString(),
            ];

            $this->logService->info("Tool executed successfully: {$toolName} by agent {$agent->name}", [
                'channel' => 'agent',
                'type' => 'tool',
                'related_id' => $agent->id,
                'related_type' => Agent::class,
                'context' => ['tool' => $toolName, 'duration_ms' => $durationMs],
            ]);

            return [
                'success' => true,
                'tool' => $toolName,
                'result' => $result,
                'duration_ms' => $durationMs,
            ];
        } catch (\Throwable $e) {
            $durationMs = round((microtime(true) - $startTime) * 1000, 2);

            $this->executionHistory[] = [
                'agent_id' => $agent->id,
                'tool' => $toolName,
                'params' => $params,
                'success' => false,
                'error' => $e->getMessage(),
                'duration_ms' => $durationMs,
                'executed_at' => now()->toISOString(),
            ];

            $this->logService->error("Tool execution failed: {$toolName} by agent {$agent->name}", [
                'channel' => 'agent',
                'type' => 'tool',
                'related_id' => $agent->id,
                'related_type' => Agent::class,
                'context' => ['tool' => $toolName, 'error' => $e->getMessage(), 'duration_ms' => $durationMs],
            ]);

            return [
                'success' => false,
                'tool' => $toolName,
                'error' => $e->getMessage(),
                'duration_ms' => $durationMs,
            ];
        }
    }

    public function executeTools(Agent $agent, array $toolCalls): array
    {
        $results = [];

        foreach ($toolCalls as $toolCall) {
            $toolName = $toolCall['tool'] ?? $toolCall['name'] ?? null;
            $params = $toolCall['params'] ?? $toolCall['parameters'] ?? [];

            if (!$toolName) {
                $results[] = [
                    'success' => false,
                    'error' => 'Missing tool name in tool call',
                ];
                continue;
            }

            $results[] = $this->executeTool($agent, $toolName, $params);
        }

        return $results;
    }

    public function getExecutionHistory(): array
    {
        return $this->executionHistory;
    }

    public function clearHistory(): void
    {
        $this->executionHistory = [];
    }

    public function getSuccessRate(string $toolName = null): float
    {
        $history = $this->executionHistory;

        if ($toolName) {
            $history = array_filter($history, fn($h) => $h['tool'] === $toolName);
        }

        $total = count($history);
        if ($total === 0) return 0.0;

        $successful = count(array_filter($history, fn($h) => $h['success']));
        return round(($successful / $total) * 100, 2);
    }
}