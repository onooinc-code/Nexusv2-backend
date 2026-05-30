<?php

namespace App\Agents;

use App\Models\Agent;
use App\Models\AgentTask;
use App\Services\AgentLifecycleService;
use App\Services\AgentRegistry;
use Illuminate\Support\Facades\Log;

class SupervisorAgent
{
    protected Agent $agent;
    protected AgentLifecycleService $lifecycle;
    protected AgentRegistry $registry;
    protected array $supervisedAgents = [];
    protected array $conflictResolutions = [];

    public function __construct(Agent $agent)
    {
        $this->agent = $agent;
        $this->lifecycle = app(AgentLifecycleService::class);
        $this->registry = app(AgentRegistry::class);
    }

    public function execute(array $context = []): array
    {
        $this->lifecycle->initialize($this->agent);

        try {
            $task = $context['task'] ?? null;
            $agentIds = $context['agent_ids'] ?? [];

            if (empty($agentIds)) {
                throw new \InvalidArgumentException('Supervisor agent requires agent IDs to supervise');
            }

            $this->supervisedAgents = $this->loadSupervisedAgents($agentIds);
            $results = $this->coordinateAndResolve($task, $context);

            $this->lifecycle->complete($this->agent);

            return [
                'success' => true,
                'supervised_count' => count($this->supervisedAgents),
                'results' => $results,
                'conflicts_resolved' => count($this->conflictResolutions),
            ];
        } catch (\Throwable $e) {
            $this->lifecycle->fail($this->agent, $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    protected function loadSupervisedAgents(array $agentIds): array
    {
        $agents = Agent::whereIn('id', $agentIds)->where('is_active', true)->get();
        return $agents->toArray();
    }

    protected function coordinateAndResolve(?string $task, array $context): array
    {
        $results = [];
        $agentOutputs = [];

        foreach ($this->supervisedAgents as $agentData) {
            $agent = Agent::find($agentData['id']);
            if (!$agent) continue;

            try {
                $instance = $this->registry->resolve($agent);
                $result = $instance->execute(array_merge($context, ['task' => $task]));
                $agentOutputs[$agent->type] = $result;
                $results[] = [
                    'agent_id' => $agent->id,
                    'agent_type' => $agent->type,
                    'success' => $result['success'] ?? false,
                    'output' => $result,
                ];
            } catch (\Throwable $e) {
                $results[] = [
                    'agent_id' => $agent->id,
                    'agent_type' => $agent->type,
                    'success' => false,
                    'error' => $e->getMessage(),
                ];
            }
        }

        $conflicts = $this->detectConflicts($agentOutputs);
        if (!empty($conflicts)) {
            $resolutions = $this->resolveConflicts($conflicts, $task);
            $this->conflictResolutions = $resolutions;
        }

        return $results;
    }

    protected function detectConflicts(array $outputs): array
    {
        $conflicts = [];
        $successOutputs = collect($outputs)->where('success', true);

        if ($successOutputs->count() < 2) {
            return $conflicts;
        }

        $values = $successOutputs->pluck('result')->toArray();
        $conflictingKeys = [];

        foreach ($values as $index => $output) {
            foreach ($values as $otherIndex => $otherOutput) {
                if ($index >= $otherIndex) continue;

                $diff = $this->compareOutputs($output, $otherOutput);
                if ($diff) {
                    $conflictingKeys = array_merge($conflictingKeys, array_keys($diff));
                }
            }
        }

        $conflictingKeys = array_unique($conflictingKeys);

        foreach ($conflictingKeys as $key) {
            $conflicts[$key] = [
                'key' => $key,
                'values' => collect($values)
                    ->map(fn($o) => $o[$key] ?? null)
                    ->filter()
                    ->unique()
                    ->values()
                    ->toArray(),
            ];
        }

        return $conflicts;
    }

    protected function compareOutputs(array $a, array $b): array
    {
        $diff = [];
        $allKeys = array_unique(array_merge(array_keys($a), array_keys($b)));

        foreach ($allKeys as $key) {
            if ($a[$key] !== $b[$key]) {
                $diff[$key] = [
                    'agent_a' => $a[$key] ?? null,
                    'agent_b' => $b[$key] ?? null,
                ];
            }
        }

        return $diff;
    }

    protected function resolveConflicts(array $conflicts, ?string $task): array
    {
        $resolutions = [];

        foreach ($conflicts as $key => $conflict) {
            $resolution = [
                'key' => $key,
                'conflicting_values' => $conflict['values'],
                'resolution' => $this->selectResolution($conflict, $task),
                'resolved_at' => now()->toISOString(),
            ];

            $resolutions[] = $resolution;
            Log::info("Conflict resolved for key {$key}: " . $resolution['resolution']);
        }

        return $resolutions;
    }

    protected function selectResolution(array $conflict, ?string $task): string
    {
        $values = $conflict['values'];

        if (count($values) === 1) {
            return (string) $values[0];
        }

        if (count($values) === 2) {
            return "Consensus: {$values[0]} vs {$values[1]} - using first value";
        }

        return "Multiple values detected: " . implode(', ', $values) . " - manual review required";
    }

    public function getSupervisedAgents(): array
    {
        return $this->supervisedAgents;
    }

    public function getConflictResolutions(): array
    {
        return $this->conflictResolutions;
    }

    public function getAgent(): Agent
    {
        return $this->agent;
    }
}
