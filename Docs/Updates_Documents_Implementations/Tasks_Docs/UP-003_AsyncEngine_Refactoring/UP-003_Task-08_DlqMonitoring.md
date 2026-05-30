# 🎯 TASK: UP-003 - Task 8: DLQ Management & Resilience Monitoring
- **Status:** 🔴 PENDING (Backend foundation exists; UI and scheduled health check command remain)
- **Dependencies:** Task 2 (Core Job Classes Implementation), Task 7 (Controller Integration & Job Dispatching)

## 0. Current Implementation
- `app/Listeners/NotifyJobFailed.php`: Job failure listener is implemented.
- `app/Http/Controllers/Admin/DlqController.php`: DLQ list, retry, destroy, and batch retry endpoints exist.
- `app/Http/Controllers/Monitoring/HealthController.php`: Health endpoints for Redis, DB, Reverb, and queue exist.
- `app/Http/Controllers/Monitoring/MetricsController.php`: Queue and websocket metrics endpoints exist.
- `routes/api.php`: Monitoring and DLQ routes are registered.
- `resources/js/Components/GlobalJobMonitor.vue`: Admin DLQ monitoring UI exists.
- `app/Services/IdempotencyService.php`: Idempotency tracking service exists.
- `app/Services/CircuitBreakerService.php`: Circuit breaker implementation exists.
- `app/Providers/AppServiceProvider.php`: `JobFailed` listener is registered.

## 1. Objective
Implement Dead-Letter Queue (DLQ) management system with admin UI for monitoring and retrying failed jobs. Create health check endpoints for Reverb server and queue system. Add graceful degradation mechanism for WebSocket failures. Implement idempotency protection and circuit breaker patterns.

## 2. Files to Create/Modify
- `app/Listeners/NotifyJobFailed.php`: New listener for job failures
- `app/Http/Controllers/Admin/DlqController.php`: New controller for DLQ management
- `app/Http/Controllers/Monitoring/HealthController.php`: New controller for health checks
- `app/Http/Controllers/Monitoring/MetricsController.php`: New controller for metrics
- `app/Console/Commands/CheckReverbHealth.php`: New Artisan command
- `app/Services/IdempotencyService.php`: New service for idempotency tracking
- `app/Services/CircuitBreakerService.php`: New service for circuit breaker pattern
- `app/Providers/EventServiceProvider.php`: Register JobFailed listener
- `app/Console/Kernel.php`: Schedule health check command
- `routes/api.php`: Add admin and monitoring routes
- `resources/js/Pages/Admin/DlqManagement.vue`: New admin component (optional)

## 3. Implementation Steps
1. **Create app/Listeners/NotifyJobFailed.php**
   - Listen to `Illuminate\Queue\Events\JobFailed` event
   - Extract failed job details: id, queue, payload, exception
   - Store in database table `failed_jobs` with:
     - `id`, `uuid`, `queue`, `payload`, `exception`, `failed_at`, `retry_count`, `last_retry_at`
   - Send admin notification (Slack, email, or in-app)
   - Fire `JobFailedEvent` for monitoring dashboard
   - Implement retry logic with exponential backoff

2. **Create app/Http/Controllers/Admin/DlqController.php**
   - Endpoint: `GET /admin/dlq` - List failed jobs with pagination
     - Returns: `[{id, uuid, queue, failed_at, exception_type, retry_count}, ...]`
     - Filters: queue, date range, retry_count
   - Endpoint: `POST /admin/dlq/{id}/retry` - Retry a failed job
     - Extract payload from failed job
     - Re-dispatch job to queue
     - Update retry_count and last_retry_at
     - Return: `{id, status: 'retrying', message: 'Job requeued'}`
   - Endpoint: `DELETE /admin/dlq/{id}` - Remove job from DLQ
     - Soft delete (mark as manually cleared)
     - Return: `{status: 'deleted', message: 'Job removed from DLQ'}`
   - Endpoint: `POST /admin/dlq/batch-retry` - Retry multiple jobs
     - Accept array of job IDs
     - Retry all with same logic
     - Return count of requeued jobs
   - Middleware: Verify admin role only

3. **Create app/Http/Controllers/Monitoring/HealthController.php**
   - Endpoint: `GET /monitoring/health` - System health check
     - Check Redis connection: connect and ping
     - Check Reverb server: HTTP request to Reverb health endpoint
     - Check queue workers: Check if any workers active (count active processes)
     - Check database: Run simple query
     - Return status: `{status: 'healthy'|'degraded'|'critical', checks: {redis, reverb, workers, db}}`
   - Endpoint: `GET /monitoring/health/reverb` - Reverb-specific health
     - Query Reverb port availability
     - Check Reverb process running (system command)
     - Return: `{status, host, port, connections_count}`
   - Endpoint: `GET /monitoring/health/queue` - Queue system health
     - Count jobs in each queue
     - Count failed jobs
     - Check worker status
     - Return: `{status, queues: {critical, llm-inference, default, batch}, failed_count, worker_count}`

4. **Create app/Http/Controllers/Monitoring/MetricsController.php**
   - Endpoint: `GET /monitoring/metrics` - System metrics
     - Queue depths: Jobs waiting in each queue
     - Processing rates: Jobs/second processed
     - WebSocket latency: Average RTT for Echo connections
     - Job throughput: Completed jobs over time window
     - Failed job rate: Failures/hour
     - Return: `{timestamp, queues, throughput, latency, errors}`
   - Endpoint: `GET /monitoring/metrics/websocket` - WebSocket metrics
     - Active connections count
     - Average latency
     - Events broadcast/received in last 5 min
     - Return: `{active_connections, latency_ms, throughput}`

5. **Create app/Console/Commands/CheckReverbHealth.php**
   - Scheduled command to run every 5 minutes
   - Call `HealthController@reverb` endpoint
   - If status != 'healthy':
     - Log alert
     - Send notification to admins
     - Store alert in database for dashboard display
   - Actions on critical failure:
     - Trigger graceful degradation mode
     - Notify frontend via broadcast event
     - Switch to REST polling fallback

6. **Create app/Services/IdempotencyService.php**
   - Method: `isProcessed(string $key): bool`
     - Check if idempotency key exists in cache
   - Method: `markAsProcessed(string $key, $ttl = 3600): void`
     - Store idempotency key in cache with TTL
   - Method: `getResult(string $key): ?array`
     - Retrieve cached result for idempotency key
   - Method: `cacheResult(string $key, $result, $ttl = 3600): void`
     - Store result for future identical requests
   - Use Redis for persistence across worker crashes

7. **Create app/Services/CircuitBreakerService.php**
   - Implement circuit breaker pattern for external APIs
   - States: CLOSED (normal), OPEN (failing), HALF_OPEN (testing)
   - Method: `call(string $serviceName, callable $callback)`
     - Execute callback
     - Track failures
     - If failure rate exceeds threshold, open circuit
     - Return fallback result when circuit open
   - Track: failure_count, last_failure_time, state, threshold
   - Store state in Redis for shared access across workers

8. **Register JobFailed Listener**
   - In `app/Providers/EventServiceProvider.php`:
     ```php
     protected $listen = [
       JobFailed::class => [NotifyJobFailed::class],
     ];
     ```

9. **Schedule Health Check Command**
   - In `app/Console/Kernel.php`:
     ```php
     $schedule->command('reverb:health-check')
       ->everyFiveMinutes()
       ->onFailure(fn() => // notify admin)
     ```

10. **Add Admin & Monitoring Routes**
    - In `routes/api.php`:
      ```php
      Route::prefix('admin')->middleware(['auth:sanctum', 'admin'])->group(function () {
        Route::prefix('dlq')->controller(DlqController::class)->group(function () {
          Route::get('/', 'index');
          Route::post('{id}/retry', 'retry');
          Route::delete('{id}', 'destroy');
          Route::post('batch-retry', 'batchRetry');
        });
      });
      
      Route::prefix('monitoring')->group(function () {
        Route::get('health', [HealthController::class, 'health']);
        Route::get('health/reverb', [HealthController::class, 'reverb']);
        Route::get('health/queue', [HealthController::class, 'queue']);
        Route::get('metrics', [MetricsController::class, 'metrics']);
        Route::get('metrics/websocket', [MetricsController::class, 'websocket']);
      });
      ```

## ✅ Final Verification Checklist
- [ ] NotifyJobFailed listener created and fires on job failures
- [ ] DLQ admin controller with CRUD operations for failed jobs
- [ ] Retry mechanism works and re-queues failed jobs
- [ ] Health check endpoints return correct status
- [ ] Reverb health endpoint detects connection issues
- [ ] Queue health shows jobs per queue and failed count
- [ ] Health check command scheduled and runs every 5 minutes
- [ ] Metrics endpoints return accurate queue and throughput data
- [ ] Idempotency service prevents duplicate job processing
- [ ] Circuit breaker pattern handles API failures gracefully
- [ ] Graceful degradation triggered when Reverb unhealthy
- [ ] Admin routes require admin middleware
- [ ] Monitoring routes accessible without authentication (or with appropriate middleware)
- [ ] Database tables for failed_jobs and metrics exist
- [ ] No sensitive data exposed in monitoring endpoints
- [ ] Alerts sent to admins on critical health issues
