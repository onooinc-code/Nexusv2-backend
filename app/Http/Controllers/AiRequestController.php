<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Services\AiModelsHub\IntentRoutingEngine;
use App\Services\AiModelsHub\DynamicProviderRegistry;
use App\Services\AiModelsHub\PayloadAdapterFactory;
use App\Services\AiModelsHub\EncryptedApiKeyStorage;
use App\Services\AiModelsHub\CircuitBreaker;
use App\Services\AiModelsHub\UsageTracker;
use App\Http\Middleware\SsrfProtectionMiddleware;
use App\Models\IntentRouting;
use App\Models\AIProvider;
use App\Models\AIModel;
use Illuminate\Support\Str;

class AiRequestController extends Controller
{
    protected $intentRoutingEngine;
    protected $providerRegistry;
    protected $payloadAdapterFactory;
    protected $encryptedKeyStorage;
    protected $circuitBreaker;
    protected $usageTracker;

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
     * Handle a unified AI request
     */
    public function handleRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'intent_name' => 'required|string|max:255',
            'prompt' => 'required|string',
            'parameters' => 'nullable|array',
            'context' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            // 1. Resolve intent to provider/model configuration
            $routing = $this->intentRoutingEngine->resolveIntent($request->intent_name);

            if (!$routing) {
                return response()->json([
                    'success' => false,
                    'message' => 'Intent routing not found'
                ], 404);
            }

            // 2. Get provider configuration
            $provider = $this->providerRegistry->getProvider($routing['default_provider_id']);

            if (!$provider) {
                return response()->json([
                    'success' => false,
                    'message' => 'Provider not found'
                ], 404);
            }

            // 3. Get decrypted API key
            $apiKey = $this->encryptedKeyStorage->getDecryptedKey($provider->id);

            if (!$apiKey) {
                return response()->json([
                    'success' => false,
                    'message' => 'API key not found or unable to decrypt'
                ], 404);
            }

            // 4. Adapt the request to provider-specific format
            $adaptedRequest = $this->payloadAdapterFactory->adaptPayload(
                $provider->payload_format,
                [
                    'prompt' => $request->prompt,
                    'parameters' => $request->parameters ?? [],
                    'context' => $request->context ?? [],
                    'model_id' => $routing['default_model_id']
                ]
            );

            // 5. Execute request with circuit breaker protection
            $result = $this->circuitBreaker->executeWithFallback(
                function () use ($provider, $adaptedRequest, $apiKey) {
                    // Make the actual HTTP request to the provider
                    $headers = [
                        'Content-Type' => 'application/json',
                    ];

                    // Handle different auth formats
                    if ($provider->auth_header_format === 'Bearer {key}') {
                        $headers['Authorization'] = 'Bearer ' . $apiKey;
                    } elseif ($provider->auth_header_format === 'Key {key}') {
                        $headers['Authorization'] = 'Key ' . $apiKey;
                    } else {
                        // Custom header format
                        $headerName = str_replace('{key}', '', $provider->auth_header_format);
                        $headers[trim($headerName)] = $apiKey;
                    }

                    // Apply SSRF protection to the provider's base URL
                    if (!SsrfProtectionMiddleware::validateUrl($provider->base_url)) {
                        throw new \Exception("SSRF protection blocked provider URL: {$provider->base_url}");
                    }

                    $response = Http::withHeaders($headers)
                        ->timeout(30)
                        ->post(
                            $provider->base_url . '/' . ltrim($provider->generate_endpoint, '/'),
                            $adaptedRequest
                        );

                    if (!$response->successful()) {
                        throw new \Exception("Provider request failed with status: {$response->status()}");
                    }

                    return $response->json();
                },
                // Fallback providers would be resolved here in a full implementation
                [] // For now, we'll handle fallbacks in the circuit breaker
            );

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => 'AI request failed after attempting all fallback options'
                ], 500);
            }

            // 6. Adapt the response back to generic format
            $adaptedResponse = $this->payloadAdapterFactory->adaptResponse(
                $provider->payload_format,
                $result
            );

            // 7. Track usage and costs
            $this->usageTracker->trackUsage(
                $provider->id,
                $routing['default_model_id'],
                $adaptedResponse['usage']['input_tokens'] ?? 0,
                $adaptedResponse['usage']['output_tokens'] ?? 0
            );

            return response()->json([
                'success' => true,
                'data' => $adaptedResponse,
                'message' => 'AI request processed successfully'
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error handling AI request: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to process AI request'
            ], 500);
        }
    }

    /**
     * Get the intent routing matrix with all intents, providers, and current routes
     */
    public function getRoutingMatrix(Request $request)
    {
        try {
            // Get all intents from the routing table (unique intent names)
            $dbIntents = IntentRouting::distinct('intent_name')->pluck('intent_name')->toArray();
            
            // Define core system intents
            $coreIntents = [
                'general_chat', 
                'data_extraction', 
                'summarization', 
                'embedding', 
                'fast_response', 
                'reasoning',
                'contact_extraction',
                'intent_classification'
            ];
            
            $allIntentNames = array_unique(array_merge($coreIntents, $dbIntents));
            
            $intents = collect($allIntentNames)
                ->map(function ($name) {
                    return [
                        'key' => $name,
                        'id' => Str::slug($name),
                        'intent' => $name,
                        'name' => $name,
                    ];
                })
                ->values()
                ->toArray();

            // Get all providers with their models
            $providers = AIProvider::with('models')
                ->where('is_active', true)
                ->get()
                ->map(function ($provider) {
                    return [
                        'key' => $provider->id,
                        'id' => $provider->id,
                        'provider' => $provider->name,
                        'name' => $provider->name,
                        'models' => $provider->models->map(fn ($model) => [
                            'id' => $model->id,
                            'name' => $model->name,
                            'key' => $model->id,
                            'label' => $model->name,
                        ])->toArray(),
                    ];
                })
                ->toArray();

            // Get all current routing configurations
            $routes = IntentRouting::all()
                ->map(function ($routing) {
                    return [
                        'intent' => $routing->intent_name,
                        'intent_key' => $routing->intent_name,
                        'provider' => $routing->default_provider_id,
                        'provider_key' => $routing->default_provider_id,
                        'model' => $routing->default_model_id,
                        'model_id' => $routing->default_model_id,
                        'fallbackProvider' => $routing->fallback_provider_id,
                        'fallbackModel' => $routing->fallback_model_id,
                    ];
                })
                ->toArray();

            return response()->json([
                'success' => true,
                'data' => [
                    'intents' => $intents,
                    'providers' => $providers,
                    'profiles' => $providers, // Alias for compatibility
                    'routes' => $routes,
                    'routing' => $routes, // Alias for compatibility
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Error retrieving routing matrix: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve intent routing matrix',
            ], 500);
        }
    }

    /**
     * Update intent routing configuration
     */
    public function routeIntent(Request $request)
    {
        // Handle both old and new validation schema
        $validator = Validator::make($request->all(), [
            'intent' => 'nullable|string|max:255',
            'intent_name' => 'nullable|string|max:255',
            'provider' => 'nullable|uuid',
            'default_provider_id' => 'nullable|uuid',
            'model' => 'nullable|uuid',
            'default_model_id' => 'nullable|uuid',
            'fallback_provider_id' => 'nullable|uuid',
            'fallback_model_id' => 'nullable|uuid',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            // Map field names from component to database
            $intentName = $request->input('intent') ?? $request->input('intent_name');
            $providerId = $request->input('provider') ?? $request->input('default_provider_id');
            $modelId = $request->input('model') ?? $request->input('default_model_id');
            $fallbackProviderId = $request->input('fallback_provider_id');
            $fallbackModelId = $request->input('fallback_model_id');

            if (!$intentName) {
                return response()->json([
                    'success' => false,
                    'message' => 'Intent name is required',
                ], 422);
            }

            // Find or create the routing configuration
            $routing = IntentRouting::firstOrNew(['intent_name' => $intentName]);

            if ($providerId) {
                // Verify provider exists
                $provider = AIProvider::find($providerId);
                if (!$provider) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Provider not found',
                    ], 404);
                }
                $routing->default_provider_id = $providerId;
            }

            if ($modelId) {
                // Verify model exists
                $model = AIModel::find($modelId);
                if (!$model) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Model not found',
                    ], 404);
                }
                $routing->default_model_id = $modelId;
            }

            if ($fallbackProviderId) {
                $routing->fallback_provider_id = $fallbackProviderId;
            }

            if ($fallbackModelId) {
                $routing->fallback_model_id = $fallbackModelId;
            }

            // Ensure ID exists for new records
            if (!$routing->exists) {
                $routing->id = Str::uuid();
            }

            $routing->save();

            return response()->json([
                'success' => true,
                'message' => 'Intent routing updated successfully',
                'data' => [
                    'intent' => $routing->intent_name,
                    'provider' => $routing->default_provider_id,
                    'model' => $routing->default_model_id,
                ],
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error updating intent routing: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update intent routing',
            ], 500);
        }
    }
}
