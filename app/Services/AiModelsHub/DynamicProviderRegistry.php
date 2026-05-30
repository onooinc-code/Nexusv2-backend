<?php

namespace App\Services\AiModelsHub;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Models\AIProvider;
use App\Models\AIModel;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use App\Services\AiModelsHub\CacheManager;

class DynamicProviderRegistry
{
    protected $cacheManager;
    protected $keyStorage;
    protected $cacheTTL = 3600; // 1 hour

    public function __construct(CacheManager $cacheManager, EncryptedApiKeyStorage $keyStorage)
    {
        $this->cacheManager = $cacheManager;
        $this->keyStorage = $keyStorage;
    }

    /**
     * Get provider by ID
     */
    public function getProvider($providerId)
    {
        return $this->cacheManager->cacheProvider(
            "provider:{$providerId}",
            function () use ($providerId) {
                return AIProvider::withCount('models')
                    ->find($providerId);
            },
            $this->cacheTTL
        );
    }

    /**
     * Get provider by name
     */
    public function getProviderByName($name)
    {
        return $this->cacheManager->cacheProvider(
            "provider:name:{$name}",
            function () use ($name) {
                return AIProvider::where('name', $name)
                    ->withCount('models')
                    ->first();
            },
            $this->cacheTTL
        );
    }

    /**
     * Register a new provider
     */
    public function registerProvider(array $data)
    {
        $provider = AIProvider::create([
            'id'                    => Str::uuid(),
            'name'                  => $data['name'],
            'base_url'              => $data['base_url'],
            'models_fetch_endpoint' => $data['models_fetch_endpoint'] ?? null,
            'generate_endpoint'     => $data['generate_endpoint'] ?? null,
            'test_endpoint'         => $data['test_endpoint'] ?? null,
            'auth_header_format'    => $data['auth_header_format'] ?? 'Bearer {key}',
            'payload_format'        => $data['payload_format'] ?? 'openai',
            'is_active'             => $data['is_active'] ?? true,
        ]);

        // Clear provider caches
        $this->clearProviderCaches();

        return $provider;
    }

    /**
     * Update an existing provider
     */
    public function updateProvider($providerId, array $data)
    {
        $provider = AIProvider::findOrFail($providerId);
        $provider->update($data);

        // Clear provider caches
        $this->clearProviderCaches();

        return $provider;
    }

    /**
     * Delete a provider
     */
    public function deleteProvider($providerId)
    {
        $provider = AIProvider::findOrFail($providerId);
        $provider->delete();

        // Clear provider caches
        $this->clearProviderCaches();

        return true;
    }

    /**
     * Sync models for a provider
     */
    public function syncModels($providerId)
    {
        $provider = $this->getProvider($providerId);

        if (!$provider) {
            throw new \Exception("Provider not found");
        }

        if (!$provider->models_fetch_endpoint) {
            throw new \Exception("Provider does not support model synchronization");
        }

        try {
            // Fetch models from provider's API using DynamicRestProvider
            $restProvider = new DynamicRestProvider($providerId, $this->keyStorage);
            $models = $restProvider->getAvailableModels();

            if (empty($models)) {
                return [
                    'success' => false,
                    'synced_count' => 0
                ];
            }

            // Sync with registry or database
            foreach ($models as $modelData) {
                $existing = AIModel::where('name', $modelData['id'])
                    ->where('provider_id', $providerId)
                    ->first();
                if ($existing) {
                    $existing->update(['last_synced_at' => now()]);
                } else {
                    AIModel::create([
                        'id' => (string) \Illuminate\Support\Str::uuid(),
                        'name' => $modelData['id'],
                        'provider_id' => $providerId,
                        'last_synced_at' => now(),
                    ]);
                }
            }

            // Update provider's last synced timestamp
            $provider->update([
                'last_synced_at' => now()
            ]);

            // Clear model caches
            $this->clearModelCaches($providerId);

            return [
                'success' => true,
                'synced_count' => count($models)
            ];
        } catch (\Exception $e) {
            Log::error("Error syncing models for provider {$provider->name}: {$e->getMessage()}");
            throw $e;
        }
    }

    /**
     * Process and store models from API response
     */
    protected function processModels($providerId, $modelsData)
    {
        // Clear existing models for this provider (optional - could be configurable)
        // AIModel::where('provider_id', $providerId)->delete();

        // Handle different response formats
        $models = $this->normalizeModelsResponse($modelsData);

        foreach ($models as $modelData) {
            AIModel::updateOrCreate(
                [
                    'provider_id' => $providerId,
                    'external_id' => $modelData['external_id'] ?? $modelData['id'] ?? null
                ],
                [
                    'id' => Str::uuid(),
                    'name' => $modelData['name'],
                    'context_window' => $modelData['context_window'] ?? 4096,
                    'input_cost_per_m' => $modelData['input_cost_per_m'] ?? 0.0,
                    'output_cost_per_m' => $modelData['output_cost_per_m'] ?? 0.0,
                    'description' => $modelData['description'] ?? null,
                    'capabilities' => $modelData['capabilities'] ?? [],
                    'metadata' => $modelData['metadata'] ?? [],
                ]
            );
        }
    }

    /**
     * Normalize different provider response formats
     */
    protected function normalizeModelsResponse($data)
    {
        // Handle OpenAI-like format: { data: [{ id: 'gpt-4', ... }] }
        if (isset($data['data']) && is_array($data['data'])) {
            return $this->mapOpenAIFormat($data['data']);
        }

        // Handle direct array format: [{ id: 'gpt-4', ... }, ...]
        if (is_array($data) && isset($data[0]) && is_array($data[0])) {
            return $this->mapOpenAIFormat($data);
        }

        // Handle Anthropic-like format
        if (isset($data['models']) && is_array($data['models'])) {
            return $this->mapAnthropicFormat($data['models']);
        }

        // Return as-is if we can't normalize
        return is_array($data) ? $data : [];
    }

    protected function mapOpenAIFormat($models)
    {
        return array_map(function($model) {
            return [
                'external_id' => $model['id'] ?? null,
                'name' => $model['id'] ?? $model['name'] ?? 'Unknown Model',
                'context_window' => $model['context_length'] ?? $model['max_tokens'] ?? 4096,
                'input_cost_per_m' => isset($model['pricing']) && isset($model['pricing']['input']) 
                    ? $model['pricing']['input'] * 1000000 
                    : 0.0,
                'output_cost_per_m' => isset($model['pricing']) && isset($model['pricing']['output']) 
                    ? $model['pricing']['output'] * 1000000 
                    : 0.0,
                'description' => $model['description'] ?? null,
                'capabilities' => isset($model['capabilities']) ? $model['capabilities'] : [],
                'metadata' => $model,
            ];
        }, $models);
    }

    protected function mapAnthropicFormat($models)
    {
        return array_map(function($model) {
            return [
                'external_id' => $model['id'] ?? null,
                'name' => $model['display_name'] ?? $model['id'] ?? 'Unknown Model',
                'context_window' => $model['context_window'] ?? 4096,
                'input_cost_per_m' => isset($model['pricing']) && isset($model['pricing']['input']) 
                    ? $model['pricing']['input'] * 1000000 
                    : 0.0,
                'output_cost_per_m' => isset($model['pricing']) && isset($model['pricing']['output']) 
                    ? $model['pricing']['output'] * 1000000 
                    : 0.0,
                'description' => $model['description'] ?? null,
                'capabilities' => isset($model['capabilities']) ? $model['capabilities'] : [],
                'metadata' => $model,
            ];
        }, $models);
    }

    /**
     * Clear provider-related caches
     */
    protected function clearProviderCaches()
    {
        $this->cacheManager->invalidateAllProviders();
    }

    /**
     * Clear model-related caches for a provider
     */
    protected function clearModelCaches($providerId)
    {
        // Clear any model caches related to this provider
        // Implementation depends on caching strategy
    }

    /**
     * Get all active providers
     */
    public function getAllProviders()
    {
        return $this->cacheManager->cacheProvider(
            'providers:all',
            function () {
                return AIProvider::where('is_active', true)
                    ->withCount('models')
                    ->get();
            },
            $this->cacheTTL
        );
    }

    /**
     * Get models for a provider
     */
    public function getProviderModels($providerId)
    {
        return Cache::remember(
            "provider:{$providerId}:models",
            $this->cacheTTL,
            function () use ($providerId) {
                return AIModel::where('provider_id', $providerId)
                    ->orderBy('name')
                    ->get();
            }
        );
    }
}