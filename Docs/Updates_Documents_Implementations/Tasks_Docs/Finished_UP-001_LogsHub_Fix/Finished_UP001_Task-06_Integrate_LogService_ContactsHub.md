# Task 6: Integrate LogService into ContactsHub

## Objective
Add LogService integration to ContactsHub for logging contact operations.

## Current State
- `ContactController` has no logging
- `ContactHubService` has no logging

## Implementation Steps

### 1. Update ContactController (`app/Http/Controllers/ContactController.php`)
```php
use App\Services\LogService;

class ContactController extends Controller
{
    protected LogService $logService;
    
    public function __construct(LogService $logService)
    {
        $this->logService = $logService;
    }
    
    public function store(Request $request): JsonResponse
    {
        $contact = Contact::create($validated);
        
        $this->logService->info('Contact created', [
            'channel' => 'contact',
            'type' => 'create',
            'related_id' => $contact->id,
            'related_type' => 'App\Models\Contact',
            'user_id' => $request->user()->id,
        ]);
        
        return response()->json([...]);
    }
    
    public function update(Request $request, $id): JsonResponse
    {
        $contact->update($validated);
        
        $this->logService->info('Contact updated', [
            'channel' => 'contact',
            'type' => 'update',
            'related_id' => $contact->id,
            'related_type' => 'App\Models\Contact',
            'user_id' => $request->user()->id,
        ]);
        
        return response()->json([...]);
    }
    
    public function destroy($id): JsonResponse
    {
        $this->logService->info('Contact deleted', [
            'channel' => 'contact',
            'type' => 'delete',
            'related_id' => $contact->id,
            'related_type' => 'App\Models\Contact',
            'user_id' => $request->user()->id,
        ]);
        
        $contact->delete();
        
        return response()->json([...]);
    }
}
```

### 2. Update ContactHubService (`app/Services/ContactHubService.php`)
```php
use App\Services\LogService;

class ContactHubService
{
    protected LogService $logService;
    
    public function __construct(LogService $logService)
    {
        $this->logService = $logService;
    }
    
    public function import(array $data): array
    {
        $this->logService->info('Contact import started', [
            'channel' => 'contact',
            'type' => 'import',
        ]);
        
        // ... import logic ...
        
        $this->logService->info('Contact import completed', [
            'channel' => 'contact',
            'type' => 'import',
            'context' => ['count' => $imported],
        ]);
        
        return $result;
    }
}
```

## Files to Modify
- `app/Http/Controllers/ContactController.php`
- `app/Services/ContactHubService.php`

## Definition of Done
- [ ] LogService integrated into ContactController
- [ ] LogService integrated into ContactHubService
- [ ] Logs include related entity information
- [ ] Tests pass