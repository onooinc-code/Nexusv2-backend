<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;

/**
 * API Routes for Nexus Platform
 * All routes are prefixed with /api/v1
 */

// Public routes (no authentication required)
Route::group(['prefix' => 'v1', 'middleware' => ['api']], function () {

    // Health check endpoint
    Route::get('/health', function (Request $request) {
        return response()->json([
            'status' => 'healthy',
            'timestamp' => now(),
            'app' => config('app.name'),
        ]);
    });

    // Broadcast auth for token-based (Sanctum) clients — supports Bearer tokens
    Route::post('/broadcasting/auth', function (Request $request) {
        // If a bearer token is present, try to authenticate the tokenable user manually
        $bearer = $request->bearerToken();
        \Log::info('broadcasting.auth called', ['bearer_present' => $bearer ? true : false]);
        if ($bearer) {
            $tokenModel = \Laravel\Sanctum\PersonalAccessToken::findToken($bearer);
            if ($tokenModel && $tokenModel->tokenable) {
                auth()->loginUsingId($tokenModel->tokenable->getAuthIdentifier());
                \Log::info('broadcasting.auth: logged in tokenable', ['user_id' => $tokenModel->tokenable->getAuthIdentifier()]);
            } else {
                \Log::warning('broadcasting.auth: token not found or has no tokenable');
            }
        }

        $resp = Broadcast::auth($request);
        \Log::info('broadcasting.auth: Broadcast::auth result', ['status' => $resp ? 'present' : 'empty']);
        return $resp;
    });

    // WAHA WhatsApp webhook endpoint
    Route::post('/webhooks/waha', [\App\Http\Controllers\WebhookController::class, 'handleWahaWebhook'])
        ->name('webhooks.waha');

    Route::prefix('monitoring')->group(function () {
        Route::get('/health', [\App\Http\Controllers\Monitoring\HealthController::class, 'health']);
        Route::get('/health/reverb', [\App\Http\Controllers\Monitoring\HealthController::class, 'reverb']);
        Route::get('/health/queue', [\App\Http\Controllers\Monitoring\HealthController::class, 'queue']);
        Route::get('/metrics', [\App\Http\Controllers\Monitoring\MetricsController::class, 'metrics']);
        Route::get('/metrics/websocket', [\App\Http\Controllers\Monitoring\MetricsController::class, 'websocket']);
    });

    // Sanctum authentication routes
    Route::post('/login', [\App\Http\Controllers\AuthController::class, 'login'])
        ->name('login');

    Route::post('/register', [\App\Http\Controllers\AuthController::class, 'register'])
        ->name('register');

    Route::post('/verify-token', [\App\Http\Controllers\AuthController::class, 'verifyToken'])
        ->name('verify-token');
});

// Protected routes (authentication required via Sanctum)
Route::group(['prefix' => 'v1', 'middleware' => ['api', EnsureFrontendRequestsAreStateful::class, 'auth:sanctum']], function () {
    // Authentication actions
    Route::post('/logout', [\App\Http\Controllers\AuthController::class, 'logout'])
        ->name('logout');

    /**
     * Contacts Hub Routes
     */
    Route::post('/contacts/import', [\App\Http\Controllers\ContactController::class, 'import'])
        ->name('contacts.import');
    Route::get('/contacts/export', [\App\Http\Controllers\ContactController::class, 'export'])
        ->name('contacts.export');
    Route::get('/contacts/{id}/memory', [\App\Http\Controllers\ContactController::class, 'getMemory'])
        ->name('contacts.memory');
    Route::get('/contacts/{id}/rules', [\App\Http\Controllers\ContactController::class, 'getRules'])
        ->name('contacts.rules');
    Route::get('/contacts/{id}/timeline', [\App\Http\Controllers\ContactController::class, 'timeline'])
        ->name('contacts.timeline');
    Route::get('/contacts/{id}/analytics', [\App\Http\Controllers\ContactController::class, 'getAnalytics'])
        ->name('contacts.analytics');
    Route::post('/contacts/{id}/merge', [\App\Http\Controllers\ContactController::class, 'merge'])
        ->name('contacts.merge');
    Route::delete('/contacts/{id}/erase', [\App\Http\Controllers\ContactController::class, 'erase'])
        ->name('contacts.erase');
    Route::post('/contacts/{id}/enrich', [\App\Http\Controllers\ContactController::class, 'enrich'])
        ->name('contacts.enrich');
    Route::resource('contacts', \App\Http\Controllers\ContactController::class);

    /**
     * Contact Sub-resources Routes
     */
    Route::get('/contacts/{contact}/identifiers', [\App\Http\Controllers\ContactIdentifierController::class, 'index'])
        ->name('contacts.identifiers.index');
    Route::post('/contacts/{contact}/identifiers', [\App\Http\Controllers\ContactIdentifierController::class, 'store'])
        ->name('contacts.identifiers.store');
    Route::get('/contacts/{contact}/identifiers/{identifier}', [\App\Http\Controllers\ContactIdentifierController::class, 'show'])
        ->name('contacts.identifiers.show');
    Route::put('/contacts/{contact}/identifiers/{identifier}', [\App\Http\Controllers\ContactIdentifierController::class, 'update'])
        ->name('contacts.identifiers.update');
    Route::delete('/contacts/{contact}/identifiers/{identifier}', [\App\Http\Controllers\ContactIdentifierController::class, 'destroy'])
        ->name('contacts.identifiers.destroy');

    Route::get('/contacts/{contact}/relationships', [\App\Http\Controllers\ContactRelationshipController::class, 'index'])
        ->name('contacts.relationships.index');
    Route::post('/contacts/{contact}/relationships', [\App\Http\Controllers\ContactRelationshipController::class, 'store'])
        ->name('contacts.relationships.store');
    Route::get('/contacts/{contact}/relationships/{relationship}', [\App\Http\Controllers\ContactRelationshipController::class, 'show'])
        ->name('contacts.relationships.show');
    Route::put('/contacts/{contact}/relationships/{relationship}', [\App\Http\Controllers\ContactRelationshipController::class, 'update'])
        ->name('contacts.relationships.update');
    Route::delete('/contacts/{contact}/relationships/{relationship}', [\App\Http\Controllers\ContactRelationshipController::class, 'destroy'])
        ->name('contacts.relationships.destroy');

    Route::get('/contacts/{contact}/preferences', [\App\Http\Controllers\ContactPreferenceController::class, 'index'])
        ->name('contacts.preferences.index');
    Route::post('/contacts/{contact}/preferences', [\App\Http\Controllers\ContactPreferenceController::class, 'store'])
        ->name('contacts.preferences.store');
    Route::get('/contacts/{contact}/preferences/{preference}', [\App\Http\Controllers\ContactPreferenceController::class, 'show'])
        ->name('contacts.preferences.show');
    Route::put('/contacts/{contact}/preferences/{preference}', [\App\Http\Controllers\ContactPreferenceController::class, 'update'])
        ->name('contacts.preferences.update');
    Route::delete('/contacts/{contact}/preferences/{preference}', [\App\Http\Controllers\ContactPreferenceController::class, 'destroy'])
        ->name('contacts.preferences.destroy');

    Route::get('/contacts/{contact}/aliases', [\App\Http\Controllers\ContactAliasController::class, 'index'])
        ->name('contacts.aliases.index');
    Route::post('/contacts/{contact}/aliases', [\App\Http\Controllers\ContactAliasController::class, 'store'])
        ->name('contacts.aliases.store');
    Route::get('/contacts/{contact}/aliases/{alias}', [\App\Http\Controllers\ContactAliasController::class, 'show'])
        ->name('contacts.aliases.show');
    Route::put('/contacts/{contact}/aliases/{alias}', [\App\Http\Controllers\ContactAliasController::class, 'update'])
        ->name('contacts.aliases.update');
    Route::delete('/contacts/{contact}/aliases/{alias}', [\App\Http\Controllers\ContactAliasController::class, 'destroy'])
        ->name('contacts.aliases.destroy');

    Route::get('/contacts/{contact}/notes', [\App\Http\Controllers\ContactNoteController::class, 'index'])
        ->name('contacts.notes.index');
    Route::post('/contacts/{contact}/notes', [\App\Http\Controllers\ContactNoteController::class, 'store'])
        ->name('contacts.notes.store');
    Route::get('/contacts/{contact}/notes/{note}', [\App\Http\Controllers\ContactNoteController::class, 'show'])
        ->name('contacts.notes.show');
    Route::put('/contacts/{contact}/notes/{note}', [\App\Http\Controllers\ContactNoteController::class, 'update'])
        ->name('contacts.notes.update');
    Route::delete('/contacts/{contact}/notes/{note}', [\App\Http\Controllers\ContactNoteController::class, 'destroy'])
        ->name('contacts.notes.destroy');

    /**
     * Notification Hub Routes
     */
    Route::get('/notifications/templates', [\App\Http\Controllers\NotificationController::class, 'indexTemplates'])
        ->name('notifications.templates.index');
    Route::post('/notifications/templates', [\App\Http\Controllers\NotificationController::class, 'storeTemplate'])
        ->name('notifications.templates.store');
    Route::get('/notifications/templates/{template}', [\App\Http\Controllers\NotificationController::class, 'showTemplate'])
        ->name('notifications.templates.show');
    Route::put('/notifications/templates/{template}', [\App\Http\Controllers\NotificationController::class, 'updateTemplate'])
        ->name('notifications.templates.update');
    Route::delete('/notifications/templates/{template}', [\App\Http\Controllers\NotificationController::class, 'destroyTemplate'])
        ->name('notifications.templates.destroy');

    Route::get('/notifications/logs', [\App\Http\Controllers\NotificationController::class, 'indexLogs'])
        ->name('notifications.logs.index');
    Route::post('/notifications/send', [\App\Http\Controllers\NotificationController::class, 'send'])
        ->name('notifications.send');
    Route::post('/notifications/{notification}/retry', [\App\Http\Controllers\NotificationController::class, 'retry'])
        ->name('notifications.retry');

    /**
     * Conversations Routes
     */
    Route::resource('conversations', \App\Http\Controllers\ConversationController::class);
    Route::get('/conversations/{id}/messages', [\App\Http\Controllers\ConversationController::class, 'getMessages'])
        ->name('conversations.messages');
    Route::post('/conversations/{id}/send-message', [\App\Http\Controllers\ConversationController::class, 'sendMessage'])
        ->name('conversations.send-message');

    /**
     * Agents Hub Routes
     */
    Route::resource('agents', \App\Http\Controllers\AgentController::class);
    Route::post('/agents/{id}/run', [\App\Http\Controllers\AgentController::class, 'run'])
        ->name('agents.run');
    Route::post('/agents/{id}/simulate', [\App\Http\Controllers\AgentController::class, 'simulate'])
        ->name('agents.simulate');
    Route::post('/agents/{id}/quarantine', [\App\Http\Controllers\AgentController::class, 'quarantine'])
        ->name('agents.quarantine');
    Route::post('/agents/{id}/unquarantine', [\App\Http\Controllers\AgentController::class, 'unquarantine'])
        ->name('agents.unquarantine');
    Route::get('/agents/{id}/status', [\App\Http\Controllers\AgentController::class, 'getStatus'])
        ->name('agents.status');
    Route::get('/agents/{id}/logs', [\App\Http\Controllers\AgentController::class, 'getLogs'])
        ->name('agents.logs');

    Route::resource('agent-personas', \App\Http\Controllers\AgentPersonaController::class);
    Route::resource('mcp-servers', \App\Http\Controllers\MCPServerController::class);
    Route::post('/mcp-servers/{name}/connect', [\App\Http\Controllers\MCPServerController::class, 'connect'])
        ->name('mcp-servers.connect');
    Route::post('/mcp-servers/{name}/disconnect', [\App\Http\Controllers\MCPServerController::class, 'disconnect'])
        ->name('mcp-servers.disconnect');

    /**
     * Workflows Hub Routes
     * NOTE: Specific routes must be defined BEFORE resource routes to prevent route conflicts
     */
    Route::get('/workflows/templates', [\App\Http\Controllers\WorkflowController::class, 'getTemplates'])
       ->name('workflows.templates');

    Route::resource('workflows', \App\Http\Controllers\WorkflowController::class);

    // Workflow action routes on specific resources
    Route::post('/workflows/{id}/execute', [\App\Http\Controllers\WorkflowController::class, 'execute'])
       ->name('workflows.execute');
    Route::get('/workflows/{id}/progress', [\App\Http\Controllers\WorkflowController::class, 'getProgress'])
       ->name('workflows.progress');

    /**
     * Tasks Hub Routes
     * NOTE: Specific routes must be defined BEFORE resource routes to prevent route conflicts
     */
    // Specific action routes (must come before resource)
    Route::get('/tasks/stats', [\App\Http\Controllers\TaskController::class, 'getStats'])
       ->name('tasks.stats');
    Route::get('/tasks/active', [\App\Http\Controllers\TaskController::class, 'getActive'])
       ->name('tasks.active');
    Route::get('/tasks/queue-stats', [\App\Http\Controllers\TaskController::class, 'getQueueStats'])
       ->name('tasks.queue-stats');
    Route::get('/tasks/routing-stats', [\App\Http\Controllers\TaskController::class, 'getRoutingStats'])
       ->name('tasks.routing-stats');

    // Resource routes
    Route::resource('tasks', \App\Http\Controllers\TaskController::class);

    // Task action routes on specific resources
    Route::post('/tasks/{id}/cancel', [\App\Http\Controllers\TaskController::class, 'cancel'])
       ->name('tasks.cancel');
    Route::post('/tasks/{id}/pause', [\App\Http\Controllers\TaskController::class, 'pause'])
       ->name('tasks.pause');
    Route::post('/tasks/{id}/resume', [\App\Http\Controllers\TaskController::class, 'resume'])
       ->name('tasks.resume');

    Route::get('/stats/usage', [\App\Http\Controllers\StatsController::class, 'usage'])
       ->name('stats.usage');

    Route::get('/stats/dashboard', [\App\Http\Controllers\StatsController::class, 'dashboard'])
       ->name('stats.dashboard');

    /**
     * Memory Hub Routes
     * NOTE: Specific routes must be defined BEFORE resource routes to prevent route conflicts
     */
    Route::get('/memories/search', [\App\Http\Controllers\MemoryController::class, 'search'])
        ->name('memories.search');
    Route::post('/memories/{id}/index', [\App\Http\Controllers\MemoryController::class, 'indexMemory'])
        ->name('memories.indexMemory');

    Route::resource('memories', \App\Http\Controllers\MemoryController::class);

    Route::middleware(['can:viewDlq'])->group(function () {
        Route::get('/admin/dlq', [\App\Http\Controllers\Admin\DlqController::class, 'index']);
        Route::post('/admin/dlq/{id}/retry', [\App\Http\Controllers\Admin\DlqController::class, 'retry']);
        Route::delete('/admin/dlq/{id}', [\App\Http\Controllers\Admin\DlqController::class, 'destroy']);
        Route::post('/admin/dlq/batch-retry', [\App\Http\Controllers\Admin\DlqController::class, 'batchRetry']);
    });

    /**
     * AI Models Hub Routes
     * NOTE: Specific routes must be defined BEFORE resource routes to prevent route conflicts
     */
    // AI Models custom routes (must come before resource)
    Route::post('/ai-models/execute', [\App\Http\Controllers\AiModelController::class, 'execute'])
        ->name('ai-models.execute');

    Route::post('/ai-models/execute-with-fallback', [\App\Http\Controllers\AiModelController::class, 'executeWithFallback'])
        ->name('ai-models.execute-with-fallback');

    Route::post('/ai-models/select', [\App\Http\Controllers\AiModelController::class, 'selectModel'])
        ->name('ai-models.select');

    Route::post('/ai-models/optimize-cost', [\App\Http\Controllers\AiModelController::class, 'optimizeCost'])
        ->name('ai-models.optimize-cost');

    Route::post('/ai-models/route-quality', [\App\Http\Controllers\AiModelController::class, 'routeByQuality'])
        ->name('ai-models.route-quality');
    Route::post('/ai-models/route-speed', [\App\Http\Controllers\AiModelController::class, 'routeBySpeed'])
        ->name('ai-models.route-speed');

    Route::get('/ai-models/providers', [\App\Http\Controllers\AiModelController::class, 'providers'])
        ->name('ai-models.providers');
    Route::get('/ai-models/key-pool', [\App\Http\Controllers\AiModelController::class, 'keyPoolStatus'])
        ->name('ai-models.key-pool');
    Route::get('/ai-models/key-health', [\App\Http\Controllers\AiModelController::class, 'keyHealth'])
        ->name('ai-models.key-health');
    Route::get('/ai-models/rate-limits', [\App\Http\Controllers\AiModelController::class, 'rateLimitStatus'])
        ->name('ai-models.rate-limits');

    Route::get('/ai-models/rotation-schedule', [\App\Http\Controllers\AiModelController::class, 'rotationSchedule'])
        ->name('ai-models.rotation-schedule');
    Route::post('/ai-models/rotate-expired', [\App\Http\Controllers\AiModelController::class, 'rotateExpired'])
        ->name('ai-models.rotate-expired');
    Route::get('/ai-models/fallback-chain', [\App\Http\Controllers\AiModelController::class, 'fallbackChainStatus'])
        ->name('ai-models.fallback-chain');
    Route::get('/ai-models/budget', [\App\Http\Controllers\AiModelController::class, 'budgetStatus'])
        ->name('ai-models.budget');

    // AI Models resource and ID-specific routes
    Route::resource('ai-models', \App\Http\Controllers\AiModelController::class);
    Route::post('/ai-models/{id}/test', [\App\Http\Controllers\AiModelController::class, 'test'])
        ->name('ai-models.test');

    // New AI Models Hub endpoints for UP-002
    Route::get('/ai/providers', [\App\Http\Controllers\AiProviderController::class, 'index'])
        ->name('ai.providers.index');
    Route::post('/ai/providers', [\App\Http\Controllers\AiProviderController::class, 'store'])
        ->name('ai.providers.store');
    Route::get('/ai/providers/{id}', [\App\Http\Controllers\AiProviderController::class, 'show'])
        ->name('ai.providers.show');
    Route::put('/ai/providers/{id}', [\App\Http\Controllers\AiProviderController::class, 'update'])
        ->name('ai.providers.update');
    Route::delete('/ai/providers/{id}', [\App\Http\Controllers\AiProviderController::class, 'destroy'])
        ->name('ai.providers.destroy');
    Route::post('/ai/providers/{id}/test', [\App\Http\Controllers\AiProviderController::class, 'test'])
        ->name('ai.providers.test');
    Route::post('/ai/providers/{id}/sync-models', [\App\Http\Controllers\AiProviderController::class, 'syncModels'])
        ->name('ai.providers.sync-models');
    Route::patch('/ai/providers/{id}/toggle-active', [\App\Http\Controllers\AiProviderController::class, 'toggleActive'])
        ->name('ai.providers.toggle-active');
    Route::get('/ai/intents/routing', [\App\Http\Controllers\AiRequestController::class, 'getRoutingMatrix'])
        ->name('ai.intents.routing.index');
    Route::put('/ai/intents/routing', [\App\Http\Controllers\AiRequestController::class, 'routeIntent'])
        ->name('ai.intents.routing.update');
    Route::post('/ai/request', [\App\Http\Controllers\AiRequestController::class, 'handleRequest'])
        ->name('ai.request.handle');
    
    // Core routing execution endpoint
    Route::post('/ai-models/route', [\App\Http\Controllers\AiRouteController::class, 'route'])
        ->name('ai-models.route');
        
    // Cost Analytics & Budget Endpoints
    Route::get('/ai/cost/forecast', [\App\Http\Controllers\AiCostAnalyticsController::class, 'forecast'])
        ->name('ai.cost.forecast');
    Route::post('/ai/cost/budget', [\App\Http\Controllers\AiCostAnalyticsController::class, 'setBudget'])
        ->name('ai.cost.budget');

    // Provider Health & Observability
    Route::get('/ai/providers/health', [\App\Http\Controllers\AiRouteController::class, 'providerHealth'])
        ->name('ai.providers.health');
    Route::get('/ai/audit-trail', [\App\Http\Controllers\AiRouteController::class, 'auditTrail'])
        ->name('ai.audit.trail');


    /**
     * Settings Hub Routes
     */
    Route::group(['prefix' => 'settings'], function () {
       Route::get('/', [\App\Http\Controllers\SettingController::class, 'index'])
             ->name('settings.index');
       Route::post('/', [\App\Http\Controllers\SettingController::class, 'store'])
             ->name('settings.store');
       Route::get('/grouped', [\App\Http\Controllers\SettingController::class, 'grouped'])
             ->name('settings.grouped');
       Route::get('/public', [\App\Http\Controllers\SettingController::class, 'publicSettings'])
             ->name('settings.public');
       Route::put('/bulk', [\App\Http\Controllers\SettingController::class, 'bulkUpdate'])
             ->name('settings.bulk-update');
       
       // Emergency control routes (super-admin only)
       Route::post('/system/agent-pause', [\App\Http\Controllers\SettingController::class, 'toggleGlobalAgentPause'])
             ->middleware('can:toggleEmergency,App\Models\Setting')
             ->name('settings.agent-pause');
             
       Route::post('/system/api-proxy', [\App\Http\Controllers\SettingController::class, 'apiProxy'])
             ->name('settings.api-proxy');
       
       // Seed manager routes (super-admin only)
       Route::get('/seeds', [\App\Http\Controllers\SettingController::class, 'listSeeds'])
             ->middleware('can:runSeeder,App\Models\Setting')
             ->name('settings.seeds.list');
       Route::post('/seeds/{seedId}/run', [\App\Http\Controllers\SettingController::class, 'runSeed'])
             ->middleware('can:runSeeder,App\Models\Setting')
             ->name('settings.seeds.run');
       Route::post('/seeds/run-multiple', [\App\Http\Controllers\SettingController::class, 'runMultipleSeeds'])
             ->middleware('can:runSeeder,App\Models\Setting')
             ->name('settings.seeds.run-multiple');
       
       // Credential validation and health routes
       Route::post('/credentials/validate', [\App\Http\Controllers\SettingController::class, 'validateCredential'])
             ->name('settings.credentials.validate');
       Route::get('/credentials/validate', [\App\Http\Controllers\SettingController::class, 'validateAllCredentials'])
             ->name('settings.credentials.validate_all');
       Route::get('/health', [\App\Http\Controllers\SettingController::class, 'healthStatus'])
             ->name('settings.health');
       
       // Admin dashboard routes (super-admin only)
       Route::group(['prefix' => 'admin', 'middleware' => 'can:create,App\Models\Setting'], function () {
           Route::get('/dashboard', [\App\Http\Controllers\SettingsHubAdminController::class, 'dashboardOverview'])
                 ->name('settings.admin.dashboard');
           Route::get('/audit-trail', [\App\Http\Controllers\SettingsHubAdminController::class, 'auditTrail'])
                 ->name('settings.admin.audit-trail');
           Route::get('/compliance', [\App\Http\Controllers\SettingsHubAdminController::class, 'complianceStatus'])
                 ->name('settings.admin.compliance');
           Route::get('/multi-tenancy', [\App\Http\Controllers\SettingsHubAdminController::class, 'multiTenancyStatus'])
                 ->name('settings.admin.multi-tenancy');
           Route::get('/performance', [\App\Http\Controllers\SettingsHubAdminController::class, 'performanceMetrics'])
                 ->name('settings.admin.performance');
           Route::post('/export', [\App\Http\Controllers\SettingsHubAdminController::class, 'exportSettings'])
                 ->name('settings.admin.export');
       });
       
       // Credential masking route
       Route::get('/{key}/masked', [\App\Http\Controllers\SettingController::class, 'getMaskedCredential'])
             ->name('settings.masked');
       
       // Standard CRUD routes
       Route::get('/{key}', [\App\Http\Controllers\SettingController::class, 'show'])
             ->name('settings.show');
       Route::put('/{key}', [\App\Http\Controllers\SettingController::class, 'update'])
             ->name('settings.update');
       Route::delete('/{key}', [\App\Http\Controllers\SettingController::class, 'destroy'])
             ->name('settings.destroy');
    });

    /**
     * Scheduler Hub Routes
     */
    Route::group(['prefix' => 'scheduler'], function () {
       Route::get('/', [\App\Http\Controllers\SchedulerController::class, 'index'])->name('scheduler.index');
       Route::post('/', [\App\Http\Controllers\SchedulerController::class, 'store'])->name('scheduler.store');
       Route::get('/{schedulerJob}', [\App\Http\Controllers\SchedulerController::class, 'show'])->name('scheduler.show');
       Route::put('/{id}', [\App\Http\Controllers\SchedulerController::class, 'update'])->name('scheduler.update');
       Route::delete('/{id}', [\App\Http\Controllers\SchedulerController::class, 'destroy'])->name('scheduler.destroy');
    });

    /**
     * Proactive AI Engine Routes
     */
    Route::group(['prefix' => 'proactive'], function () {
        Route::get('/rules', [\App\Http\Controllers\ProactiveAIController::class, 'indexRules'])->name('proactive.rules.index');
        Route::post('/rules', [\App\Http\Controllers\ProactiveAIController::class, 'storeRule'])->name('proactive.rules.store');
        Route::patch('/rules/{id}/toggle', [\App\Http\Controllers\ProactiveAIController::class, 'toggleRule'])->name('proactive.rules.toggle');
        Route::delete('/rules/{id}', [\App\Http\Controllers\ProactiveAIController::class, 'destroyRule'])->name('proactive.rules.destroy');
        Route::get('/triggers', [\App\Http\Controllers\ProactiveAIController::class, 'indexTriggers'])->name('proactive.triggers.index');
        Route::get('/logs', [\App\Http\Controllers\ProactiveAIController::class, 'indexLogs'])->name('proactive.logs.index');
    });

    /**
     * Logs Hub Routes
     */
    Route::group(['prefix' => 'logs'], function () {
       Route::get('/', [\App\Http\Controllers\LogController::class, 'index'])
             ->name('logs.index');
       Route::post('/clear', [\App\Http\Controllers\LogController::class, 'clear'])
             ->name('logs.clear');
       Route::get('/stats', [\App\Http\Controllers\LogController::class, 'stats'])
             ->name('logs.stats');
       Route::get('/levels', [\App\Http\Controllers\LogController::class, 'levels'])
             ->name('logs.levels');
       Route::get('/channels', [\App\Http\Controllers\LogController::class, 'channels'])
             ->name('logs.channels');
       Route::get('/categories', [\App\Http\Controllers\LogController::class, 'channels'])
             ->name('logs.categories');
       Route::get('/errors', [\App\Http\Controllers\LogController::class, 'errors'])
             ->name('logs.errors');
       Route::get('/{id}', [\App\Http\Controllers\LogController::class, 'show'])
             ->name('logs.show');
       Route::delete('/{id}', [\App\Http\Controllers\LogController::class, 'destroy'])
             ->name('logs.destroy');
    });

    /**
     * User Profile Routes
     */
    Route::group(['prefix' => 'profile'], function () {
        Route::get('/', [\App\Http\Controllers\ProfileController::class, 'show'])
            ->name('profile.show');
        Route::put('/', [\App\Http\Controllers\ProfileController::class, 'update'])
            ->name('profile.update');
        Route::post('/avatar', [\App\Http\Controllers\ProfileController::class, 'updateAvatar'])
            ->name('profile.avatar');
    });

    /**
     * Authentication Routes
     */
    Route::post('/logout', [\App\Http\Controllers\AuthController::class, 'logout'])
        ->name('logout');

    Route::post('/refresh-token', [\App\Http\Controllers\AuthController::class, 'refreshToken'])
        ->name('refresh-token');
});

// Fallback route
Route::fallback(function () {
    return response()->json([
        'error' => 'Not Found',
        'message' => 'The requested resource was not found',
    ], 404);
});
