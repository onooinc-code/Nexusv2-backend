# Task 3: Add Polymorphic Relations to Log Model and LogService

## Objective
Add polymorphic relations to the Log model to link logs to any entity (Contact, Agent, Workflow, etc.)

## Current State
- `related_id` and `related_type` columns exist in migration
- Not used in Log model
- Not used in LogService

## Implementation Steps

### 1. Update Log Model (`app/Models/Log.php`)
Add the `related()` relationship method:
```php
/**
 * Get the related entity.
 */
public function related(): MorphTo
{
    return $this->morphTo();
}
```

### 2. Update LogService (`app/Services/LogService.php`)
Update the `log()` method to accept and set related entity:
```php
public function log(string $level, string $message, array $context = []): Log
{
    // ... existing code ...
    
    $log = Log::create([
        'level' => $level,
        'channel' => $context['channel'] ?? 'app',
        'message' => $message,
        'context' => Arr::except($context, ['channel', 'related_id', 'related_type']),
        'type' => $context['type'] ?? 'application',
        'user_id' => $context['user_id'] ?? null,
        'related_id' => $context['related_id'] ?? null,
        'related_type' => $context['related_type'] ?? null,
    ]);
    
    return $log;
}
```

### 3. Add Helper Method for Logging Related Entities
```php
public function logRelated(string $level, string $message, Model $model, array $context = []): Log
{
    $context['related_id'] = $model->getKey();
    $context['related_type'] = $model->getMorphClass();
    return $this->log($level, $message, $context);
}
```

## Files to Modify
- `app/Models/Log.php`
- `app/Services/LogService.php`

## Definition of Done
- [ ] `related()` relationship is defined in Log model
- [ ] LogService accepts and sets `related_id` and `related_type`
- [ ] Helper method for logging related entities is available
- [ ] Tests pass