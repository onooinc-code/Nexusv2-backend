# 🎯 TASK: UP-003 - Task 1: Horizon Configuration & Base Job Infrastructure
- **Status:** � IMPLEMENTED - Ready for Testing
- **Dependencies:** None

## 1. Objective
Set up Laravel Horizon for queue supervision and create the base abstract job class with resilience patterns (idempotency, model missing handling, rate limit safe release). Configure queue workers for critical, llm-inference, default, and batch queues with appropriate settings per queue type.

## 2. Files to Create/Modify
- `config/horizon.php`: Configure supervisors for multiple queues with queue-specific settings
- `app/Jobs/BaseJob.php`: Create abstract base class with common resilience traits and patterns
- `.env`: Add/update Horizon configuration environment variables
- `app/Providers/EventServiceProvider.php`: Register JobFailed listener (for Task 8)

## 3. Implementation Steps
1. **Horizon Configuration**
   - Verify `config/horizon.php` exists (should already be present)
   - Configure environment variables: QUEUE_DRIVER=redis, REDIS_HOST, REDIS_PASSWORD
   - Define supervisors for queues: `critical`, `llm-inference`, `default`, `batch`
   - Settings per supervisor:
     - `critical`: 3 processes, maxTime 60s, timeout 120s, sleep 3s
     - `llm-inference`: 2 processes, maxTime 300s, timeout 600s, sleep 5s (longer due to LLM calls)
     - `default`: 2 processes, maxTime 180s, timeout 300s, sleep 3s
     - `batch`: 1 process, maxTime 600s, timeout 1800s, sleep 10s
2. **Base Job Class Creation**
   - Create `app/Jobs/BaseJob.php` as abstract class extending Queue\Job
   - Implement traits for:
     - **Idempotency**: Store processed job IDs in cache with TTL
     - **Model Missing Handling**: Use `deleteWhenMissingModels()` trait
     - **Rate Limit Safe Release**: Replace sleep() with `release($delay)` for rate limits
   - Add common properties:
     - `$tries`: Set to 3 by default
     - `$timeout`: Set to 120s by default
     - `$maxExceptions`: Set to 3
   - Implement methods:
     - `checkIdempotency()`: Check if job already processed
     - `markAsProcessed()`: Mark job as processed in cache
     - `handleRateLimit()`: Release job with exponential backoff instead of sleeping
     - `safelyGetModel()`: Fetch model with missing check
3. **Queue Configuration in config/queue.php**
   - Verify `connections.redis` is properly configured
   - Ensure `default` connection uses `redis`
   - Add queue-specific timeout and retry settings
4. **Environment Setup**
   - Update `.env` with:
     - `QUEUE_CONNECTION=redis`
     - `HORIZON_DOMAIN=localhost` (or production domain)
     - `HORIZON_PATH=/horizon`

## ✅ Final Verification Checklist
- [ ] Horizon config created with all 4 supervisors (critical, llm-inference, default, batch)
- [ ] BaseJob class created with idempotency, model missing, and rate limit handling
- [ ] `.env` configured for Redis queue connection
- [ ] `php artisan horizon` can run without errors
- [ ] Horizon dashboard accessible at `/horizon`
- [ ] BaseJob trait properly integrated with Laravel queue system
