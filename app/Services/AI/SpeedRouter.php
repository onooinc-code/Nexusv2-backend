<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Log;

class SpeedRouter
{
    protected ModelSelector $selector;
    protected array $speedTiers = [
        'instant' => ['max_latency_ms' => 300, 'description' => 'Sub-300ms for real-time'],
        'fast' => ['max_latency_ms' => 800, 'description' => 'Under 800ms for interactive'],
        'normal' => ['max_latency_ms' => 2000, 'description' => 'Under 2s for standard tasks'],
        'batch' => ['max_latency_ms' => 10000, 'description' => 'Under 10s for batch processing'],
    ];

    public function __construct(ModelSelector $selector)
    {
        $this->selector = $selector;
    }

    public function route(string $tier, array $request): array
    {
        if (!isset($this->speedTiers[$tier])) {
            return [
                'success' => false,
                'error' => "Unknown speed tier: {$tier}",
                'available_tiers' => array_keys($this->speedTiers),
            ];
        }

        $tierConfig = $this->speedTiers[$tier];
        $criteria = ['max_latency_ms' => $tierConfig['max_latency_ms']];

        if (isset($request['model'])) {
            $criteria['model'] = $request['model'];
        }
        if (isset($request['provider'])) {
            $criteria['provider'] = $request['provider'];
        }

        $selection = $this->selector->select($criteria);

        if (!$selection) {
            return [
                'success' => false,
                'error' => "No model found for speed tier: {$tier}",
                'tier' => $tier,
                'max_latency_ms' => $tierConfig['max_latency_ms'],
            ];
        }

        $result = $selection['provider']->execute($request);
        $result['speed_tier'] = $tier;
        $result['tier_description'] = $tierConfig['description'];
        $result['selected_model'] = $selection['model'];
        $result['selected_provider'] = $selection['provider']->getProviderName();

        return $result;
    }

    public function getTierForRequest(array $request): string
    {
        $latencyRequirement = $request['max_latency_ms'] ?? null;

        if ($latencyRequirement !== null) {
            foreach ($this->speedTiers as $tier => $config) {
                if ($latencyRequirement <= $config['max_latency_ms']) {
                    return $tier;
                }
            }
        }

        $interactive = $request['interactive'] ?? false;
        if ($interactive) return 'fast';

        $realtime = $request['realtime'] ?? false;
        if ($realtime) return 'instant';

        return 'normal';
    }

    public function getAvailableTiers(): array
    {
        $tiers = [];
        foreach ($this->speedTiers as $name => $config) {
            $tiers[$name] = [
                'description' => $config['description'],
                'max_latency_ms' => $config['max_latency_ms'],
            ];
        }
        return $tiers;
    }

    public function autoRoute(array $request): array
    {
        $tier = $this->getTierForRequest($request);
        return $this->route($tier, $request);
    }
}
