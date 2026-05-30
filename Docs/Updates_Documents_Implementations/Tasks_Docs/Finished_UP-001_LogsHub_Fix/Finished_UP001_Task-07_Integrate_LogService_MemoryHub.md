# Task 7: Integrate LogService into MemoryHub

## Objective
Replace Laravel's `Log::` facade with `LogService` in MemoryHub for consistent logging.

## Current State
- `MemoryController` uses `Log::` facade
- `SyncMemoryJob` uses `Log::` facade
- Memory services use `Log::` facade

## Implementation Steps

### 1. Update MemoryController (`app/Http/Controllers/MemoryController.php`)
```php
use App\Services\LogService;

class MemoryController extends Controller
{
    protected LogService $logService;
    
    public function __construct(LogService $logService)
    {
        $this->logService = $logService;
    }
    
    public function store(Request $request): JsonResponse
    {
        $memory = Memory::create($validated);
        
        $this->logService->info('Memory created', [
            'channel' => 'memory',
            'type' => 'create',
            'related_id' => $memory->id,
            'related_type' => 'App\Models\Memory',
            'user_id' => $request->user()->id,
        ]);
        
        return response()->json([...]);
    }
    
    public function indexMemory($id, Request $request): JsonResponse
    {
        $this->logService->info('Memory indexed', [
            'channel' => 'memory',
            'type' => 'index',
            'related_id' => $memory->id,
            'related_type' => 'App\Models\Memory',
            'user_id' => $request->user()->id,
        ]);
        
        // ... existing code ...
    }
}
```

### 2. Update SyncMemoryJob (`app/Jobs/SyncMemoryJob.php`)
```php
use App\Services\LogService;

class SyncMemoryJob implements ShouldQueue
{
    protected LogService $logService;
    
    public function __construct(
        protected array $data,
        LogService $logService
    ) {
        $this->logService = $logService;
    }
    
    public function handle(): void
    {
        $this->logService->info('Memory sync started', [
            'channel' => 'memory',
            'type' => 'sync',
        ]);
        
        // ... existing code ...
        
        $this->logService->info('Memory sync completed', [
            'channel' => 'memory',
            'type' => 'sync',
            'context' => ['count' => $synced],
        ]);
    }
}
```

## Files to Modify
- `app/Http/Controllers/MemoryController.php`
- `app/Jobs/SyncMemoryJob.php`

## Definition of Done
- [ ] All `Log::` facade calls replaced with `LogService`
- [ ] Logs include related entity information
- [ ] Tests pass