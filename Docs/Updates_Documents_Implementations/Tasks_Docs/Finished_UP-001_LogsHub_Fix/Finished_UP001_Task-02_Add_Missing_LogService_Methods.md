# Task 2: Add Missing LogService Methods

## Objective
Add the missing methods to LogService that are required by the specification.

## Missing Methods
- `getStats()` - Return log statistics (total, by_level, by_category, today, errors_today)
- `getLevels()` - Return available log levels
- `getCategories()` - Return available log categories
- `getErrors()` - Return error-level logs
- `delete($id)` - Delete specific log
- `clearOldLogs($days)` - Delete logs older than X days
- `getById($id)` - Get specific log by ID

## Implementation Steps

### 1. Add getStats() Method
```php
public function getStats(): array
{
    return [
        'total' => Log::count(),
        'by_level' => Log::selectRaw('level, count(*) as count')
            ->groupBy('level')
            ->pluck('count', 'level')
            ->toArray(),
        'by_category' => Log::selectRaw('channel, count(*) as count')
            ->groupBy('channel')
            ->pluck('count', 'channel')
            ->toArray(),
        'today' => Log::whereDate('created_at', today())->count(),
        'errors_today' => Log::whereDate('created_at', today())
            ->whereIn('level', [self::LEVEL_ERROR, self::LEVEL_CRITICAL, self::LEVEL_ALERT, self::LEVEL_EMERGENCY])
            ->count(),
    ];
}
```

### 2. Add getLevels() Method
```php
public function getLevels(): array
{
    return [
        ['value' => self::LEVEL_DEBUG, 'label' => 'Debug'],
        ['value' => self::LEVEL_INFO, 'label' => 'Info'],
        ['value' => self::LEVEL_NOTICE, 'label' => 'Notice'],
        ['value' => self::LEVEL_WARNING, 'label' => 'Warning'],
        ['value' => self::LEVEL_ERROR, 'label' => 'Error'],
        ['value' => self::LEVEL_CRITICAL, 'label' => 'Critical'],
        ['value' => self::LEVEL_ALERT, 'label' => 'Alert'],
        ['value' => self::LEVEL_EMERGENCY, 'label' => 'Emergency'],
    ];
}
```

### 3. Add getCategories() Method
```php
public function getCategories(): array
{
    return [
        ['value' => 'auth', 'label' => 'Authentication'],
        ['value' => 'security', 'label' => 'Security'],
        ['value' => 'api', 'label' => 'API'],
        ['value' => 'workflow', 'label' => 'Workflow'],
        ['value' => 'agent', 'label' => 'Agent'],
        ['value' => 'ai', 'label' => 'AI'],
        ['value' => 'system', 'label' => 'System'],
        ['value' => 'database', 'label' => 'Database'],
        ['value' => 'cache', 'label' => 'Cache'],
        ['value' => 'queue', 'label' => 'Queue'],
    ];
}
```

### 4. Add getErrors() Method
```php
public function getErrors(int $limit = 100)
{
    return Log::whereIn('level', [
        self::LEVEL_ERROR,
        self::LEVEL_CRITICAL,
        self::LEVEL_ALERT,
        self::LEVEL_EMERGENCY,
    ])->latest()->limit($limit)->get();
}
```

### 5. Add getById() Method
```php
public function getById(int $id): ?Log
{
    return Log::find($id);
}
```

### 6. Add delete() Method
```php
public function delete(int $id): bool
{
    $log = Log::find($id);
    if (!$log) {
        return false;
    }
    return $log->delete();
}
```

### 7. Add clearOldLogs() Method
```php
public function clearOldLogs(int $days): int
{
    return Log::where('created_at', '<', now()->subDays($days))->delete();
}
```

## Files to Modify
- `app/Services/LogService.php`

## Definition of Done
- [ ] All missing methods are implemented
- [ ] Methods return correct data types
- [ ] Tests pass