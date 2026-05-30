<?php

namespace App\Agents;

use App\Models\Agent;
use App\Models\AgentTask;
use App\Services\AgentLifecycleService;
use Illuminate\Support\Facades\Log;

class ReflectionAgent
{
    protected Agent $agent;
    protected AgentLifecycleService $lifecycle;
    protected array $reflectionHistory = [];

    public function __construct(Agent $agent)
    {
        $this->agent = $agent;
        $this->lifecycle = app(AgentLifecycleService::class);
    }

    public function execute(array $context = []): array
    {
        $this->lifecycle->initialize($this->agent);

        try {
            $pastActions = $this->getPastActions(limit: 10);
            $reflection = $this->reflectOnActions($pastActions);
            $improvements = $this->generateImprovements($reflection);

            $this->recordReflection($reflection, $improvements);
            $this->lifecycle->complete($this->agent);

            return [
                'success' => true,
                'reflection' => $reflection,
                'improvements' => $improvements,
                'actions_analyzed' => count($pastActions),
            ];
        } catch (\Throwable $e) {
            $this->lifecycle->fail($this->agent, $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    protected function getPastActions(int $limit = 10): array
    {
        return AgentTask::where('agent_id', $this->agent->id)
            ->whereIn('status', ['completed', 'failed'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    protected function reflectOnActions(array $actions): array
    {
        $total = count($actions);
        $successful = collect($actions)->where('status', 'completed')->count();
        $failed = collect($actions)->where('status', 'failed')->count();

        $patterns = [];
        if ($total > 0) {
            $successRate = ($successful / $total) * 100;
            $patterns['success_rate'] = round($successRate, 2);
            $patterns['total_actions'] = $total;
            $patterns['successful_actions'] = $successful;
            $patterns['failed_actions'] = $failed;
        }

        $commonFailures = collect($actions)
            ->where('status', 'failed')
            ->pluck('error')
            ->filter()
            ->countBy()
            ->sortDesc()
            ->take(3)
            ->toArray();

        $patterns['common_failures'] = $commonFailures;

        return $patterns;
    }

    protected function generateImprovements(array $reflection): array
    {
        $improvements = [];

        if (isset($reflection['success_rate']) && $reflection['success_rate'] < 70) {
            $improvements[] = "Success rate is below 70%. Review error patterns and adjust execution strategy.";
        }

        if (!empty($reflection['common_failures'])) {
            $improvements[] = "Address recurring failure patterns: " . implode(', ', array_keys($reflection['common_failures']));
        }

        if ($reflection['total_actions'] > 0 && $reflection['success_rate'] >= 90) {
            $improvements[] = "Excellent performance. Consider increasing task complexity or delegation scope.";
        }

        return $improvements;
    }

    protected function recordReflection(array $reflection, array $improvements): void
    {
        $this->reflectionHistory[] = [
            'timestamp' => now()->toISOString(),
            'reflection' => $reflection,
            'improvements' => $improvements,
        ];

        Log::info("Reflection recorded for agent {$this->agent->name}", [
            'success_rate' => $reflection['success_rate'] ?? null,
            'improvements_count' => count($improvements),
        ]);
    }

    public function getReflectionHistory(): array
    {
        return $this->reflectionHistory;
    }

    public function getAgent(): Agent
    {
        return $this->agent;
    }
}
