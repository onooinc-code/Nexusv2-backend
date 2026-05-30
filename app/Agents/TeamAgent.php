<?php

namespace App\Agents;

use App\Models\Agent;
use App\Models\AgentTask;
use App\Services\AgentLifecycleService;
use App\Services\AgentRegistry;
use Illuminate\Support\Facades\Log;

class TeamAgent
{
    protected Agent $agent;
    protected AgentLifecycleService $lifecycle;
    protected AgentRegistry $registry;
    protected array $teamMembers = [];
    protected array $executionResults = [];

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
            $members = $context['members'] ?? [];

            if (empty($members)) {
                throw new \InvalidArgumentException('Team agent requires at least one member');
            }

            $this->teamMembers = $this->resolveTeamMembers($members);
            $results = $this->coordinateExecution($task, $context);

            $this->lifecycle->complete($this->agent);

            return [
                'success' => true,
                'team_size' => count($this->teamMembers),
                'results' => $results,
                'execution_summary' => $this->buildExecutionSummary($results),
            ];
        } catch (\Throwable $e) {
            $this->lifecycle->fail($this->agent, $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    protected function resolveTeamMembers(array $memberTypes): array
    {
        $members = [];
        foreach ($memberTypes as $memberType) {
            if ($this->registry->has($memberType)) {
                $members[] = [
                    'type' => $memberType,
                    'class' => $this->registry->getAgentClass($memberType),
                ];
            }
        }
        return $members;
    }

    protected function coordinateExecution(?string $task, array $context): array
    {
        $results = [];

        foreach ($this->teamMembers as $index => $member) {
            Log::info("Team member executing: {$member['type']}");

            $memberContext = array_merge($context, [
                'task' => $task,
                'team_index' => $index,
                'total_members' => count($this->teamMembers),
            ]);

            try {
                $agent = Agent::where('type', $member['type'])->first();
                if ($agent) {
                    $instance = $this->registry->resolve($agent);
                    $result = $instance->execute($memberContext);
                    $results[] = [
                        'member_type' => $member['type'],
                        'success' => $result['success'] ?? false,
                        'result' => $result,
                    ];
                }
            } catch (\Throwable $e) {
                $results[] = [
                    'member_type' => $member['type'],
                    'success' => false,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    protected function buildExecutionSummary(array $results): array
    {
        $total = count($results);
        $successful = collect($results)->where('success', true)->count();
        $failed = $total - $successful;

        return [
            'total_members' => $total,
            'successful' => $successful,
            'failed' => $failed,
            'success_rate' => $total > 0 ? round(($successful / $total) * 100, 2) : 0,
        ];
    }

    public function getTeamMembers(): array
    {
        return $this->teamMembers;
    }

    public function getExecutionResults(): array
    {
        return $this->executionResults;
    }

    public function getAgent(): Agent
    {
        return $this->agent;
    }
}
