<?php

namespace App\Http\Controllers;

use App\Models\AIModel;
use App\Models\ApiKey;
use App\Jobs\ExecuteAiModelJob;
use App\Services\LogService;
use App\Services\AI\ProviderInterface;
use App\Services\AI\ModelSelector;
use App\Services\AI\FallbackChainService;
use App\Services\AI\CostOptimizer;
use App\Services\AI\QualityRouter;
use App\Services\AI\SpeedRouter;
use App\Services\AI\ApiKeyPool;
use App\Services\AI\ApiKeyRotationService;
use App\Services\AI\RateLimitService;
use App\Services\AI\ApiKeyHealthService;
use App\Services\AiModelsHub\DynamicRestProvider;
use App\Services\AiModelsHub\EncryptedApiKeyStorage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AiModelController extends Controller
{
    public function __construct(
        protected LogService $logService,
        protected ModelSelector $modelSelector,
        protected FallbackChainService $fallbackChain,
        protected CostOptimizer $costOptimizer,
        protected QualityRouter $qualityRouter,
        protected SpeedRouter $speedRouter,
        protected ApiKeyPool $keyPool,
        protected ApiKeyRotationService $keyRotation,
        protected RateLimitService $rateLimiter,
        protected ApiKeyHealthService $keyHealth,
    ) {}

    public function index(Request $request)
    {
        $query = AIModel::query();

        if ($request->has('provider')) {
            $query->where('provider', $request->provider);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $models = $query->orderBy('created_at', 'desc')->paginate($request->per_page ?? 20);

        return response()->json($models);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'provider' => 'required|string',
            'external_id' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'capabilities' => 'nullable|array',
            'metadata' => 'nullable|array',
            'status' => 'nullable|string|in:active,inactive,deprecated',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $model = AIModel::create($validator->validated());

        $this->logService->info('AI model created', [
            'channel' => 'ai',
            'type' => 'create',
            'related_id' => $model->id,
            'related_type' => 'App\Models\AIModel',
            'user_id' => $request->user()?->id,
            'context' => ['name' => $model->name, 'provider' => $model->provider],
        ]);

        return response()->json([
            'message' => 'AI model created successfully',
            'data' => $model,
        ], 201);
    }

    public function show($id)
    {
        $model = AIModel::with(['provider'])->findOrFail($id);
        return response()->json($model);
    }

    public function update(Request $request, $id)
    {
        $model = AIModel::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'capabilities' => 'nullable|array',
            'metadata' => 'nullable|array',
            'status' => 'nullable|string|in:active,inactive,deprecated',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $model->update($validator->validated());

        $this->logService->info('AI model updated', [
            'channel' => 'ai',
            'type' => 'update',
            'related_id' => $model->id,
            'related_type' => 'App\Models\AIModel',
            'user_id' => $request->user()?->id,
            'context' => ['changes' => $validator->validated()],
        ]);

        return response()->json([
            'message' => 'AI model updated successfully',
            'data' => $model,
        ]);
    }

    public function destroy($id)
    {
        $model = AIModel::findOrFail($id);
        $modelId = $model->id;
        $modelName = $model->name;
        $model->delete();

        $this->logService->info('AI model deleted', [
            'channel' => 'ai',
            'type' => 'delete',
            'related_id' => $modelId,
            'related_type' => 'App\Models\AIModel',
            'user_id' => request()->user()?->id,
            'context' => ['name' => $modelName],
        ]);

        return response()->json(['message' => 'AI model deleted successfully']);
    }

    public function test(Request $request, $id)
    {
        $model = AIModel::findOrFail($id);
        $provider = $model->provider;

        $this->logService->info('AI model test started', [
            'channel' => 'ai',
            'type' => 'test',
            'related_id' => $model->id,
            'related_type' => 'App\Models\AIModel',
            'user_id' => $request->user()?->id,
            'context' => ['model' => $model->name, 'provider' => $provider],
        ]);

        $apiKey = ApiKey::where('provider', $provider)
            ->where('type', 'ai_provider')
            ->where('is_active', true)
            ->first()?->key;

        if (!$apiKey) {
            $this->logService->warning('AI model test failed - no API key', [
                'channel' => 'ai',
                'type' => 'test',
                'related_id' => $model->id,
                'related_type' => 'App\Models\AIModel',
                'context' => ['provider' => $provider],
            ]);

            return response()->json([
                'success' => false,
                'error' => "No active API key found for provider: {$provider}",
            ], 400);
        }

        $providerInstance = $this->resolveProvider($provider, $apiKey);
        $externalId = $model->external_id ?? $model->name;

        $testRequest = [
            'model' => $externalId,
            'prompt' => $request->prompt ?? 'Hello, please respond with a single word.',
            'options' => [
                'max_tokens' => 50,
                'temperature' => 0.7,
            ],
        ];

        $startTime = microtime(true);
        $result = $providerInstance->execute($testRequest);
        $durationMs = round((microtime(true) - $startTime) * 1000, 2);

        $result['test_duration_ms'] = $durationMs;
        $result['model_id'] = $id;
        $result['model_name'] = $model->name;

        $this->logService->info('AI model test completed', [
            'channel' => 'ai',
            'type' => 'test',
            'related_id' => $model->id,
            'related_type' => 'App\Models\AIModel',
            'user_id' => $request->user()?->id,
            'context' => [
                'model' => $model->name,
                'provider' => $provider,
                'success' => $result['success'] ?? false,
                'duration_ms' => $durationMs,
            ],
        ]);

        return response()->json($result);
    }



    protected function resolveProvider(string $providerId, string $apiKey)
    {
        // For dynamic routing we use DynamicRestProvider
        // We will need to pass EncryptedApiKeyStorage but for this legacy method signature we can mock it
        // Or simply instantiate it
        return new \App\Services\AiModelsHub\DynamicRestProvider($providerId, app(\App\Services\AiModelsHub\EncryptedApiKeyStorage::class));
    }
}
