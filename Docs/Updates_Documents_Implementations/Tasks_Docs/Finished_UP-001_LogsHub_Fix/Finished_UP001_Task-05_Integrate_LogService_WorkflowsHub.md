# Task 5: Integrate LogService into WorkflowsHub

## Objective
Replace Laravel's `Log::` facade with `LogService` in WorkflowsHub for consistent logging.

## Current State
- `WorkflowController` uses `Log::` facade
- `WorkflowExecutor` uses `Log::` facade
- `WorkflowValidationService` uses `Log::` facade

## Implementation Steps

### 1. Update WorkflowController (`app/Http/Controllers/WorkflowController.php`)
```php
use App\Services\LogService;

class WorkflowController extends Controller
{
    protected LogService $logService;
    
    public function __construct(LogService $logService)
    {
        $this->logService = $logService;
    }
    
    public function store(Request $request): JsonResponse
    {
        $workflow = Workflow::create($validated);
        
        $this->logService->info('Workflow created', [
            'channel' => 'workflow',
            'type' => 'create',
            'related_id' => $workflow->id,
            'related_type' => 'App\Models\Workflow',
            'user_id' => $request->user()->id,
        ]);
        
        return response()->json([...]);
    }
    
    public function execute($id, Request $request): JsonResponse
    {
        $this->logService->info('Workflow execution started', [
            'channel' => 'workflow',
            'type' => 'execute',
            'related_id' => $workflow->id,
            'related_type' => 'App\Models\Workflow',
            'user_id' => $request->user()->id,
        ]);
        
        // ... existing code ...
    }
}
```

### 2. Update WorkflowExecutor (`app/Services/WorkflowExecutor.php`)
```php
use App\Services\LogService;

class WorkflowExecutor
{
    protected LogService $logService;
    
    public function __construct(LogService $logService)
    {
        $this->logService = $logService;
    }
    
    public function execute(Workflow $workflow, array $input): array
    {
        $this->logService->info('Workflow step execution started', [
            'channel' => 'workflow',
            'type' => 'step',
            'related_id' => $workflow->id,
            'related_type' => 'App\Models\Workflow',
        ]);
        
        // ... for each step ...
        $this->logService->info('Workflow step completed', [
            'channel' => 'workflow',
            'type' => 'step',
            'related_id' => $workflow->id,
            'related_type' => 'App\Models\Workflow',
            'context' => ['step' => $step->title],
        ]);
        
        return $result;
    }
}
```

## Files to Modify
- `app/Http/Controllers/WorkflowController.php`
- `app/Services/WorkflowExecutor.php`

## Definition of Done
- [ ] All `Log::` facade calls replaced with `LogService`
- [ ] Logs include related entity information
- [ ] Tests pass