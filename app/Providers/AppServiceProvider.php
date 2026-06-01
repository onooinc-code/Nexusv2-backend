<?php

namespace App\Providers;

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use App\Events\MemoryIndexed;
use App\Events\WorkflowCompleted;
use App\Events\WorkflowStarted;
use App\Events\ContactCreated;
use App\Events\MessageReceived;
use App\Listeners\IndexMemory;
use App\Listeners\LogJobFailed;
use App\Listeners\LogWorkflowCompleted;
use App\Listeners\LogWorkflowStarted;
use App\Listeners\LogWorkflowStepCompleted;
use App\Listeners\NotifyJobFailed;
use App\Listeners\ProcessContactCreated;
use App\Listeners\ProcessMessageReceived;
use App\Listeners\TriggerWorkflowsForEvent;
use App\Models\ConversationSession;
use Illuminate\Queue\Events\JobFailed;
use App\Models\User;
use App\Policies\SessionPolicy;
use App\Policies\SettingPolicy;
use App\Models\Setting;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register Singleton Services
        $this->app->singleton('nexus.memory', function ($app) {
            return new \App\Services\Memory\MemoryService($app['cache'], $app['db']);
        });

        $this->app->singleton('nexus.ai', function ($app) {
            return $app->make(\App\Services\AiModelsHub\UniversalAiGatewayService::class);
        });

        $this->app->singleton('nexus.whatsapp', function ($app) {
            return new \App\Services\WhatsApp\WAHAService($app['config']);
        });

        $this->app->singleton('nexus.router', function ($app) {
            return new \App\Services\Routing\MessageRouterService($app['cache']);
        });

        $this->app->singleton(
            \Illuminate\Contracts\Console\Kernel::class,
            \App\Console\Kernel::class,
        );

        // Bind Interface implementations
        $this->app->bind(
            \App\Contracts\MemoryEngineContract::class,
            \App\Services\Memory\MemoryEngine::class
        );

        $this->app->bind(
            \App\Contracts\AIEngineContract::class,
            \App\Services\AI\AIEngine::class
        );

        $this->app->bind(
            \App\Contracts\IntentRouterContract::class,
            \App\Services\Routing\IntentRouter::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Configure pagination
        \Illuminate\Pagination\Paginator::useBootstrap();

        // Register event listeners
        Event::listen(MemoryIndexed::class, [IndexMemory::class, 'handle']);
        Event::listen(ContactCreated::class, [ProcessContactCreated::class, 'handle']);
        Event::listen(ContactCreated::class, [TriggerWorkflowsForEvent::class, 'handle']);
        Event::listen(MessageReceived::class, [ProcessMessageReceived::class, 'handle']);
        Event::listen(MessageReceived::class, [TriggerWorkflowsForEvent::class, 'handle']);
        Event::listen(WorkflowStarted::class, [LogWorkflowStarted::class, 'handle']);
        Event::listen(WorkflowCompleted::class, [LogWorkflowCompleted::class, 'handle']);
        Event::listen(\App\Events\WorkflowStepCompleted::class, [LogWorkflowStepCompleted::class, 'handle']);
        Event::listen(JobFailed::class, [LogJobFailed::class, 'handle']);
        Event::listen(JobFailed::class, [NotifyJobFailed::class, 'handle']);
        Event::listen(\App\Events\TaskCompletedEvent::class, [\App\Listeners\ResumeWorkflowOnTaskCompletion::class, 'handle']);
        Event::listen(\App\Events\TaskFailedEvent::class, [\App\Listeners\ResumeWorkflowOnTaskCompletion::class, 'handle']);

        // Register broadcast authorization policies
        Gate::policy(ConversationSession::class, SessionPolicy::class);
        Gate::policy(Setting::class, SettingPolicy::class);
        Gate::define('viewBatch', fn (User $user, string $batchId): bool => in_array($user->email, config('broadcasting.admin_emails', []), true));
        Gate::define('viewDlq', fn (User $user): bool => in_array($user->email, config('broadcasting.admin_emails', []), true));

        // Register broadcast auth routes and load channel definitions
        Broadcast::routes(['middleware' => ['web', 'auth:sanctum']]);
        if (file_exists(base_path('routes/channels.php'))) {
            require base_path('routes/channels.php');
        }

        // Apply optional Redis fallback when local Redis is unavailable.
        $this->configureRedisFallback();

        // Register macros for common operations
        $this->registerMacros();

        // Register wildcard workflow event trigger listener
        app(\App\Services\Workflows\WorkflowEventTriggerService::class)->registerWildcardListener();
    }

    /**
     * Apply a safe local fallback when Redis cannot be reached.
     */
    protected function configureRedisFallback(): void
    {
        if (! env('REDIS_FALLBACK', false)) {
            return;
        }

        try {
            $connection = app('redis')->connection();
            if (! $connection->ping()) {
                throw new \RuntimeException('Redis ping failed');
            }
        } catch (\Throwable $exception) {
            Log::warning('Redis unavailable, falling back to local drivers.', [
                'redis_host' => env('REDIS_HOST'),
                'redis_port' => env('REDIS_PORT'),
                'exception' => $exception->getMessage(),
            ]);

            config(['cache.default' => 'file']);
            config(['session.driver' => 'database']);
            config(['queue.default' => 'sync']);
        }
    }

    /**
     * Register helper macros
     */
    protected function registerMacros(): void
    {
        // Collection macros for common operations
        \Illuminate\Support\Collection::macro('mapWithKeys', function (callable $callback) {
            $result = [];
            foreach ($this as $key => $value) {
                $mapped = $callback($value, $key);
                foreach ($mapped as $k => $v) {
                    $result[$k] = $v;
                }
            }
            return new self($result);
        });
    }
}
