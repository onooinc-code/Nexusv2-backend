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
use App\Services\AiModelsHub\SemanticCache;
use App\Services\AiModelsHub\ProviderHealthMonitor;
use App\Models\AiAuditTrail;
use App\Http\Middleware\SsrfProtectionMiddleware;

class AiRouteController extends Controller
{
    protected $intentRoutingEngine;
    protected $providerRegistry;
    protected $payloadAdapterFactory;
    protected $encryptedKeyStorage;
    protected $circuitBreaker;
    protected $usageTracker;
    protected $semanticCache;
    protected $healthMonitor;

    public function __construct(
        IntentRoutingEngine $intentRoutingEngine,
        DynamicProviderRegistry $providerRegistry,
        PayloadAdapterFactory $payloadAdapterFactory,
        EncryptedApiKeyStorage $encryptedKeyStorage,
        CircuitBreaker $circuitBreaker,
        UsageTracker $usageTracker,
        SemanticCache $semanticCache,
        ProviderHealthMonitor $healthMonitor
    ) {
        $this->intentRoutingEngine = $intentRoutingEngine;
        $this->providerRegistry = $providerRegistry;
        $this->payloadAdapterFactory = $payloadAdapterFactory;
        $this->encryptedKeyStorage = $encryptedKeyStorage;
        $this->circuitBreaker = $circuitBreaker;
        $this->usageTracker = $usageTracker;
        $this->semanticCache = $semanticCache;
        $this->healthMonitor = $healthMonitor;
    }

    /**
     * Core routing endpoint with full parameter handling, semantic caching, and audit trail
     */
    public function route(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'intent' => 'required|string|max:255',
            'prompt' => 'required|string',
            'cost_profile' => 'nullable|string|in:low,medium,high',
            'latency_profile' => 'nullable|string|in:fast,balanced,safe',
            'security_class' => 'nullable|string|in:standard,sensitive,restricted',
            'language' => 'nullable|string',
            'parameters' => 'nullable|array',
            'context' => 'nullable|array',
            'bypass_cache' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $startTime = microtime(true);

        try {
            $profiles = $request->only(['cost_profile', 'latency_profile', 'security_class', 'language']);

            // 1. Check semantic cache first (unless bypass requested)
            if (!$request->boolean('bypass_cache')) {
                $cached = $this->semanticCache->get(
                    $request->intent,
                    $request->prompt,
                    $request->parameters ?? []
                );

                if ($cached) {
                    $latency = round((microtime(true) - $startTime) * 1000);
                    $this->recordAudit('route_executed', $cached['provider_id'] ?? null, $cached['model_id'] ?? null, $request->intent, 'success', $latency, false, null, 0, 0, null, null, $request, ['cache_hit' => true, 'profiles' => $profiles]);

                    return response()->json([
                        'success' => true,
                        'data' => $cached['data'],
                        'meta' => [
                            'provider_used' => $cached['provider_id'] ?? null,
                            'model_used' => $cached['model_id'] ?? null,
                            'fallback_triggered' => false,
                            'cache_hit' => true,
                        ],
                        'message' => 'AI request served from cache'
                    ], 200);
                }
            }

            // 2. Resolve intent with profiles
            $routing = $this->intentRoutingEngine->resolveIntentWithProfiles($request->intent, $profiles);

            if (!$routing) {
                $this->recordAudit('route_executed', null, null, $request->intent, 'failed', 0, false, null, 0, 0, 'routing_not_found', 'No suitable routing configuration', $request, ['profiles' => $profiles]);

                return response()->json([
                    'success' => false,
                    'message' => 'Suitable routing configuration not found for intent and profiles'
                ], 404);
            }

            // 3. Extract primary and fallbacks
            $primaryProvider = $routing['primary']['provider'];
            $primaryModel = $routing['primary']['model'];
            $fallbacks = $routing['fallbacks'] ?? [];

            // 4. Execute with circuit breaker and fallbacks
            $fallbackClosures = [];
            foreach ($fallbacks as $fallback) {
                $fProvider = $fallback['provider'];
                $fModel = $fallback['model'];
                $fallbackClosures[] = function () use ($fProvider, $fModel, $request) {
                    return $this->executeProviderRequest($fProvider, $fModel, $request);
                };
            }

            $result = $this->circuitBreaker->executeWithFallback(
                function () use ($primaryProvider, $primaryModel, $request) {
                    return $this->executeProviderRequest($primaryProvider, $primaryModel, $request);
                },
                $fallbackClosures
            );

            $latency = round((microtime(true) - $startTime) * 1000);

            if (!$result['success']) {
                $this->recordAudit('route_executed', $primaryProvider->id ?? null, $primaryModel->id ?? null, $request->intent, 'failed', $latency, true, count($fallbacks), 0, 0, 'all_providers_failed', $result['message'] ?? 'All providers failed', $request, ['profiles' => $profiles, 'errors' => $result['errors'] ?? []]);

                return response()->json([
                    'success' => false,
                    'message' => 'AI request failed after attempting all fallback options',
                    'errors' => $result['errors'] ?? []
                ], 500);
            }

            // 5. Track usage
            $inputTokens = $result['usage']['input_tokens'] ?? 0;
            $outputTokens = $result['usage']['output_tokens'] ?? 0;

            $this->usageTracker->trackUsage(
                $result['provider_id'],
                $result['model_id'],
                $inputTokens,
                $outputTokens,
                $request->user() ? $request->user()->workspace_id : null
            );

            // 6. Store in semantic cache
            $this->semanticCache->put(
                $request->intent,
                $request->prompt,
                $request->parameters ?? [],
                $result
            );

            // 7. Record audit trail
            $this->recordAudit('route_executed', $result['provider_id'], $result['model_id'], $request->intent, 'success', $latency, $result['fallback_triggered'] ?? false, null, $inputTokens, $outputTokens, null, null, $request, ['profiles' => $profiles, 'cache_hit' => false]);

            return response()->json([
                'success' => true,
                'data' => $result['data'],
                'meta' => [
                    'provider_used' => $result['provider_id'],
                    'model_used' => $result['model_id'],
                    'fallback_triggered' => $result['fallback_triggered'] ?? false,
                    'cache_hit' => false,
                    'latency_ms' => $latency,
                ],
                'message' => 'AI request processed successfully'
            ], 200);

        } catch (\Exception $e) {
            $latency = round((microtime(true) - $startTime) * 1000);
            Log::error('Error handling AI request in AiRouteController: ' . $e->getMessage());

            $this->recordAudit('route_executed', null, null, $request->intent ?? null, 'failed', $latency, false, null, 0, 0, get_class($e), $e->getMessage(), $request, ['profiles' => $profiles ?? []]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to process AI request'
            ], 500);
        }
    }

    /**
     * Provider health scorecard API
     */
    public function providerHealth()
    {
        try {
            $scorecard = $this->healthMonitor->getScorecard();

            return response()->json([
                'success' => true,
                'data' => $scorecard
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching provider health: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch provider health'
            ], 500);
        }
    }

    /**
     * Audit trail listing
     */
    public function auditTrail(Request $request)
    {
        try {
            $query = AiAuditTrail::orderBy('created_at', 'desc');

            if ($request->has('event_type')) {
                $query->where('event_type', $request->event_type);
            }
            if ($request->has('provider_id')) {
                $query->where('provider_id', $request->provider_id);
            }
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            $trails = $query->limit($request->integer('limit', 100))->get();

            return response()->json([
                'success' => true,
                'data' => $trails
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching audit trail: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch audit trail'
            ], 500);
        }
    }

    /**
     * Telemetry and observability dashboard data
     */
    public function telemetry(Request $request)
    {
        try {
            $last24h = now()->subHours(24);
            
            $totalRequests = AiAuditTrail::where('created_at', '>=', $last24h)->count();
            $failedRequests = AiAuditTrail::where('created_at', '>=', $last24h)->where('status', 'failed')->count();
            $cacheHits = AiAuditTrail::where('created_at', '>=', $last24h)->whereJsonContains('metadata->cache_hit', true)->count();
            
            $avgLatency = AiAuditTrail::where('created_at', '>=', $last24h)->where('status', 'success')->avg('latency_ms') ?? 0;
            
            $providerUsage = AiAuditTrail::where('created_at', '>=', $last24h)
                ->whereNotNull('provider_id')
                ->select('provider_id', \DB::raw('count(*) as count'))
                ->groupBy('provider_id')
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'period' => '24h',
                    'total_requests' => $totalRequests,
                    'error_rate' => $totalRequests > 0 ? round(($failedRequests / $totalRequests) * 100, 2) : 0,
                    'cache_hit_rate' => $totalRequests > 0 ? round(($cacheHits / $totalRequests) * 100, 2) : 0,
                    'average_latency_ms' => round($avgLatency),
                    'provider_usage' => $providerUsage
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching telemetry: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch telemetry data'
            ], 500);
        }
    }

    /**
     * Executes the actual HTTP request to the AI Provider
     */
    public function executeProviderRequest($provider, $model, Request $request)
    {
        $apiKey = $this->encryptedKeyStorage->getDecryptedKey($provider->id);

        if (!$apiKey) {
            throw new \Exception("API key not found or unable to decrypt for provider: {$provider->name}");
        }

        $adaptedRequest = $this->payloadAdapterFactory->adaptPayload(
            $provider->payload_format,
            [
                'prompt' => $request->prompt,
                'parameters' => $request->parameters ?? [],
                'context' => $request->context ?? [],
                'model_id' => $model->external_id ?? $model->name
            ]
        );

        $headers = ['Content-Type' => 'application/json'];

        if ($provider->auth_header_format === 'Bearer {key}') {
            $headers['Authorization'] = 'Bearer ' . $apiKey;
        } elseif ($provider->auth_header_format === 'Key {key}') {
            $headers['Authorization'] = 'Key ' . $apiKey;
        } else {
            $headerName = str_replace('{key}', '', $provider->auth_header_format);
            $headers[trim($headerName)] = $apiKey;
        }

        if (!SsrfProtectionMiddleware::validateUrl($provider->base_url)) {
            throw new \Exception("SSRF protection blocked provider URL: {$provider->base_url}");
        }

        $response = Http::withHeaders($headers)
            ->timeout(30)
            ->post(
                $provider->base_url . '/' . ltrim($provider->generate_endpoint, '/'),
                $adaptedRequest
            );

        if ($response->status() === 429) {
            throw new \App\Exceptions\AiRateLimitException();
        }

        if (!$response->successful()) {
            throw new \App\Exceptions\AiProviderOfflineException("Provider request failed with status: {$response->status()}");
        }

        $adaptedResponse = $this->payloadAdapterFactory->adaptResponse(
            $provider->payload_format,
            $response->json()
        );

        return [
            'success' => true,
            'provider_id' => $provider->id,
            'model_id' => $model->id,
            'data' => $adaptedResponse,
            'usage' => $adaptedResponse['usage'] ?? []
        ];
    }

    /**
     * Record an event to the audit trail
     */
    protected function recordAudit(
        string $eventType,
        ?string $providerId,
        ?string $modelId,
        ?string $intent,
        string $status,
        int $latencyMs,
        bool $fallbackTriggered,
        ?int $fallbackSequence,
        int $inputTokens,
        int $outputTokens,
        ?string $errorType,
        ?string $errorMessage,
        Request $request,
        array $metadata = []
    ): void {
        try {
            AiAuditTrail::create([
                'event_type' => $eventType,
                'provider_id' => $providerId,
                'model_id' => $modelId,
                'intent' => $intent,
                'status' => $status,
                'latency_ms' => $latencyMs,
                'fallback_triggered' => $fallbackTriggered,
                'fallback_sequence' => $fallbackSequence,
                'input_tokens' => $inputTokens,
                'output_tokens' => $outputTokens,
                'error_type' => $errorType,
                'error_message' => $errorMessage,
                'workspace_id' => $request->user()?->workspace_id,
                'user_id' => $request->user()?->id,
                'metadata' => $metadata,
            ]);
        } catch (\Exception $e) {
            Log::warning("Failed to record audit trail: {$e->getMessage()}");
        }
    }
}
