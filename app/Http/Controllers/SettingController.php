<?php

namespace App\Http\Controllers;

use App\Events\GlobalAgentPauseToggled;
use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Policies\SettingPolicy;
use App\Services\CredentialEncryptionService;
use App\Services\CredentialValidationService;
use App\Services\LogService;
use App\Services\SeedRunnerService;
use App\Services\SettingCacheService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;

/**
 * SettingController
 *
 * Handles CRUD operations for application settings.
 * Supports grouped retrieval, type filtering, authorization, and emergency controls.
 */
class SettingController extends Controller
{
    /**
     * The cache service instance.
     *
     * @var SettingCacheService
     */
    protected SettingCacheService $cacheService;

    /**
     * The log service instance.
     *
     * @var LogService
     */
    protected LogService $logService;

    /**
     * The credential encryption service instance.
     *
     * @var CredentialEncryptionService
     */
    protected CredentialEncryptionService $encryptionService;

    /**
     * The credential validation service instance.
     *
     * @var CredentialValidationService
     */
    protected CredentialValidationService $validationService;

    /**
     * The seed runner service instance.
     *
     * @var SeedRunnerService
     */
    protected SeedRunnerService $seedRunnerService;

    /**
     * Create a new controller instance.
     *
     * @param SettingCacheService $cacheService
     * @param LogService $logService
     * @param CredentialEncryptionService $encryptionService
     * @param SeedRunnerService $seedRunnerService
     * @return void
     */
    public function __construct(
        SettingCacheService $cacheService,
        LogService $logService,
        CredentialEncryptionService $encryptionService,
        CredentialValidationService $validationService,
        SeedRunnerService $seedRunnerService,
    ) {
        $this->cacheService = $cacheService;
        $this->logService = $logService;
        $this->encryptionService = $encryptionService;
        $this->validationService = $validationService;
        $this->seedRunnerService = $seedRunnerService;

        // Apply authorization middleware to sensitive methods
        $this->middleware('auth:sanctum');
    }

    /**
     * Display a listing of settings.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        // Authorize user
        $this->authorize('create', Setting::class);

        $query = Setting::query();

        if ($request->has('group')) {
            $query->byGroup($request->input('group'));
        }

        if ($request->has('type')) {
            $query->byType($request->input('type'));
        }

        if ($request->has('scope')) {
            $query->where('scope', $request->input('scope'));
        }

        if ($request->has('workspace_id')) {
            $query->byWorkspace((int) $request->input('workspace_id'));
        }

        if ($request->has('user_id')) {
            $query->byUser((int) $request->input('user_id'));
        }

        if ($request->has('is_public')) {
            $isPublic = filter_var($request->input('is_public'), FILTER_VALIDATE_BOOLEAN);
            if ($isPublic) {
                $query->public();
            } else {
                $query->private();
            }
        }

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where('key', 'like', "%{$search}%");
        }

        $settings = $query->orderBy('group')->orderBy('key')->get();

        return response()->json([
            'success' => true,
            'data' => $settings,
            'count' => $settings->count(),
        ]);
    }

    /**
     * Store a newly created setting.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        // Authorize user
        $this->authorize('create', Setting::class);

        $validator = Validator::make($request->all(), [
            'key' => ['required', 'string', 'max:255', 'unique:settings,key'],
            'value' => ['required'],
            'type' => ['required', 'string', 'in:string,integer,boolean,json,text'],
            'group' => ['required', 'string', 'max:255'],
            'scope' => ['sometimes', 'string', 'in:global,workspace,user'],
            'workspace_id' => ['nullable', 'integer', 'exists:workspaces,id'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'is_public' => ['boolean'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        $data['scope'] = $data['scope'] ?? Setting::SCOPE_GLOBAL;

        if ($data['scope'] === Setting::SCOPE_WORKSPACE) {
            $data['workspace_id'] = $data['workspace_id'] ?? $request->input('workspace_id');
        }

        if ($data['scope'] === Setting::SCOPE_USER) {
            $data['user_id'] = $data['user_id'] ?? $request->input('user_id');
        }

        if ($data['type'] === Setting::TYPE_JSON && is_array($data['value'])) {
            $data['value'] = json_encode($data['value']);
        }

        // Encrypt if needed
        if ($this->encryptionService->shouldEncrypt($data['key'])) {
            $data['value'] = $this->encryptionService->encrypt((string) $data['value']);
            $data['is_encrypted'] = true;
        }

        $setting = Setting::create($data);
        $this->cacheService->forget($setting->key);

        $this->logService->info('Setting created', [
            'channel' => 'system',
            'type' => 'setting',
            'related_id' => $setting->id,
            'related_type' => 'App\Models\Setting',
            'user_id' => $request->user()?->id,
            'context' => ['key' => $setting->key, 'group' => $setting->group],
        ]);

        return response()->json([
            'success' => true,
            'data' => $setting,
            'message' => 'Setting created successfully.',
        ], 201);
    }

    /**
     * Display the specified setting.
     *
     * @param string $key
     * @return JsonResponse
     */
    public function show(string $key): JsonResponse
    {
        $setting = Setting::where('key', $key)->firstOrFail();

        // Authorize user
        $this->authorize('view', $setting);

        // Decrypt if needed
        if ($setting->is_encrypted) {
            $setting->value = $this->encryptionService->decrypt($setting->value);
        }

        return response()->json([
            'success' => true,
            'data' => $setting,
        ]);
    }

    /**
     * Update the specified setting.
     *
     * @param Request $request
     * @param string $key
     * @return JsonResponse
     */
    public function update(Request $request, string $key): JsonResponse
    {
        $setting = Setting::where('key', $key)->firstOrFail();

        // Authorize user
        $this->authorize('update', $setting);

        $oldValue = $setting->value;

        $validator = Validator::make($request->all(), [
            'value' => ['required'],
            'type' => ['sometimes', 'string', 'in:string,integer,boolean,json,text'],
            'group' => ['sometimes', 'string', 'max:255'],
            'scope' => ['sometimes', 'string', 'in:global,workspace,user'],
            'workspace_id' => ['nullable', 'integer', 'exists:workspaces,id'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'is_public' => ['boolean'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        if (array_key_exists('scope', $data)) {
            $data['scope'] = $data['scope'] ?? Setting::SCOPE_GLOBAL;
        }

        if ((($data['type'] ?? $setting->type) === Setting::TYPE_JSON) && is_array($data['value'])) {
            $data['value'] = json_encode($data['value']);
        }

        // Encrypt if needed
        if ($this->encryptionService->shouldEncrypt($setting->key) && isset($data['value'])) {
            $data['value'] = $this->encryptionService->encrypt((string) $data['value']);
            $data['is_encrypted'] = true;
        }

        $setting->update($data);
        $this->cacheService->forget($key);

        $this->logService->info('Setting updated', [
            'channel' => 'system',
            'type' => 'setting',
            'related_id' => $setting->id,
            'related_type' => 'App\Models\Setting',
            'user_id' => $request->user()?->id,
            'context' => [
                'key' => $key,
                'old_value' => $oldValue,
                'new_value' => $setting->value,
            ],
        ]);

        return response()->json([
            'success' => true,
            'data' => $setting,
            'message' => 'Setting updated successfully.',
        ]);
    }

    /**
     * Remove the specified setting.
     *
     * @param string $key
     * @return JsonResponse
     */
    public function destroy(Request $request, string $key): JsonResponse
    {
        $setting = Setting::where('key', $key)->firstOrFail();

        // Authorize user
        $this->authorize('delete', $setting);

        $settingId = $setting->id;
        $settingKey = $setting->key;
        $setting->delete();
        $this->cacheService->forget($key);

        $this->logService->info('Setting deleted', [
            'channel' => 'system',
            'type' => 'setting',
            'related_id' => $settingId,
            'related_type' => 'App\Models\Setting',
            'user_id' => $request->user()?->id,
            'context' => ['key' => $settingKey],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Setting deleted successfully.',
        ]);
    }

    /**
     * Get all settings grouped by their group.
     *
     * @return JsonResponse
     */
    public function grouped(): JsonResponse
    {
        // Authorize user
        $this->authorize('create', Setting::class);

        $settings = Setting::orderBy('group')->orderBy('key')->get();
        $grouped = $settings->groupBy('group');

        return response()->json([
            'success' => true,
            'data' => $grouped,
        ]);
    }

    /**
     * Get all public settings.
     *
     * @return JsonResponse
     */
    public function publicSettings(): JsonResponse
    {
        $settings = Setting::public()->orderBy('key')->get();

        return response()->json([
            'success' => true,
            'data' => $settings,
        ]);
    }

    /**
     * Get masked credential for sensitive settings.
     *
     * @param string $key
     * @return JsonResponse
     */
    public function getMaskedCredential(string $key): JsonResponse
    {
        $setting = Setting::where('key', $key)->firstOrFail();

        // Authorize user
        $this->authorize('viewMasked', $setting);

        // Decrypt to get value
        $value = $setting->value;
        if ($setting->is_encrypted) {
            $value = $this->encryptionService->decrypt($value);
        }

        // Mask the credential
        $masked = $this->encryptionService->mask($value);

        return response()->json([
            'success' => true,
            'data' => [
                'key' => $setting->key,
                'masked' => $masked,
                'is_encrypted' => $setting->is_encrypted,
            ],
        ]);
    }

    /**
     * Validate a single credential in a secure setting.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function validateCredential(Request $request): JsonResponse
    {
        $this->authorize('create', Setting::class);

        $validator = Validator::make($request->all(), [
            'key' => ['required', 'string', 'max:255'],
            'value' => ['nullable'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        $setting = Setting::where('key', $data['key'])->first();

        if ($setting && $setting->is_encrypted) {
            $settingValue = $this->encryptionService->decrypt($setting->value);
        } else {
            $settingValue = $data['value'] ?? $setting?->value;
        }

        if (!$settingValue) {
            return response()->json([
                'success' => false,
                'message' => 'No credential value available to validate.',
            ], 400);
        }

        $result = $this->validationService->validateCredential($data['key'], (string) $settingValue);

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }

    /**
     * Validate all integration credentials in the settings store.
     *
     * @return JsonResponse
     */
    public function validateAllCredentials(): JsonResponse
    {
        $this->authorize('create', Setting::class);

        $result = $this->validationService->validateAllCredentials();

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }

    /**
     * Get current health status for the settings and integration layer.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function healthStatus(Request $request): JsonResponse
    {
        $this->authorize('create', Setting::class);

        $health = [
            'timestamp' => now()->toIso8601String(),
            'reverb' => $this->runReverbHealthCheck(),
            'credential_validation' => $this->validationService->validateAllCredentials(),
        ];

        return response()->json([
            'success' => true,
            'data' => $health,
        ]);
    }

    /**
     * Perform a lightweight Reverb connection health check.
     *
     * @return array<string, mixed>
     */
    private function runReverbHealthCheck(): array
    {
        $host = config('broadcasting.connections.reverb.host', env('REVERB_HOST', '127.0.0.1'));
        $port = config('broadcasting.connections.reverb.port', env('REVERB_PORT', 6001));

        try {
            $sock = @fsockopen($host, $port, $errno, $errstr, 3);
            if ($sock) {
                fclose($sock);
                return [
                    'healthy' => true,
                    'message' => "Reverb is reachable on {$host}:{$port}",
                    'host' => $host,
                    'port' => $port,
                ];
            }

            return [
                'healthy' => false,
                'message' => "Unable to connect to Reverb on {$host}:{$port}",
                'error' => $errstr,
            ];
        } catch (\Throwable $exception) {
            return [
                'healthy' => false,
                'message' => 'Reverb health check failed.',
                'error' => $exception->getMessage(),
            ];
        }
    }

    /**
     * Bulk update settings.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function bulkUpdate(Request $request): JsonResponse
    {
        // Authorize user
        $this->authorize('create', Setting::class);

        $validator = Validator::make($request->all(), [
            'settings' => ['required', 'array'],
            'settings.*.key' => ['required', 'string'],
            'settings.*.value' => ['required'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $updated = [];
        foreach ($request->input('settings') as $item) {
            $setting = Setting::where('key', $item['key'])->first();
            if ($setting) {
                // Authorize individual setting update
                try {
                    $this->authorize('update', $setting);
                } catch (\Exception $e) {
                    continue; // Skip unauthorized settings
                }

                $value = $item['value'];

                // Encrypt if needed
                if ($this->encryptionService->shouldEncrypt($setting->key)) {
                    $value = $this->encryptionService->encrypt($value);
                    $setting->is_encrypted = true;
                }

                $setting->update(['value' => $value]);
                $this->cacheService->forget($setting->key);
                $updated[] = $setting;
            }
        }

        $this->logService->info('Settings bulk updated', [
            'channel' => 'system',
            'type' => 'setting',
            'user_id' => $request->user()?->id,
            'context' => ['keys' => array_column($request->input('settings'), 'key'), 'updated_count' => count($updated)],
        ]);

        return response()->json([
            'success' => true,
            'data' => $updated,
            'message' => count($updated) . ' settings updated.',
        ]);
    }

    /**
     * Toggle global agent pause (emergency control).
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function toggleGlobalAgentPause(Request $request): JsonResponse
    {
        // Only super-admins can toggle agent pause
        if (!($request->user()->is_super_admin ?? false)) {
            return response()->json([
                'message' => 'Forbidden',
                'error' => 'Super-admin access required for emergency controls',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'enabled' => ['required', 'boolean'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $enabled = $request->input('enabled');
        $reason = $request->input('reason') ?? 'Emergency control activated';

        // Update setting
        $setting = Setting::firstOrCreate(
            ['key' => 'system.global_agent_pause'],
            [
                'value' => false,
                'type' => 'boolean',
                'group' => 'security',
                'is_public' => false,
                'description' => 'Global pause for all agents (emergency only)',
            ]
        );

        $setting->update(['value' => $enabled]);
        $this->cacheService->forget('system.global_agent_pause');

        // Broadcast event
        event(new GlobalAgentPauseToggled($enabled, $reason));

        $this->logService->info('Global agent pause toggled', [
            'channel' => 'system',
            'type' => 'security',
            'user_id' => $request->user()?->id,
            'context' => ['enabled' => $enabled, 'reason' => $reason],
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'enabled' => $enabled,
                'reason' => $reason,
                'timestamp' => now()->toIso8601String(),
            ],
            'message' => $enabled ? 'Agent pause ACTIVATED' : 'Agent pause DEACTIVATED',
        ]);
    }

    /**
     * Toggle global maintenance mode (emergency control).
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function toggleMaintenanceMode(Request $request): JsonResponse
    {
        // Only super-admins can toggle maintenance mode
        if (!($request->user()->is_super_admin ?? false)) {
            return response()->json([
                'message' => 'Forbidden',
                'error' => 'Super-admin access required for emergency controls',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'enabled' => ['required', 'boolean'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $enabled = $request->input('enabled');
        $reason = $request->input('reason') ?? 'System Maintenance';

        // Update setting
        $setting = Setting::firstOrCreate(
            ['key' => 'system.maintenance_mode'],
            [
                'value' => false,
                'type' => 'boolean',
                'group' => 'security',
                'is_public' => true, // Make public so frontend knows
                'description' => 'Global maintenance mode',
            ]
        );

        $setting->update(['value' => $enabled]);
        $this->cacheService->forget('system.maintenance_mode');

        $this->logService->info('Maintenance mode toggled', [
            'channel' => 'system',
            'type' => 'security',
            'user_id' => $request->user()?->id,
            'context' => ['enabled' => $enabled, 'reason' => $reason],
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'enabled' => $enabled,
                'reason' => $reason,
                'timestamp' => now()->toIso8601String(),
            ],
            'message' => $enabled ? 'Maintenance mode ACTIVATED' : 'Maintenance mode DEACTIVATED',
        ]);
    }

    /**
     * Get available database seeders.
     *
     * @return JsonResponse
     */
    public function listSeeds(): JsonResponse
    {
        // Authorize user
        $user = auth()->user();
        if (!($user && $user->is_super_admin)) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden',
                'error' => 'Super-admin access required',
            ], 403);
        }

        $seeds = $this->seedRunnerService->listAvailableSeeds();

        return response()->json([
            'success' => true,
            'data' => $seeds,
            'count' => count($seeds),
        ]);
    }

    /**
     * Run a database seeder.
     *
     * @param Request $request
     * @param string $seedId
     * @return JsonResponse
     */
    public function runSeed(Request $request, string $seedId): JsonResponse
    {
        // Authorize user
        if (!($request->user()->is_super_admin ?? false)) {
            return response()->json([
                'message' => 'Forbidden',
                'error' => 'Super-admin access required',
            ], 403);
        }

        try {
            $result = $this->seedRunnerService->runSeed($seedId);

            $this->logService->info('Seeder executed', [
                'channel' => 'system',
                'type' => 'database',
                'user_id' => $request->user()?->id,
                'context' => ['seed_id' => $seedId, 'success' => $result['success']],
            ]);

            return response()->json([
                'success' => $result['success'],
                'data' => $result,
            ], $result['success'] ? 200 : 400);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Run multiple database seeders.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function runMultipleSeeds(Request $request): JsonResponse
    {
        // Authorize user
        if (!($request->user()->is_super_admin ?? false)) {
            return response()->json([
                'message' => 'Forbidden',
                'error' => 'Super-admin access required',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'seed_ids' => ['required', 'array'],
            'seed_ids.*' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $result = $this->seedRunnerService->runMultiple($request->input('seed_ids'));

            $this->logService->info('Multiple seeders executed', [
                'channel' => 'system',
                'type' => 'database',
                'user_id' => $request->user()?->id,
                'context' => [
                    'seed_ids' => $request->input('seed_ids'),
                    'successful' => $result['successful'],
                    'failed' => $result['failed'],
                ],
            ]);

            return response()->json([
                'success' => $result['success'],
                'data' => $result,
            ], $result['success'] ? 200 : 207);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Proxy an API request to bypass CORS restrictions.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function apiProxy(Request $request): JsonResponse
    {
        // Require super-admin or high privileges for security
        if (!($request->user()->is_super_admin ?? false)) {
            return response()->json([
                'message' => 'Forbidden',
                'error' => 'Super-admin access required to use the API proxy.',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'url' => ['required', 'url'],
            'method' => ['required', 'string', 'in:GET,POST,PUT,PATCH,DELETE'],
            'headers' => ['nullable', 'array'],
            'body' => ['nullable'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $url = $request->input('url');
        $method = strtoupper($request->input('method'));
        $headers = $request->input('headers', []);
        $body = $request->input('body');

        // Clean out empty headers and format as key-value
        $formattedHeaders = [];
        foreach ($headers as $header) {
            if (!empty($header['key'])) {
                $formattedHeaders[$header['key']] = $header['value'] ?? '';
            }
        }

        $startTime = microtime(true);

        try {
            $pendingRequest = \Illuminate\Support\Facades\Http::withHeaders($formattedHeaders)
                ->withoutVerifying() // Useful for testing self-signed or local APIs
                ->timeout(30);

            if (in_array($method, ['POST', 'PUT', 'PATCH'])) {
                $response = $pendingRequest->send($method, $url, [
                    'body' => is_array($body) ? json_encode($body) : $body,
                ]);
            } else {
                $response = $pendingRequest->send($method, $url);
            }

            $latency = round((microtime(true) - $startTime) * 1000);

            $bodyStr = $response->body();
            $jsonBody = json_decode($bodyStr, true);

            return response()->json([
                'success' => true,
                'data' => [
                    'status' => $response->status(),
                    'headers' => $response->headers(),
                    'body' => $jsonBody !== null ? $jsonBody : $bodyStr,
                    'latency' => $latency,
                ]
            ]);

        } catch (\Exception $e) {
            $latency = round((microtime(true) - $startTime) * 1000);
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'data' => [
                    'latency' => $latency,
                ]
            ], 500);
        }
    }
}
