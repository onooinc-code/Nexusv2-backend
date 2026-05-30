<?php

namespace App\Agents;

use App\Models\Agent;
use App\Services\AgentLifecycleService;
use App\Services\AgentToolRegistry;
use Illuminate\Support\Facades\Log;

class SpecializedAgent
{
    protected Agent $agent;
    protected AgentLifecycleService $lifecycle;
    protected AgentToolRegistry $toolRegistry;
    protected array $domain = [];
    protected array $executionHistory = [];

    public function __construct(Agent $agent)
    {
        $this->agent = $agent;
        $this->lifecycle = app(AgentLifecycleService::class);
        $this->toolRegistry = app(AgentToolRegistry::class);
        $this->domain = $agent->metadata['domain'] ?? [];
    }

    public function execute(array $context = []): array
    {
        $this->lifecycle->initialize($this->agent);

        try {
            $task = $context['task'] ?? null;
            $domain = $context['domain'] ?? $this->domain;

            if (empty($task)) {
                throw new \InvalidArgumentException('Specialized agent requires a task');
            }

            $relevantTools = $this->identifyRelevantTools($task, $domain);
            $result = $this->executeWithExpertise($task, $relevantTools, $context);

            $this->executionHistory[] = [
                'timestamp' => now()->toISOString(),
                'task' => $task,
                'domain' => $domain,
                'tools_used' => $relevantTools,
                'result' => $result,
            ];

            $this->lifecycle->complete($this->agent);

            return [
                'success' => true,
                'domain' => $domain,
                'tools_used' => $relevantTools,
                'result' => $result,
            ];
        } catch (\Throwable $e) {
            $this->lifecycle->fail($this->agent, $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    protected function identifyRelevantTools(string $task, array $domain): array
    {
        $tools = $this->agent->activeTools();
        $taskLower = strtolower($task);
        $domainLower = array_map('strtolower', $domain);

        return $tools
            ->filter(function ($tool) use ($taskLower, $domainLower) {
                $toolName = strtolower($tool->name);
                $toolDesc = strtolower($tool->description ?? '');

                foreach ($domainLower as $d) {
                    if (str_contains($toolName, $d) || str_contains($toolDesc, $d)) {
                        return true;
                    }
                }

                return str_contains($taskLower, $toolName) || str_contains($toolDesc, $taskLower);
            })
            ->pluck('name')
            ->toArray();
    }

    protected function executeWithExpertise(string $task, array $tools, array $context): array
    {
        $results = [];

        foreach ($tools as $toolName) {
            try {
                $tool = $this->toolRegistry->get($toolName);
                if ($tool) {
                    $result = $this->toolRegistry->execute($toolName, $context);
                    $results[$toolName] = [
                        'success' => true,
                        'result' => $result,
                    ];
                }
            } catch (\Throwable $e) {
                $results[$toolName] = [
                    'success' => false,
                    'error' => $e->getMessage(),
                ];
            }
        }

        if (empty($results)) {
            return [
                'success' => true,
                'message' => "No specific tools matched. Executing with domain expertise: " . implode(', ', $this->domain),
                'domain_expertise' => $this->domain,
            ];
        }

        return [
            'success' => true,
            'tool_results' => $results,
            'tools_executed' => count($results),
        ];
    }

    public function getDomain(): array
    {
        return $this->domain;
    }

    public function getExecutionHistory(): array
    {
        return $this->executionHistory;
    }

    public function getAgent(): Agent
    {
        return $this->agent;
    }
}
