<?php

namespace App\Services\AiModelsHub;

use Illuminate\Support\Facades\Log;
use App\Models\IntentRouting;
use App\Models\AIProvider;
use App\Models\AIModel;
use App\Services\AiModelsHub\CacheManager;

class IntentRoutingEngine
{
    protected $cacheManager;
    protected $cacheTTL = 1800; // 30 minutes

    public function __construct(CacheManager $cacheManager)
    {
        $this->cacheManager = $cacheManager;
    }

    /**
     * Resolve intent to provider/model configuration
     */
    public function resolveIntent($intentName)
    {
        return $this->cacheManager->cacheIntentRouting(
            "intent:{$intentName}",
            function () use ($intentName) {
                return IntentRouting::with(['defaultProvider', 'defaultModel', 'fallbackProvider', 'fallbackModel'])
                    ->where('intent_name', $intentName)
                    ->first();
            },
            $this->cacheTTL
        );
    }

    /**
     * Get default model for an intent
     */
    public function getDefaultModel($intentName)
    {
        $routing = $this->resolveIntent($intentName);
        return $routing ? $routing->defaultModel : null;
    }

    /**
     * Get fallback options for an intent
     */
    public function getFallbackOptions($intentName)
    {
        $routing = $this->resolveIntent($intentName);
        if (!$routing) {
            return [];
        }

        $fallbacks = [];
        if ($routing->fallback_provider_id && $routing->fallback_model_id) {
            $fallbacks[] = [
                'provider' => $routing->fallbackProvider,
                'model' => $routing->fallbackModel
            ];
        }

        return $fallbacks;
    }

    /**
     * Create or update intent routing
     */
    public function upsertIntentRouting(array $data)
    {
        $intentRouting = IntentRouting::updateOrCreate(
            ['intent_name' => $data['intent_name']],
            [
                'id' => $data['id'] ?? \Illuminate\Support\Str::uuid(),
                'default_provider_id' => $data['default_provider_id'],
                'default_model_id' => $data['default_model_id'],
                'fallback_provider_id' => $data['fallback_provider_id'] ?? null,
                'fallback_model_id' => $data['fallback_model_id'] ?? null,
            ]
        );

        // Clear intent cache
        $this->clearIntentCache($data['intent_name']);

        return $intentRouting;
    }

    /**
     * Delete intent routing
     */
    public function deleteIntentRouting($intentName)
    {
        $intentRouting = IntentRouting::where('intent_name', $intentName)->first();
        if ($intentRouting) {
            $intentRouting->delete();
            $this->clearIntentCache($intentName);
            return true;
        }
        return false;
    }

    /**
     * Get all intent routings
     */
    public function getAllIntentRouting()
    {
        return $this->cacheManager->cacheProvider(
            'intents:all',
            function () {
                return IntentRouting::with(['defaultProvider', 'defaultModel', 'fallbackProvider', 'fallbackModel'])
                    ->get();
            },
            $this->cacheTTL
        );
    }

    /**
     * Clear intent cache
     */
    protected function clearIntentCache($intentName)
    {
        $this->cacheManager->invalidateIntentRouting($intentName);
        $this->cacheManager->invalidateAllIntentRouting();
    }

    /**
     * Resolve intent to provider/model configuration considering profiles
     */
    public function resolveIntentWithProfiles($intentName, array $profiles)
    {
        // Get the base intent routing
        $baseRouting = $this->resolveIntent($intentName);
        
        if (!$baseRouting) {
            return null;
        }

        // If no profiles specified, use the defaults
        if (empty(array_filter($profiles))) {
            return [
                'primary' => [
                    'provider' => $baseRouting->defaultProvider,
                    'model' => $baseRouting->defaultModel
                ],
                'fallbacks' => $this->getFallbackOptions($intentName)
            ];
        }

        // Build a query for dynamic models matching the profiles
        $query = AIModel::with('provider')->whereHas('provider', function($q) {
            $q->where('is_active', true);
        })->where('status', 'active');
        
        if (!empty($profiles['cost_profile'])) {
            $query->where('cost_profile', $profiles['cost_profile']);
        }
        
        if (!empty($profiles['latency_profile'])) {
            $query->where('latency_profile', $profiles['latency_profile']);
        }
        
        if (!empty($profiles['security_class'])) {
            $query->where('security_class', $profiles['security_class']);
        }
        
        if (!empty($profiles['language'])) {
            $lang = $profiles['language'];
            $query->whereJsonContains('language_support', $lang);
        }
        
        // Find matching models
        $matchingModels = $query->get();
        
        if ($matchingModels->isEmpty()) {
            // Fall back to default if no profiles match
            Log::warning("No models matched the requested profiles for intent {$intentName}. Falling back to default.");
            return [
                'primary' => [
                    'provider' => $baseRouting->defaultProvider,
                    'model' => $baseRouting->defaultModel
                ],
                'fallbacks' => $this->getFallbackOptions($intentName)
            ];
        }
        
        // Select the best match as primary
        $primaryModel = $matchingModels->first();
        
        // Use others as fallbacks
        $fallbacks = [];
        foreach ($matchingModels->skip(1) as $model) {
            $fallbacks[] = [
                'provider' => $model->provider,
                'model' => $model
            ];
        }
        
        // Also append original fallbacks just in case
        $fallbacks = array_merge($fallbacks, $this->getFallbackOptions($intentName));
        
        return [
            'primary' => [
                'provider' => $primaryModel->provider,
                'model' => $primaryModel
            ],
            'fallbacks' => $fallbacks
        ];
    }
}