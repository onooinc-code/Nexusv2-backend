<?php

namespace App\Services\AiModelsHub;

use App\Models\Agent;
use App\Models\AIModel;
use App\Models\AIProvider;
use Illuminate\Support\Facades\Log;

/**
 * UniversalAiGatewayService
 *
 * Single entry point for routing LLM requests to any AI Provider.
 * Defaults to Gemini, but can be configured to use others via IntentRouting or Agent settings.
 */
class UniversalAiGatewayService
{
    public function __construct(
        protected EncryptedApiKeyStorage $keyStorage
    ) {}

    /**
     * Resolve the appropriate AI model.
     * Priority: Agent settings -> Default 'Gemini' provider -> IntentRouting -> First active
     */
    protected function resolveModel(Agent $agent): ?AIModel
    {
        // 1. Agent-specific model override
        $settings = $agent->settings ?? [];
        if (!empty($settings['ai_model_id'])) {
            $model = AIModel::with('provider')->find($settings['ai_model_id']);
            if ($model) return $model;
        }

        // 2. Default Gemini Provider
        // Look for Gemini by name, or first model from Gemini provider
        $geminiProvider = AIProvider::where('name', 'like', '%Gemini%')
                                    ->orWhere('name', 'like', '%Google%')
                                    ->first();
        if ($geminiProvider) {
            $geminiModel = AIModel::with('provider')
                                  ->where('provider_id', $geminiProvider->id)
                                  ->where('status', 'active')
                                  ->first();
            if ($geminiModel) return $geminiModel;
        }

        // 3. Platform default via intent routing
        $intentRouting = \App\Models\IntentRouting::where('intent_name', 'agent_execution')
            ->with(['defaultModel.provider'])
            ->first();

        if ($intentRouting && $intentRouting->defaultModel) {
            return $intentRouting->defaultModel;
        }

        // 4. Last resort
        return AIModel::with('provider')
            ->whereHas('provider', fn($q) => $q->where('is_active', true))
            ->where('status', 'active')
            ->first();
    }

    /**
     * Execute an Agent's prompt against the resolved AI Model.
     */
    public function executeWithAgent(Agent $agent, array $context): array
    {
        $model = $this->resolveModel($agent);

        if (!$model || !$model->provider) {
            throw new \RuntimeException('No AI model available for execution. Please ensure Gemini or another provider is active.');
        }

        $provider = new DynamicRestProvider($model->provider->id, $this->keyStorage);

        $prompt = is_string($context['input']) ? $context['input'] : json_encode($context['input']);
        
        $temperature = $agent->settings['temperature'] ?? 0.7;
        $maxTokens = $agent->settings['max_tokens'] ?? 2048;

        $options = [
            'model'       => $model->external_id ?? $model->name,
            'temperature' => (float)$temperature,
            'max_tokens'  => (int)$maxTokens,
            'system'      => $context['system_prompt'] ?? '',
        ];

        // Format prompt as messages array if needed by specific providers
        // DynamicRestProvider handles wrapping in user content.

        $result = $provider->generateText($prompt, $options);

        if (!$result['success']) {
            throw new \RuntimeException('LLM call failed: ' . ($result['error'] ?? 'Unknown error'));
        }

        // Attach resolved model metadata for logging purposes
        $result['used_model'] = $model->name;
        $result['used_provider'] = $model->provider->name;

        return $result;
    }

    /**
     * Generate embeddings for the given text using the appropriate AI model.
     */
    public function generateEmbeddings(string $text, ?Agent $agent = null): array
    {
        // 1. Resolve model via agent or default
        $model = $agent ? $this->resolveModel($agent) : $this->resolveModel(new Agent());

        if (!$model || !$model->provider) {
            throw new \RuntimeException('No AI provider available for generating embeddings.');
        }

        $provider = new DynamicRestProvider($model->provider->id, $this->keyStorage);

        $options = [
            'model' => 'text-embedding-3-small', // Default for OpenAI compatibility
        ];

        $result = $provider->generateEmbeddings($text, $options);

        if (!$result['success']) {
            throw new \RuntimeException('Embedding generation failed: ' . ($result['error'] ?? 'Unknown error'));
        }

        return $result['vector'] ?? [];
    }
}
