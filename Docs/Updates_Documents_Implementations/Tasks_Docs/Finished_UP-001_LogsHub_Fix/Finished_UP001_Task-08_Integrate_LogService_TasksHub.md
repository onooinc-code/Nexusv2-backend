# Task 8: Integrate LogService into TasksHub

## Objective
Add LogService integration to TasksHub for logging task operations.

## Current State
- `TaskController` has no logging
- `TaskQueueService` has no logging
- `TaskRoutingService` has no logging

## Implementation Steps

### 1. Update TaskController (`app/Http/Controllers/TaskController.php`)
```php
use App\Services\LogService;

class TaskController extends Controller
{
    protected LogService $logService;
    
    public function __construct(LogService $logService)
    {
        $this->logService = $logService;
    }
    
    public function store(Request $request): JsonResponse
    {
        $task = AgentTask::create($validated);
        
        $this->logService->info('Task created', [
            'channel' => 'task',
            'type' => 'create',
            'related_id' => $task->id,
            'related_type' => 'App\Models\AgentTask',
            'user_id' => $request->user()->id,
        ]);
        
        return response()->json([...]);
    }
    
    public function cancel($id): JsonResponse
    {
        $this->logService->warning('Task cancelled', [
            'channel' => 'task',
            'type' => 'cancel',
            'related_id' => $task->id,
            'related_type' => 'App\Models\AgentTask',
            'user_id' => $request->user()->id,
        ]);
        
        // ... existing code ...
    }
}
```

### 2. Update TaskQueueService (`app/Services/TaskQueueService.php`)
```php
use App\Services\LogService;

class TaskQueueService
{
    protected LogService $logService;
    
    public function __construct(LogService $logService)
    {
        $this->logService = $logService;
    }
    
    public function enqueue(AgentTask $task): void
    {
        $this->logService->info('Task enqueued', [
            'channel' => 'task',
            'type' => 'queue',
            'related_id' => $task->id,
            'related_type' => 'App\Models\AgentTask',
        ]);
        
        // ... existing code ...
    }
}
```

## Files to Modify
- `app/Http/Controllers/TaskController.php`
- `app/Services/TaskQueueService.php`

## Definition of Done
- [ ] LogService integrated into TaskController
- [ ] LogService integrated into TaskQueueService
- [ ] Logs include related entity information
- [ ] Tests pass