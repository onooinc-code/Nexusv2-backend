 

# ⚡ Async & Real-Time Engine - Master Blueprint (Report 2: Implementation Details)

## 1. 🖥️ Frontend & UI/UX Integration (Vue.js / Laravel Echo)

### A. Core Views & Components
1. **`LiveChatStream.vue` (AI Streaming Component):**
    *   **Purpose:** Renders the token-by-token stream from the LLM.
    *   **Logic:** Instead of replacing the text, it appends incoming tokens to a localized `ref`.
    *   **WebSocket Hook:**
        ```javascript
        window.Echo.private(`chat.session.${sessionId}`)
            .listen('.TokenStreamed', (e) => {
                streamingText.value += e.token;
            })
            .listen('.MessageCompleted', (e) => {
                finalizeMessage(e.message_data);
            });
        ```
2. **`GlobalJobMonitor.vue` (Dashboard Widget):**
    *   **UI:** A minimal progress bar tracking batched jobs (e.g., "Consolidating 1,200 memories: 45%").
    *   **WebSocket Hook:** Listens to batch completion events sent via Reverb.

### B. Connection Management & State (Pinia)
*   **`useEchoStore.js`:**
    *   **State:** `isConnected` (boolean), `ping` (ms).
    *   **Behavior:** Monitors connection state. If Reverb disconnects, it displays a silent yellow dot in the header. Upon reconnection, it automatically triggers a REST API call to fetch any missed messages or state updates (`syncMissedEvents()`).

---

## 2. 🗄️ Database, Redis & Horizon Configuration

The system uses Redis as the primary queue driver, managed by Laravel Horizon, and standard tables for failing/batching.

### A. Queue Definitions (`config/horizon.php`)
Strict queue separation is mandatory to prevent fast tasks from being blocked by slow LLM calls.
```php
'environments' => [
    'production' => [
        'supervisor-critical' => [
            'connection' => 'redis',
            'queue'      => ['critical'],
            'balance'    => 'simple',
            'processes'  => 5,
            'tries'      => 3,
            'timeout'    => 60,
        ],
        'supervisor-llm' => [
            'connection' => 'redis',
            'queue'      => ['llm-inference'],
            'balance'    => 'auto', // Auto-scales based on queue depth
            'minProcesses' => 2,
            'maxProcesses' => 15,
            'tries'      => 2,
            'timeout'    => 300, // LLMs take time, timeout must be high
        ],
        'supervisor-default' => [
            'connection' => 'redis',
            'queue'      => ['default', 'notifications', 'batch'],
            'balance'    => 'auto',
            'processes'  => 10,
            'tries'      => 3,
            'timeout'    => 90,
        ],
    ],
],
```

### B. Database Tables (Laravel 11 Defaults but critical to verify)
*   `jobs` (If fallback to DB is ever needed, though Redis is primary).
*   `failed_jobs`: (id, uuid, connection, queue, payload, exception, failed_at).
*   `job_batches`: (id, name, total_jobs, pending_jobs, failed_jobs, failed_job_ids, options, cancelled_at, created_at, finished_at).

---

## 3. 🌐 API Contracts & Service Layer

### A. Channel Authorization (`routes/channels.php`)
All WebSockets in Nexus are Private or Presence channels. Zero public channels.
```php
// User's personal notification & sync channel
Broadcast::channel('user.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Private chat session (Secured)
Broadcast::channel('chat.session.{sessionId}', function ($user, $sessionId) {
    // Logic: check if the user owns this chat session via SecurityHub
    return SessionPolicy::canAccess($user, $sessionId);
});
```

### B. Event Broadcasting Class (Example)
```php
class TokenStreamed implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $sessionId, 
        public string $token
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('chat.session.' . $this->sessionId)];
    }

    // Must specify explicitly what is broadcasted to save bandwidth
    public function broadcastWith(): array
    {
        return ['token' => $this->token];
    }
}
```

### C. Background Job Structure (Example)
```php
class ProcessAiInferenceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $deleteWhenMissingModels = true; // Auto-discard if DB record is deleted
    public $timeout = 120;

    public function __construct(public RequestPayload $payload) {
        $this->onQueue('llm-inference'); // Explicit queue assignment
    }

    public function handle()
    {
        try {
            // Processing logic...
        } catch (RateLimitException $e) {
            // DO NOT SLEEP. Release back to queue with exponential backoff.
            $this->release(pow(2, $this->attempts()) * 10);
        }
    }
}
```

---

## 4. 🔄 Automated Recoveries & Edge Cases

*   **Job Chaining with Failure Catch:** When dispatching a chain (e.g., `FetchData -> Transform -> Save`), the system uses the `catch` block to handle partial failures to prevent data corruption.
    ```php
    Bus::chain([
        new ExtractMemoryJob($message),
        new VectorizeMemoryJob(),
        new SaveToPineconeJob()
    ])->catch(function (Throwable $e) {
        LogHubService::logChainFailure($e);
    })->dispatch();
    ```
*   **Reverb Server Monitoring:** A scheduled ping (via `SchedulerHub`) checks if the Reverb port (e.g., 8080) is accepting connections. If closed, an alert is sent via the `NotificationHub` (using Email/SMS since WebSockets are down).

***
 