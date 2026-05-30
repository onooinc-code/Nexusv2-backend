# UP-001: LogsHub Implementation Fix

## Overview
Fix the LogsHub implementation to address schema mismatches, missing methods, and universal logging integration.

## Current State Analysis
The audit report is partially outdated - most components exist but have critical issues:

### ✅ Already Implemented
- `app/Models/Log.php` - Log model with PSR-3 levels and channels
- `app/Models/SystemLog.php` - SystemLog model extending Log
- `app/Services/LogService.php` - Logging service with all required methods
- `app/Services/AlertService.php` - Alert management service
- `app/Http/Controllers/LogController.php` - Log controller with CRUD operations
- `resources/js/Pages/LogsView.vue` - Logs dashboard page
- `resources/js/Components/LogStream.vue` - Real-time log stream component
- `routes/api.php` - API routes for logs (lines 174-191)
- `database/migrations/2026_05_17_080000_create_phase_02_database_models.php` - logs table migration

### ❌ Issues to Fix

#### 1. Schema Mismatch (FIXED ✅)
- ~~`Log` model uses `category` and `source` columns~~ → Now uses `channel` and `type`
- ~~`LogService::log()` tries to set `ip_address` and `user_agent`~~ → Removed non-existent columns
- ~~`related_id` and `related_type` exist in migration but not used~~ → Now supported with morphTo

#### 2. Missing LogService Methods (FIXED ✅)
- ~~`getStats()`~~ → Implemented
- ~~`getLevels()`~~ → Implemented
- ~~`getCategories()`~~ → Now `getChannels()` implemented
- ~~`getErrors()`~~ → Implemented
- ~~`delete($id)`~~ → Implemented
- ~~`clearOldLogs($days)`~~ → Implemented
- ~~`getById($id)`~~ → Implemented

#### 3. Missing Polymorphic Relations (FIXED ✅)
- ~~`related_id` and `related_type` columns exist but not used~~ → Now used
- ~~No `related()` relationship in Log model~~ → Added morphTo relationship
- ~~LogService doesn't accept related entity parameters~~ → Now accepts via context

#### 4. Universal Logging Not Integrated (FIXED ✅)
- ~~AgentsHub uses `Log::` facade instead of `LogService`~~ → Now uses LogService
- ~~WorkflowsHub uses `Log::` facade instead of `LogService`~~ → Now uses LogService
- ~~ContactsHub has no logging~~ → Now uses LogService
- ~~MemoryHub uses `Log::` facade instead of `LogService`~~ → Now uses LogService
- ~~TasksHub has no logging~~ → Now uses LogService
- ~~AI Models Hub has no logging~~ → Now uses LogService
- ~~Settings Hub has no logging~~ → Now uses LogService

## Execution Checklist

- [x] **Task 1**: Fix database schema mismatch - update Log model to match migration
- [x] **Task 2**: Add missing LogService methods (getStats, getLevels, getCategories, getErrors, delete, clearOldLogs, getById)
- [x] **Task 3**: Add polymorphic relations to Log model and LogService
- [x] **Task 4**: Integrate LogService into AgentsHub
- [x] **Task 5**: Integrate LogService into WorkflowsHub
- [x] **Task 6**: Integrate LogService into ContactsHub
- [x] **Task 7**: Integrate LogService into MemoryHub
- [x] **Task 8**: Integrate LogService into TasksHub
- [x] **Task 9**: Integrate LogService into AI Models Hub
- [x] **Task 10**: Integrate LogService into Settings Hub
- [x] **Task 11**: Add event listeners for automatic logging
- [x] **Task 12**: Write tests for LogService and LogController

## Technical Specifications

### Database Schema (from migration)
```php
Schema::create('logs', function (Blueprint $table) {
    $table->id();
    $table->string('level');
    $table->string('channel')->nullable();
    $table->text('message');
    $table->json('context')->nullable();
    $table->string('type')->default('application');
    $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
    $table->unsignedBigInteger('related_id')->nullable();
    $table->string('related_type')->nullable();
    $table->timestamps();
});
```

### LogService API
```php
// Core logging methods
log($level, $message, $context = [])
debug($message, $context = [])
info($message, $context = [])
notice($message, $context = [])
warning($message, $context = [])
error($message, $context = [])
critical($message, $context = [])
alert($message, $context = [])
emergency($message, $context = [])

// Query methods
getStats()
getLevels()
getChannels()
getErrors()
getById($id)
recent($limit = 100)
byLevel($levels, $limit = 100)
byChannel($channels, $limit = 100)

// Management methods
delete($id)
clearOldLogs($days)
logRelated($level, $message, $model, $context = [])
```

## Dependencies
- Laravel 11.x
- PHP 8.2+
- Vue 3 (for frontend components)
- Redis (for AlertService caching)

## Estimated Effort
- Total: ~12-16 hours
- Each task: 1-2 hours