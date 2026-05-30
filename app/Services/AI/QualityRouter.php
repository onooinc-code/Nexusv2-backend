<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Log;

class QualityRouter
{
    protected ModelSelector $selector;
    protected array $qualityTiers = [
        'critical' => ['min_quality' => 90, 'description' => 'Highest quality for critical tasks'],
        'high' => ['min_quality' => 80, 'description' => 'High quality for important tasks'],
        'standard' => ['min_quality' => 65, 'description' => 'Standard quality for regular tasks'],
        'low' => ['min_quality' => 0, 'description' => 'Lowest quality for simple tasks'],
    ];

    public function __construct(ModelSelector $selector)
    {
        $this->selector = $selector;
    }

    public function route(string $tier, array $request): array
    {
        if (!isset($this->qualityTiers[$tier])) {
            return [
                'success' => false,
                'error' => "Unknown quality tier: {$tier}",
                'available_tiers' => array_keys($this->qualityTiers),
            ];
        }

        $tierConfig = $this->qualityTiers[$tier];
        $criteria = ['min_quality' => $tierConfig['min_quality']];

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
                'error' => "No model found for quality tier: {$tier}",
                'tier' => $tier,
                'min_quality' => $tierConfig['min_quality'],
            ];
        }

        $result = $selection['provider']->execute($request);
        $result['quality_tier'] = $tier;
        $result['tier_description'] = $tierConfig['description'];
        $result['selected_model'] = $selection['model'];
        $result['selected_provider'] = $selection['provider']->getProviderName();

        return $result;
    }

    public function getTierForRequest(array $request): string
    {
        $complexity = $request['complexity'] ?? 'standard';
        $importance = $request['importance'] ?? 'standard';

        if ($complexity === 'high' || $importance === 'critical') {
            return 'critical';
        }
        if ($complexity === 'medium' || $importance === 'high') {
            return 'high';
        }
        if ($complexity === 'low' || $importance === 'low') {
            return 'low';
        }

        return 'standard';
    }

    public function getAvailableTiers(): array
    {
        $tiers = [];
        foreach ($this->qualityTiers as $name => $config) {
            $tiers[$name] = [
                'description' => $config['description'],
                'min_quality' => $config['min_quality'],
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
