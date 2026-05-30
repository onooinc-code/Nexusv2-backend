# Task 4: Integrate LogService into AgentsHub

## Objective
Replace Laravel's `Log::` facade with `LogService` in AgentsHub for consistent logging.

## Current State
- `AgentController` uses `Log::info()` and `Log::error()` directly
- `AgentLifecycleService` uses `Log::` facade
- `AgentToolExecutor` uses `Log::` facade

## Implementation Steps

### 1. Update AgentController (`app/Http/Controllers/AgentController.php`)
Inject LogService and use it for logging:
```php
use App\Services\LogService;

class AgentController extends Controller
{
    protected LogService $logService;
    
    public function __construct(LogService $logService)
    {
        $this->logService = $logService;
    }
    
    public function store(Request $request): JsonResponse
    {
        // ... existing code ...
        
        $this->logService->info('Agent created', [
            'channel' => 'agent',
            'type' => 'create',
            'related_id' => $agent->id,
            'related_type' => 'App\Models\Agent',
            'user_id' => $request->user()->id,
        ]);
        
        return response()->json([...]);
    }
    
    public function execute($id, Request $request): JsonResponse
    {
        // ... existing code ...
        
        $this->logService->info('Agent execution started', [
            'channel' => 'agent',
            'type' => 'execute',
            'related_id' => $agent->id,
            'related_type' => 'App\Models\Agent',
            'user_id' => $request->user()->id,
        ]);
        
        // ... on completion ...
        
        $this->logService->info('Agent execution completed', [
            'channel' => 'agent',
            'type' => 'execute',
            'related_id' => $agent->id,
            'related_type' => 'App\Models\Agent',
            'user_id' => $request->user()->id,
        ]);
    }
}
```

### 2. Update AgentLifecycleService (`app/Services/AgentLifecycleService.php`)
```php
use App\Services\LogService;

class AgentLifecycleService
{
    protected LogService $logService;
    
    public function __construct(LogService $logService)
    {
        $this->logService = $logService;
    }
    
    public function execute(Agent $agent, array $input): array
    {
        $this->logService->info('Agent lifecycle: starting execution', [
            'channel' => 'agent',
            'type' => 'lifecycle',
            'related_id' => $agent->id,
            'related_type' => 'App\Models\Agent',
        ]);
        
        // ... existing code ...
    }
}
```

### 3. Update AgentToolExecutor (`app/Services/AgentToolExecutor.php`)
```php
use App\Services\LogService;

class AgentToolExecutor
{
    protected LogService $logService;
    
    public function __construct(LogService $logService)
    {
        $this->logService = $logService;
    }
    
    public function execute(Agent $agent, string $tool, array $params): mixed
    {
        $this->logService->info('Tool execution started', [
            'channel' => 'agent',
            'type' => 'tool',
            'related_id' => $agent->id,
            'related_type' => 'App\Models\Agent',
            'context' => ['tool' => $tool, 'params' => $params],
        ]);
        
        // ... existing code ...
    }
}
```

## Files to Modify
- `app/Http/Controllers/AgentController.php`
- `app/Services/AgentLifecycleService.php`
- `app/Services/AgentToolExecutor.php`

## Definition of Done
- [ ] All `Log::` facade calls replaced with `LogService`
- [ ] Logs include related entity information
- [ ] Tests pass