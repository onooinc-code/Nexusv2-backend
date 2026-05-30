# Task 11: Add Event Listeners for Automatic Logging

## Objective
Create event listeners that automatically log significant system events.

## Current State
- `Listener.php` uses `Log::` facade
- `ProcessContactCreated.php` uses `Log::` facade
- No automatic logging for JobFailed, WorkflowStepCompleted, etc.

## Implementation Steps

### 1. Create JobFailed Listener
```php
// app/Listeners/LogJobFailed.php
namespace App\Listeners;

use App\Services\LogService;
use Illuminate\Queue\Events\JobFailed;

class LogJobFailed
{
    public function __construct(protected LogService $logService) {}
    
    public function handle(JobFailed $event): void
    {
        $this->logService->error('Job failed', [
            'channel' => 'queue',
            'type' => 'job_failed',
            'context' => [
                'job' => $event->job->getName(),
                'exception' => $event->exception->getMessage(),
            ],
        ]);
    }
}
```

### 2. Create WorkflowStepCompleted Listener
```php
// app/Listeners/LogWorkflowStepCompleted.php
namespace App\Listeners;

use App\Events\WorkflowStepCompleted;
use App\Services\LogService;

class LogWorkflowStepCompleted
{
    public function __construct(protected LogService $logService) {}
    
    public function handle(WorkflowStepCompleted $event): void
    {
        $this->logService->info('Workflow step completed', [
            'channel' => 'workflow',
            'type' => 'step',
            'related_id' => $event->workflow->id,
            'related_type' => 'App\Models\Workflow',
            'context' => ['step' => $event->step->title],
        ]);
    }
}
```

### 3. Update Existing Listeners
Update `Listener.php` and `ProcessContactCreated.php` to use LogService.

### 4. Register Listeners in EventServiceProvider
```php
// app/Providers/EventServiceProvider.php
protected $listen = [
    JobFailed::class => [
        LogJobFailed::class,
    ],
    WorkflowStepCompleted::class => [
        LogWorkflowStepCompleted::class,
    ],
];
```

## Files to Create/Modify
- `app/Listeners/LogJobFailed.php` (new)
- `app/Listeners/LogWorkflowStepCompleted.php` (new)
- `app/Listeners/Listener.php` (modify)
- `app/Listeners/ProcessContactCreated.php` (modify)
- `app/Providers/EventServiceProvider.php` (modify)

## Definition of Done
- [ ] Event listeners created for automatic logging
- [ ] Existing listeners updated to use LogService
- [ ] Listeners registered in EventServiceProvider
- [ ] Tests pass