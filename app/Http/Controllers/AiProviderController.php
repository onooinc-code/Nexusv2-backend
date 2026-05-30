<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use App\Services\AiModelsHub\DynamicProviderRegistry;
use App\Services\AiModelsHub\DynamicRestProvider;
use App\Services\AiModelsHub\EncryptedApiKeyStorage;

class AiProviderController extends Controller
{
    protected $providerRegistry;
    protected $keyStorage;

    public function __construct(DynamicProviderRegistry $providerRegistry, EncryptedApiKeyStorage $keyStorage)
    {
        $this->providerRegistry = $providerRegistry;
        $this->keyStorage = $keyStorage;
    }

    /**
     * List all AI providers
     */
    public function index()
    {
        try {
            $providers = \App\Models\AIProvider::with('models')->get();
            return response()->json([
                'success' => true,
                'data' => $providers
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error fetching AI providers: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch AI providers'
            ], 500);
        }
    }

    /**
     * Store a new AI provider
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'                  => 'required|string|max:255',
            'base_url'              => 'required|url',
            'models_fetch_endpoint' => 'nullable|string|max:255',
            'generate_endpoint'     => 'nullable|string|max:255',
            'test_endpoint'         => 'nullable|string|max:255',
            'auth_header_format'    => 'nullable|string|max:255',
            'payload_format'        => 'nullable|string|max:255',
            'is_active'             => 'nullable|boolean',
            'api_key'               => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $provider = $this->providerRegistry->registerProvider([
                'name'                  => $request->name,
                'base_url'              => $request->base_url,
                'models_fetch_endpoint' => $request->models_fetch_endpoint,
                'generate_endpoint'     => $request->generate_endpoint,
                'test_endpoint'         => $request->test_endpoint,
                'auth_header_format'    => $request->auth_header_format,
                'payload_format'        => $request->payload_format,
                'is_active'             => $request->is_active ?? true,
            ]);

            // Save the API key if provided
            if ($request->filled('api_key')) {
                $this->keyStorage->storeKey($provider->id, $request->api_key, "API Key for {$provider->name}");
            }

            // Sync models first time from the provider API (independent of api_key presence)
            if ($request->models_fetch_endpoint) {
                try {
                    $restProvider = new DynamicRestProvider($provider->id, $this->keyStorage);
                    $models = $restProvider->getAvailableModels();
                    if (!empty($models)) {
                        foreach ($models as $modelData) {
                            // Use updateOrCreate to prevent duplicate key errors on retry
                            \App\Models\AIModel::updateOrCreate(
                                [
                                    'name'        => $modelData['id'] ?? $modelData['name'],
                                    'provider_id' => $provider->id,
                                ],
                                [
                                    'id'            => (string) \Illuminate\Support\Str::uuid(),
                                    'last_synced_at'=> now(),
                                ]
                            );
                        }
                    }
                } catch (\Exception $e) {
                    Log::warning('Initial model sync failed for provider ' . $provider->id . ': ' . $e->getMessage());
                }
            }

            return response()->json([
                'success' => true,
                'data'    => $provider->load('models'),
                'message' => 'AI provider created successfully',
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error creating AI provider: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create AI provider',
            ], 500);
        }
    }

    /**
     * Display the specified AI provider
     */
    public function show($id)
    {
        try {
            $provider = \App\Models\AIProvider::with('models')->find($id);

            if (!$provider) {
                return response()->json([
                    'success' => false,
                    'message' => 'AI provider not found',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data'    => $provider,
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error fetching AI provider: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch AI provider',
            ], 500);
        }
    }

    /**
     * Update the specified AI provider
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name'                  => 'required|string|max:255',
            'base_url'              => 'required|url',
            'models_fetch_endpoint' => 'nullable|string|max:255',
            'generate_endpoint'     => 'nullable|string|max:255',
            'test_endpoint'         => 'nullable|string|max:255',
            'auth_header_format'    => 'nullable|string|max:255',
            'payload_format'        => 'nullable|string|max:255',
            'is_active'             => 'nullable|boolean',
            'api_key'               => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $provider = \App\Models\AIProvider::find($id);

            if (!$provider) {
                return response()->json([
                    'success' => false,
                    'message' => 'AI provider not found',
                ], 404);
            }

            $provider->update([
                'name'                  => $request->name,
                'base_url'              => $request->base_url,
                'models_fetch_endpoint' => $request->models_fetch_endpoint,
                'generate_endpoint'     => $request->generate_endpoint,
                'test_endpoint'         => $request->test_endpoint,
                'auth_header_format'    => $request->auth_header_format,
                'payload_format'        => $request->payload_format,
                'is_active'             => $request->is_active ?? $provider->is_active,
            ]);

            if ($request->filled('api_key')) {
                $this->keyStorage->updateKey($provider->id, $request->api_key, "API Key for {$provider->name}");
            }

            return response()->json([
                'success' => true,
                'data'    => $provider->load('models'),
                'message' => 'AI provider updated successfully',
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error updating AI provider: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update AI provider: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified AI provider
     */
    public function destroy($id)
    {
        try {
            $provider = \App\Models\AIProvider::find($id);

            if (!$provider) {
                return response()->json([
                    'success' => false,
                    'message' => 'AI provider not found',
                ], 404);
            }

            // Deactivate and delete associated keys
            $this->keyStorage->deactivateKey($id);
            \App\Models\AIApiKey::where('provider_id', $id)->delete();

            // Delete associated models
            \App\Models\AIModel::where('provider_id', $id)->delete();

            $provider->delete();

            return response()->json([
                'success' => true,
                'message' => 'AI provider deleted successfully',
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error deleting AI provider: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete AI provider: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Sync models for a specific provider
     */
    public function syncModels(Request $request, $id)
    {
        try {
            $provider = $this->providerRegistry->getProvider($id);

            if (!$provider) {
                return response()->json([
                    'success' => false,
                    'message' => 'AI provider not found',
                ], 404);
            }

            $restProvider = new DynamicRestProvider($id, $this->keyStorage);
            $models = $restProvider->getAvailableModels();

            if (empty($models)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to fetch models from provider API, or provider returned no models',
                ], 400);
            }

            foreach ($models as $modelData) {
                \App\Models\AIModel::updateOrCreate(
                    [
                        'name'        => $modelData['id'] ?? $modelData['name'],
                        'provider_id' => $id,
                    ],
                    [
                        'id'             => (string) \Illuminate\Support\Str::uuid(),
                        'last_synced_at' => now(),
                    ]
                );
            }

            // Update provider last_synced_at
            $provider->update(['last_synced_at' => now()]);

            // Reload from DB to return current full list
            $syncedModels = \App\Models\AIModel::where('provider_id', $id)->orderBy('name')->get();

            return response()->json([
                'success'      => true,
                'data'         => $syncedModels,
                'synced_count' => count($models), // count of models fetched from API
                'total_count'  => $syncedModels->count(), // total in DB
                'message'      => 'Models synchronized successfully',
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error syncing models for AI provider: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to sync models for AI provider: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Test connection to a specific provider
     */
    public function test(Request $request, $id)
    {
        try {
            $provider = $this->providerRegistry->getProvider($id);

            if (!$provider) {
                return response()->json([
                    'success' => false,
                    'message' => 'AI provider not found',
                ], 404);
            }

            $restProvider = new DynamicRestProvider($id, $this->keyStorage);
            $health = $restProvider->getHealthStatus();

            return response()->json([
                'success'   => $health['status'] === 'healthy',
                'message'   => $health['status'] === 'healthy' ? 'Connection to provider successful' : 'Connection failed',
                'status'    => $health['status'],
                'data'      => $health,
                'timestamp' => now()->toIso8601String(),
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error testing provider connection: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to test provider connection: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Toggle active/inactive status of a provider (PATCH /ai/providers/{id}/toggle-active)
     */
    public function toggleActive(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'is_active' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $provider = \App\Models\AIProvider::find($id);

            if (!$provider) {
                return response()->json([
                    'success' => false,
                    'message' => 'AI provider not found',
                ], 404);
            }

            $provider->update(['is_active' => $request->boolean('is_active')]);

            return response()->json([
                'success' => true,
                'data'    => $provider->load('models'),
                'message' => 'Provider status updated successfully',
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error toggling AI provider status: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update provider status: ' . $e->getMessage(),
            ], 500);
        }
    }
}

