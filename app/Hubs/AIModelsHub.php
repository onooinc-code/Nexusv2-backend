<?php

namespace App\Hubs;

use App\Services\AiModelsHub\IntentRoutingEngine;
use App\Services\AiModelsHub\DynamicProviderRegistry;
use App\Services\AiModelsHub\PayloadAdapterFactory;
use App\Services\AiModelsHub\EncryptedApiKeyStorage;
use App\Services\AiModelsHub\CircuitBreaker;
use App\Services\AiModelsHub\UsageTracker;
use App\Services\AiModelsHub\AiProviderInterface;
use Illuminate\Support\Facades\Log;

class AIModelsHub
{
    protected $intentRoutingEngine;
    protected $providerRegistry;
    protected $payloadAdapterFactory;
    protected $encryptedKeyStorage;
    protected $circuitBreaker;
    protected $usageTracker;
    protected $providers = [];

    public function __construct(
        IntentRoutingEngine $intentRoutingEngine,
        DynamicProviderRegistry $providerRegistry,
        PayloadAdapterFactory $payloadAdapterFactory,
        EncryptedApiKeyStorage $encryptedKeyStorage,
        CircuitBreaker $circuitBreaker,
        UsageTracker $usageTracker
    ) {
        $this->intentRoutingEngine = $intentRoutingEngine;
        $this->providerRegistry = $providerRegistry;
        $this->payloadAdapterFactory = $payloadAdapterFactory;
        $this->encryptedKeyStorage = $encryptedKeyStorage;
        $this->circuitBreaker = $circuitBreaker;
        $this->usageTracker = $usageTracker;
    }

    /**
     * Process an AI request based on intent
     */
    public function processIntentRequest(string $intentName, string $prompt, array $options = []): array
    {
        try {
            // Resolve intent to provider/model configuration
            $routing = $this->intentRoutingEngine->resolveIntent($intentName);
            
            if (!$routing) {
                return [
                    'success' => false,
                    'error' => "Intent routing not found: {$intentName}",
                ];
            }

            // Get provider configuration
            $provider = $this->providerRegistry->getProvider($routing['default_provider_id']);
            
            if (!$provider) {
                return [
                    'success' => false,
                    'error' => "Provider not found for intent: {$intentName}",
                ];
            }

            // Get decrypted API key
            $apiKey = $this->encryptedKeyStorage->getDecryptedKey($provider->id);
            
            if (!$apiKey) {
                return [
                    'success' => false,
                    'error' => "API key not found or unable to decrypt for provider: {$provider->name}",
                ];
            }

            // Get or create provider service instance
            $providerService = $this->getProviderService($provider->id, $provider->name, $apiKey);
            
            if (!$providerService) {
                return [
                    'success' => false,
                    'error' => "Unable to initialize provider service: {$provider->name}",
                ];
            }

            // Prepare request for provider
            $request = [
                'prompt' => $prompt,
                'model' => $options['model'] ?? $routing['default_model_id'],
                'temperature' => $options['temperature'] ?? 0.7,
                'max_tokens' => $options['max_tokens'] ?? null,
                'context' => $options['context'] ?? [],
            ];

            // Execute request with circuit breaker protection
            $result = $this->circuitBreaker->executeWithFallback(
                function () use ($providerService, $request) {
                    return $providerService->generateText($request['prompt'], [
                        'model' => $request['model'],
                        'temperature' => $request['temperature'],
                        'max_tokens' => $request['max_tokens'],
                    ]);
                },
                // Fallback providers would be resolved here in a full implementation
                [] // For now, we'll handle fallbacks in the circuit breaker
            );

            if (!$result['success']) {
                return $result;
            }

            // Track usage and costs
            $this->usageTracker->trackUsage(
                $provider->id,
                $request['model'],
                $result['usage']['input_tokens'] ?? 0,
                $result['usage']['output_tokens'] ?? 0
            );

            return [
                'success' => true,
                'provider' => $result['provider'],
                'model' => $result['model'],
                'content' => $result['content'],
                'usage' => $result['usage'],
            ];
        } catch (\Exception $e) {
            Log::error('Error processing intent request: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Failed to process AI request',
            ];
        }
    }

    protected function getProviderService(string $providerId, string $providerName, string $apiKey): ?AiProviderInterface
    {
        // Check if we already have an instance
        if (isset($this->providers[$providerId])) {
            return $this->providers[$providerId];
        }

        // Dynamically instantiate the universal REST provider
        $this->providers[$providerId] = new \App\Services\AiModelsHub\DynamicRestProvider(
            $providerId, 
            $this->encryptedKeyStorage
        );

        return $this->providers[$providerId];
    }

    /**
     * Get available providers
     */
    public function getAvailableProviders(): array
    {
        return $this->providerRegistry->getAllProviders();
    }

    /**
     * Get available models for a provider
     */
    public function getProviderModels(string $providerId): array
    {
        $provider = $this->providerRegistry->getProvider($providerId);
        if (!$provider) {
            return [];
        }

        $providerService = $this->getProviderService($provider->id, $provider->name, '');
        if (!$providerService) {
            return [];
        }

        return $providerService->getAvailableModels();
    }

    /**
     * Get default model for a provider
     */
    public function getDefaultModel(string $providerId): ?string
    {
        $provider = $this->providerRegistry->getProvider($providerId);
        if (!$provider) {
            return null;
        }

        $providerService = $this->getProviderService($provider->id, $provider->name, '');
        if (!$providerService) {
            return null;
        }

        return $providerService->getDefaultModel();
    }
}