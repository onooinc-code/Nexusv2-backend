# Task 1: Fix Database Schema Mismatch

## Objective
Update the Log model to match the database migration schema and fix LogService to use correct column names.

## Current Issues
1. `Log` model uses `category` and `source` but migration has `channel` and `type`
2. `LogService::log()` tries to set `ip_address` and `user_agent` which don't exist
3. `related_id` and `related_type` exist in migration but not used

## Implementation Steps

### 1. Update Log Model (`app/Models/Log.php`)
- Change `category` to `channel` in fillable and casts
- Add `type` column support
- Add `related_id` and `related_type` to fillable
- Add `related()` morphTo relationship
- Update scopes to use `channel` instead of `category`

### 2. Update LogService (`app/Services/LogService.php`)
- Update `log()` method to use `channel` instead of `category`
- Remove `ip_address` and `user_agent` from log creation
- Add `related_id` and `related_type` parameters
- Add `type` parameter support

### 3. Create Migration for Additional Columns (if needed)
- Add `source` column if we want to keep it
- Add `ip_address` and `user_agent` if needed for audit

## Files to Modify
- `app/Models/Log.php`
- `app/Services/LogService.php`

## Definition of Done
- [ ] Log model matches migration schema
- [ ] LogService uses correct column names
- [ ] Polymorphic relations are supported
- [ ] Tests pass

## Technical Details

### Log Model Changes
```php
// Before
protected $fillable = [
    'level', 'category', 'message', 'context', 'source', 'user_id', 'ip_address', 'user_agent'
];

// After
protected $fillable = [
    'level', 'channel', 'message', 'context', 'type', 'user_id', 'related_id', 'related_type'
];
```

### LogService Changes
```php
// Before
Log::create([
    'level' => $level,
    'category' => $context['category'] ?? Log::CATEGORY_SYSTEM,
    'message' => $message,
    'context' => Arr::except($context, ['category']),
    'source' => $context['source'] ?? 'app',
    'user_id' => $context['user_id'] ?? null,
    'ip_address' => $context['ip_address'] ?? request()->ip(),
    'user_agent' => $context['user_agent'] ?? request()->userAgent(),
]);

// After
Log::create([
    'level' => $level,
    'channel' => $context['channel'] ?? 'app',
    'message' => $message,
    'context' => Arr::except($context, ['channel', 'related_id', 'related_type']),
    'type' => $context['type'] ?? 'application',
    'user_id' => $context['user_id'] ?? null,
    'related_id' => $context['related_id'] ?? null,
    'related_type' => $context['related_type'] ?? null,
]);