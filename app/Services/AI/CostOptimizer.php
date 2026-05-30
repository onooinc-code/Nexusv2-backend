<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Log;

class CostOptimizer
{
    protected ModelSelector $selector;
    protected float $budgetLimit = 0.0;
    protected float $spentThisPeriod = 0.0;
    protected string $period = 'monthly';

    public function __construct(ModelSelector $selector)
    {
        $this->selector = $selector;
    }

    public function setBudget(float $limit, string $period = 'monthly'): void
    {
        $this->budgetLimit = $limit;
        $this->period = $period;
    }

    public function recordSpend(float $amount): void
    {
        $this->spentThisPeriod += $amount;
    }

    public function getBudgetStatus(): array
    {
        $remaining = $this->budgetLimit - $this->spentThisPeriod;
        $percentUsed = $this->budgetLimit > 0 ? ($this->spentThisPeriod / $this->budgetLimit) * 100 : 0;

        return [
            'budget_limit' => $this->budgetLimit,
            'spent' => $this->spentThisPeriod,
            'remaining' => $remaining,
            'percent_used' => round($percentUsed, 2),
            'period' => $this->period,
            'status' => $remaining < 0 ? 'over_budget' : ($percentUsed > 90 ? 'critical' : ($percentUsed > 75 ? 'warning' : 'healthy')),
        ];
    }

    public function optimize(array $request, float $maxCostPerRequest = null): array
    {
        $maxCost = $maxCostPerRequest ?? ($this->budgetLimit - $this->spentThisPeriod);

        if ($maxCost <= 0) {
            Log::warning('Budget exhausted, cannot fulfill request within cost constraints');
            return [
                'success' => false,
                'error' => 'Budget exhausted',
                'budget_status' => $this->getBudgetStatus(),
            ];
        }

        $criteria = [
            'max_cost_per_1k' => $maxCost * 2,
        ];

        if (isset($request['model'])) {
            $criteria['model'] = $request['model'];
        }

        $selection = $this->selector->select($criteria);

        if (!$selection) {
            return [
                'success' => false,
                'error' => 'No model found within cost constraints',
                'budget_status' => $this->getBudgetStatus(),
            ];
        }

        $estimatedCost = $selection['provider']->estimateCost(
            $selection['model'],
            $request['estimated_input_tokens'] ?? 1000,
            $request['estimated_output_tokens'] ?? 500
        );

        if ($estimatedCost > $maxCost) {
            return [
                'success' => false,
                'error' => "Estimated cost \${$estimatedCost} exceeds budget of \${$maxCost}",
                'estimated_cost' => $estimatedCost,
                'budget_status' => $this->getBudgetStatus(),
            ];
        }

        return [
            'success' => true,
            'provider' => $selection['provider'],
            'model' => $selection['model'],
            'estimated_cost' => $estimatedCost,
            'budget_status' => $this->getBudgetStatus(),
        ];
    }

    public function suggestCheaperAlternative(string $currentModel, string $currentProvider): ?array
    {
        $provider = $this->selector->getProviderForModel($currentModel);
        if (!$provider) return null;

        $currentCost = $provider->estimateCost($currentModel, 1000, 1000);

        $cheaper = [];
        foreach ($this->selector->getAllModels() as $entry) {
            if ($entry['model'] === $currentModel) continue;

            $altProvider = $this->selector->getProviderForModel($entry['model']);
            if (!$altProvider) continue;

            $cost = $altProvider->estimateCost($entry['model'], 1000, 1000);
            if ($cost < $currentCost * 0.8) {
                $cheaper[] = [
                    'model' => $entry['model'],
                    'provider' => $entry['provider'],
                    'cost_per_1k' => $cost,
                    'savings_pct' => round((1 - $cost / $currentCost) * 100, 1),
                ];
            }
        }

        usort($cheaper, fn($a, $b) => $a['cost_per_1k'] <=> $b['cost_per_1k']);

        return $cheaper[0] ?? null;
    }
}
