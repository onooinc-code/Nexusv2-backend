# Task 10: Integrate LogService into Settings Hub

## Objective
Add LogService integration to Settings Hub for logging setting changes.

## Current State
- `SettingController` has no logging

## Implementation Steps

### 1. Update SettingController (`app/Http/Controllers/SettingController.php`)
```php
use App\Services\LogService;

class SettingController extends Controller
{
    protected LogService $logService;
    
    public function __construct(LogService $logService)
    {
        $this->logService = $logService;
    }
    
    public function update(Request $request, $key): JsonResponse
    {
        $oldValue = $setting->value;
        $setting->update($validated);
        
        $this->logService->info('Setting updated', [
            'channel' => 'system',
            'type' => 'setting',
            'related_id' => $setting->id,
            'related_type' => 'App\Models\Setting',
            'user_id' => $request->user()->id,
            'context' => [
                'key' => $key,
                'old_value' => $oldValue,
                'new_value' => $setting->value,
            ],
        ]);
        
        return response()->json([...]);
    }
    
    public function bulkUpdate(Request $request): JsonResponse
    {
        $this->logService->info('Settings bulk updated', [
            'channel' => 'system',
            'type' => 'setting',
            'user_id' => $request->user()->id,
            'context' => ['keys' => array_keys($validated)],
        ]);
        
        // ... existing code ...
    }
}
```

## Files to Modify
- `app/Http/Controllers/SettingController.php`

## Definition of Done
- [ ] LogService integrated into SettingController
- [ ] Logs include related entity information
- [ ] Tests pass