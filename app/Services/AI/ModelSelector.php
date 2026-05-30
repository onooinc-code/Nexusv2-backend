<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Log;

class ModelSelector
{
    protected array $providers = [];
    protected array $modelRegistry = [];

    public function __construct(array $providers = [])
    {
        $this->providers = $providers;
        $this->buildModelRegistry();
    }

    public function registerProvider(ProviderInterface $provider): void
    {
        $this->providers[$provider->getProviderName()] = $provider;
        $this->buildModelRegistry();
    }

    protected function buildModelRegistry(): void
    {
        $this->modelRegistry = [];
        foreach ($this->providers as $provider) {
            foreach ($provider->getAvailableModels() as $model) {
                $this->modelRegistry[$model] = [
                    'provider' => $provider->getProviderName(),
                    'model' => $model,
                ];
            }
        }
    }

    public function select(array $criteria = []): ?array
    {
        $providerFilter = $criteria['provider'] ?? null;
        $modelFilter = $criteria['model'] ?? null;
        $capabilities = $criteria['capabilities'] ?? [];
        $maxCost = $criteria['max_cost_per_1k'] ?? null;
        $minQuality = $criteria['min_quality'] ?? null;
        $maxLatency = $criteria['max_latency_ms'] ?? null;

        if ($modelFilter && isset($this->modelRegistry[$modelFilter])) {
            $entry = $this->modelRegistry[$modelFilter];
            $provider = $this->providers[$entry['provider']] ?? null;
            if ($provider) {
                return [
                    'provider' => $provider,
                    'model' => $modelFilter,
                    'score' => 100,
                ];
            }
        }

        $candidates = [];
        foreach ($this->providers as $provider) {
            if ($providerFilter && $provider->getProviderName() !== $providerFilter) {
                continue;
            }

            foreach ($provider->getAvailableModels() as $model) {
                $score = $this->scoreModel($provider, $model, $criteria);
                if ($score === null) continue;

                $candidates[] = [
                    'provider' => $provider,
                    'model' => $model,
                    'score' => $score,
                ];
            }
        }

        if (empty($candidates)) {
            Log::warning('No model candidates found for criteria: ' . json_encode($criteria));
            return null;
        }

        usort($candidates, fn($a, $b) => $b['score'] <=> $a['score']);

        return $candidates[0];
    }

    protected function scoreModel(ProviderInterface $provider, string $model, array $criteria): ?float
    {
        $score = 50.0;
        $maxCost = $criteria['max_cost_per_1k'] ?? null;
        $minQuality = $criteria['min_quality'] ?? null;
        $maxLatency = $criteria['max_latency_ms'] ?? null;

        if ($maxCost !== null) {
            $cost = $provider->estimateCost($model, 1000, 1000);
            if ($cost > $maxCost) return null;
            $score += ($maxCost - $cost) * 10;
        }

        if ($minQuality !== null) {
            $quality = $this->getModelQuality($model);
            if ($quality < $minQuality) return null;
            $score += $quality * 20;
        }

        if ($maxLatency !== null) {
            $latency = $this->getModelLatency($model);
            if ($latency > $maxLatency) return null;
            $score += ($maxLatency - $latency) * 0.1;
        }

        return round($score, 2);
    }

    protected function getModelQuality(string $model): float
    {
        $qualityMap = [
            'claude-3-opus-20240229' => 95,
            'gpt-4o' => 90,
            'claude-3-5-sonnet-20241022' => 88,
            'gpt-4-turbo' => 85,
            'gemini-1.5-pro' => 82,
            'llama-3.3-70b-versatile' => 78,
            'mixtral-8x7b-32768' => 75,
            'gemini-1.5-flash' => 70,
            'claude-3-haiku-20240307' => 68,
            'gpt-4o-mini' => 65,
            'gemini-2.0-flash' => 72,
            'llama-3.1-8b-instant' => 60,
            'gemma2-9b-it' => 55,
            'gpt-3.5-turbo' => 50,
        ];

        return $qualityMap[$model] ?? 50;
    }

    protected function getModelLatency(string $model): float
    {
        $latencyMap = [
            'llama-3.1-8b-instant' => 200,
            'gemini-1.5-flash' => 300,
            'gemini-2.0-flash' => 350,
            'gpt-4o-mini' => 400,
            'claude-3-haiku-20240307' => 450,
            'llama-3.3-70b-versatile' => 500,
            'mixtral-8x7b-32768' => 600,
            'gemma2-9b-it' => 550,
            'gemini-1.5-pro' => 700,
            'gpt-4-turbo' => 800,
            'claude-3-5-sonnet-20241022' => 750,
            'gpt-4o' => 900,
            'claude-3-opus-20240229' => 1000,
            'gpt-3.5-turbo' => 600,
        ];

        return $latencyMap[$model] ?? 500;
    }

    public function getAllModels(): array
    {
        return $this->modelRegistry;
    }

    public function getModelsByProvider(string $providerName): array
    {
        $provider = $this->providers[$providerName] ?? null;
        if (!$provider) return [];

        return array_map(fn($model) => [
            'provider' => $providerName,
            'model' => $model,
        ], $provider->getAvailableModels());
    }

    public function getProviderForModel(string $model): ?ProviderInterface
    {
        $entry = $this->modelRegistry[$model] ?? null;
        if (!$entry) return null;

        return $this->providers[$entry['provider']] ?? null;
    }
}
