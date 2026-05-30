# 04 - Developer Quick Start

## Overview
This document provides new developers with a quick guide to set up Nexus locally, understand the codebase structure, run tests, and start contributing.

---

## 1. Prerequisites

**Required**:
- PHP 8.1+ (`php -v`)
- MySQL 8.0+ (`mysql --version`)
- Redis 7.0+ (`redis-cli --version`)
- Node.js 18+ (for Vue frontend)
- Git (`git --version`)
- Composer (`composer --version`)

**Optional**:
- Docker & Docker Compose (for containerized setup)
- VS Code with PHP Intelephense extension
- Postman (for API testing)

---

## 2. Local Setup

### 2.1 Clone Repository

```bash
git clone https://github.com/onoorepo/NexusSoul.git nexus
cd nexus
git checkout main
```

### 2.2 Install PHP Dependencies

```bash
composer install

# If you encounter issues:
composer install --no-interaction --prefer-dist --optimize-autoloader
```

### 2.3 Environment Configuration

```bash
# Copy example env file
cp .env.example .env

# Generate app key
php artisan key:generate

# Edit .env with local settings
# Key items to set:
# - APP_URL=http://localhost:8000
# - DB_HOST=127.0.0.1
# - DB_DATABASE=nexus_dev
# - DB_USERNAME=root
# - DB_PASSWORD=
# - REDIS_HOST=127.0.0.1
# - GEMINI_API_KEY=your_key_here
# - WAHA_API_URL=http://localhost:3000
```

### 2.4 Database Setup

```bash
# Create database
mysql -u root -e "CREATE DATABASE nexus_dev CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Run migrations
php artisan migrate

# Seed sample data
php artisan db:seed
```

### 2.5 Start Development Server

```bash
# Terminal 1: Laravel development server
php artisan serve --port=8000

# Terminal 2: Queue worker (for async jobs)
php artisan queue:work

# Terminal 3: Schedule runner (if needed)
php artisan schedule:work

# Terminal 4: Vite (frontend dev server)
npm run dev
```

Verify API is running:
```bash
curl http://localhost:8000/api/health
```

---

## 3. Project Structure

```
nexus/
├── app/
│   ├── Actions/           # Command objects
│   ├── Ai/                # AI service classes
│   ├── Console/           # Artisan commands
│   ├── Hubs/              # Hub orchestrators
│   ├── Http/
│   │   ├── Controllers/   # API controllers
│   │   ├── Middleware/    # HTTP middleware
│   │   └── Requests/      # Form request validation
│   ├── Jobs/              # Async jobs
│   ├── Models/            # Eloquent models
│   ├── Services/          # Business logic
│   └── Tools/             # Reusable utilities
├── config/                # Configuration files
├── database/
│   ├── migrations/        # Database schema
│   ├── seeders/           # Sample data
│   └── factories/         # Model factories for testing
├── routes/
│   ├── api.php            # API routes
│   └── web.php            # Web routes
├── tests/                 # Test suite
│   ├── Feature/           # Integration tests
│   └── Unit/              # Unit tests
├── resources/
│   ├── views/             # Blade templates
│   └── js/                # Vue components
├── storage/               # Uploaded files, logs
├── vendor/                # Composer packages
├── NexusDocumentations_Lastv/  # Documentation
└── composer.json, package.json, etc.
```

---

## 4. Understanding Hub Architecture

### Hub Pattern Overview

Each hub is a service responsible for a specific domain:

```
┌─────────────────────────────────────────┐
│ API Request arrives at Route            │
└────────────┬────────────────────────────┘
             │
             ↓
┌─────────────────────────────────────────┐
│ HubController (routing & validation)    │
└────────────┬────────────────────────────┘
             │
             ↓
┌─────────────────────────────────────────┐
│ Hub Service (business logic)            │
│ - Router (message routing)              │
│ - Engine (processing)                   │
│ - Pipeline (workflow execution)         │
└────────────┬────────────────────────────┘
             │
             ↓
┌─────────────────────────────────────────┐
│ External Systems                        │
│ - Database                              │
│ - Cache                                 │
│ - AI Models                             │
│ - External APIs                         │
└─────────────────────────────────────────┘
```

### Key Hubs

**ContactsHub**:
- Location: `app/Hubs/ContactsHub.php`
- Manages contact profiles and relationships
- Example: `ContactsHub::find($id)`, `ContactsHub::create($data)`

**MemoryHub**:
- Location: `app/Hubs/MemoryHub.php`
- Stores and retrieves memories, beliefs, preferences
- Example: `MemoryHub::extract($contact, $message)`, `MemoryHub::search($query)`

**AiModelsHub**:
- Location: `app/Hubs/AiModelsHub.php`
- Routes requests to Gemini, OpenAI, or other providers
- Example: `AiModelsHub::generate($prompt)`, `AiModelsHub::embed($text)`

**WorkflowsAndTasksHub**:
- Location: `app/Hubs/WorkflowsAndTasksHub.php`
- Orchestrates multi-step workflows
- Example: `WorkflowsAndTasksHub::execute($workflow_id)`

---

## 5. Running Tests

### 5.1 Unit Tests

```bash
# Run all tests
php artisan test

# Run specific test file
php artisan test tests/Unit/Services/MemoryExtractionServiceTest.php

# Run tests with coverage
php artisan test --coverage

# Run tests with verbose output
php artisan test --verbose
```

### 5.2 Feature Tests (Integration)

```bash
# Test API endpoints
php artisan test tests/Feature/Api/ContactsControllerTest.php

# Refresh database before each test
php artisan test --refresh-database
```

### 5.3 Test Coverage Goals

- Unit tests: 85%+ coverage
- Critical paths: 100% coverage
- Integration tests: Key workflows

---

## 6. Common Development Tasks

### 6.1 Create a New Hub Service

```bash
# Generate controller
php artisan make:controller HubController

# Generate service
php artisan make:service YourHubService

# Generate migration (if needed DB changes)
php artisan make:migration create_your_table

# Follow the hub pattern:
# 1. Create controller (app/Http/Controllers/YourController.php)
# 2. Create hub service (app/Services/YourHubService.php)
# 3. Create router/engine if needed
# 4. Add routes (routes/api.php)
# 5. Add tests (tests/Feature/YourControllerTest.php)
```

### 6.2 Add a Database Migration

```bash
php artisan make:migration add_field_to_contacts_table --table=contacts

# Edit migration file, then run:
php artisan migrate

# Rollback if needed:
php artisan migrate:rollback
```

### 6.3 Create a Background Job

```bash
php artisan make:job ProcessMemoryExtractionJob

# Implement handle() method
# Dispatch from controller or service:
ProcessMemoryExtractionJob::dispatch($contact, $message);

# Run queue worker to process:
php artisan queue:work
```

### 6.4 Add API Endpoint

1. **Create route** (`routes/api.php`):
```php
Route::get('/contacts/{id}', [ContactsController::class, 'show']);
```

2. **Create controller method**:
```php
public function show(Request $request, $id)
{
    $contact = ContactsHub::find($id);
    return response()->json($contact);
}
```

3. **Add validation** if needed:
```php
$request->validate([
    'name' => 'required|string',
    'email' => 'required|email',
]);
```

4. **Test** (`tests/Feature/Api/ContactsControllerTest.php`):
```php
$this->getJson('/api/contacts/1')
    ->assertOk()
    ->assertJsonStructure(['id', 'name', 'email']);
```

---

## 7. Debugging

### 7.1 Laravel Debugbar

```bash
# Already installed, enabled in dev environment
# Shows in bottom of page during dev

# Or programmatically:
\Debugbar::info('Debug message');
\Debugbar::error('Error occurred');
```

### 7.2 Tinker REPL

```bash
# Open interactive shell
php artisan tinker

# Try some commands
>>> $contact = Contact::first();
>>> $contact->name;
>>> $contact->memories()->count();
>>> // Make database queries, test services, etc.
```

### 7.3 Logging

```php
// In your code:
Log::info('Message', ['context' => $data]);
Log::error('Error occurred', ['exception' => $e]);

// View logs:
tail -f storage/logs/laravel.log

// Or in tinker:
>>> tail -50 storage/logs/laravel.log
```

### 7.4 Xdebug Setup

```bash
# In .env:
XDEBUG_MODE=debug
XDEBUG_CONFIG=client_host=host.docker.internal

# In VS Code settings.json:
{
    "launch": {
        "version": "0.2.0",
        "configurations": [
            {
                "name": "Listen for XDebug",
                "type": "php",
                "port": 9003
            }
        ]
    }
}
```

---

## 8. Code Style & Standards

### 8.1 PHP Standards

- Follow PSR-12 coding standard
- Use type hints (PHP 8.1+)
- Document with PHPDoc

```php
/**
 * Extract memory from contact message
 * 
 * @param Contact $contact
 * @param string $message
 * @return array Memory fragments extracted
 */
public function extract(Contact $contact, string $message): array
{
    // Implementation
}
```

### 8.2 Formatting

```bash
# Format code with Laravel Pint
./vendor/bin/pint

# Check without fixing
./vendor/bin/pint --test
```

### 8.3 Static Analysis

```bash
# Run PHPStan
./vendor/bin/phpstan analyse app/

# Run Larastan (Laravel-specific)
./vendor/bin/phpstan analyse --configuration=phpstan.neon
```

---

## 9. Git Workflow

### 9.1 Branch Strategy

```bash
# Create feature branch
git checkout -b feature/contact-intelligence

# Make changes, commit regularly
git add .
git commit -m "feat: implement contact intelligence feature"

# Push and create pull request
git push origin feature/contact-intelligence

# After PR approved, merge
git checkout main
git pull
git merge feature/contact-intelligence
git push origin main
```

### 9.2 Commit Message Format

```
type(scope): subject

body

footer
```

Types: `feat`, `fix`, `docs`, `style`, `refactor`, `test`, `chore`

Example:
```
feat(memory): add belief extraction from messages

Implement belief extraction using Gemini 2.0
- Extract beliefs from natural language
- Store with confidence score
- Link to contact profile

Closes #123
```

---

## 10. Common Gotchas

| Issue | Solution |
|-------|----------|
| "SQLSTATE[HY000]: General error: 1030" | MySQL charset issue, run `ALTER DATABASE nexus_dev CHARACTER SET utf8mb4;` |
| Queue not processing | Run `php artisan queue:work`, check if worker is running |
| Cache stale | Run `php artisan cache:clear` and `redis-cli FLUSHALL` |
| Tests fail randomly | Use `--refresh-database` flag, check for timing issues |
| Gemini API 401 error | Verify GEMINI_API_KEY in .env is correct |
| WAHA connection refused | Start WAHA service or update WAHA_API_URL |
| "Class not found" error | Run `composer dump-autoload` |

---

## 11. Useful Commands Cheat Sheet

```bash
# Server management
php artisan serve                    # Start dev server
php artisan queue:work               # Start queue worker
php artisan schedule:work            # Start scheduler
php artisan horizon                  # Monitor queues (if installed)

# Database
php artisan migrate                  # Run migrations
php artisan migrate:rollback         # Undo migrations
php artisan db:seed                  # Seed sample data
php artisan tinker                   # Interactive shell

# Testing
php artisan test                     # Run all tests
php artisan test --coverage          # With coverage
php artisan test --profile           # Show slowest tests

# Maintenance
php artisan cache:clear              # Clear cache
php artisan config:cache             # Cache configuration
php artisan route:cache              # Cache routes
php artisan optimize                 # General optimization

# Generation
php artisan make:model Contact       # Generate model
php artisan make:controller API\ContactController   # Generate controller
php artisan make:migration create_table             # Generate migration
php artisan make:job ProcessJob                     # Generate job
```

---

## 12. Getting Help

**Internal Resources**:
- Documentation: `/NexusDocumentations_Lastv/`
- Code comments: Read docblocks and inline comments
- Tests: `tests/` folder shows usage examples

**External Resources**:
- Laravel docs: https://laravel.com/docs
- PHP docs: https://www.php.net/docs.php
- Vue 3 docs: https://vuejs.org/guide/introduction.html

**Team**:
- Ask in Slack #dev-help
- Code review feedback in PR comments
- Weekly dev sync meeting

---

**Document Status**: COMPLETE - Quick start guide provided  
**Last Updated**: 2025-05-16  
**Recommended Next Reading**: 02-ARCHITECTURE/01-SYSTEM_ARCHITECTURE.md
