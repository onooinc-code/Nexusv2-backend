 
# ⚡ Async & Real-Time Engine (WebSockets, Jobs & Queues) - Master Blueprint (Report 1: Architecture)

## 1. 🎯 Executive Definition & Core Philosophy

**Definition:** 
In the Nexus platform, the combination of **WebSockets (Laravel Reverb)**, **Background Jobs**, and **Queues (Laravel Horizon / Redis)** forms the "Asynchronous & Real-Time Nervous System." Because Nexus relies on heavy cognitive tasks (LLM inference, semantic searches, memory consolidation), the system is strictly **Background-First**. APIs must respond instantly, offloading heavy lifting to queues, while WebSockets push live state updates, streaming tokens, and completion events back to the UI.

**Core Philosophy & Architectural Principles:**
*   **Zero-Blocking APIs:** No REST API endpoint should ever wait for an LLM response or an external HTTP call if it exceeds 200ms. Everything heavy is pushed to a Queue.
*   **Event-Driven Push over Long-Polling:** The frontend must NEVER poll the backend for updates. The backend pushes state changes instantly via Private WebSocket Channels.
*   **Tiered Priority Queuing:** Not all jobs are equal. The system enforces strict queue priorities: `critical` (immediate chat responses), `default` (standard workflows), `long_running` (memory summaries), and `batch` (vector embeddings).
*   **Idempotency & Safe Retries:** Every background job must be idempotent. If a queue worker crashes or a job fails, the automatic retry mechanism (with exponential backoff) must not corrupt the database or duplicate notifications.

---

## 2. 📋 Exhaustive Requirements

### Functional Requirements
*   **WebSocket Broadcasting:** Support for Private Channels (authenticated per user/admin) and Presence Channels (showing if Hédra or a Contact is currently active/typing).
*   **Streaming Responses:** Real-time streaming of LLM generated text (token-by-token) from the backend directly to the Vue.js Chat UI via WebSockets.
*   **Job Batching & Chaining:** Ability to chain jobs sequentially (e.g., *Job 1: Fetch Data -> Job 2: Generate Vector -> Job 3: Save Memory*) and batch jobs (e.g., process 100 historical messages at once).
*   **Dead-Letter Queue (DLQ) Management:** Failed jobs must be safely captured in a DLQ with full stack traces, input payloads, and a manual "Retry" button available in the Nexus Admin UI.

### Non-Functional Requirements
*   **WebSocket Latency:** Message delivery from event dispatch to UI render must be `< 50ms`.
*   **Queue Throughput:** The Redis-backed queue system must support at least `10,000+` jobs processed daily without memory leaks.
*   **Graceful Degradation:** If the WebSocket server (Reverb) goes offline, the frontend must silently fall back to standard HTTP polling for critical updates (like new messages) without breaking the app.

### Security & Privacy Constraints
*   **Channel Authentication:** WebSockets must enforce strict auth checks. A user cannot listen to `private-contact.{id}` unless they are authorized by the `SecurityHub`.
*   **Payload Sanitization in Queues:** Background job payloads (which are serialized and stored in Redis/DB) must NOT contain raw API keys or highly sensitive unencrypted PII. They should pass database UUIDs instead of raw data.

---

## 3. ⚙️ Technical & Architectural Details (High-Level)

*   **Queue Driver & Supervisor:** 
    *   **Laravel Horizon:** Acts as the master supervisor for Redis queues, providing auto-scaling of worker processes based on queue depth.
*   **WebSocket Server:** 
    *   **Laravel Reverb:** A first-party, high-performance WebSocket server written in PHP, natively integrated with Laravel's Event Broadcasting system.
*   **Job Architecture:**
    *   All jobs implement `ShouldQueue` and utilize traits: `Dispatchable`, `InteractsWithQueue`, `Queueable`, and `SerializesModels`.
    *   **Timeout & Tries:** Every job explicitly defines `$timeout` (e.g., 120s for LLMs) and `$tries` (e.g., 3 retries).
*   **Frontend Integration:**
    *   Uses **Laravel Echo** to subscribe to channels.

---

## 4. 🕸️ The Relational Matrix (Deep System Integration)

The Async Engine is the invisible fabric connecting all Hubs. Here is how it interacts with the rest of Nexus:

*   **AiModelsHub:**
    *   *Queueing:* Batches vector embeddings (`embedding-004`) to the `batch` queue to save costs.
    *   *WebSockets:* Streams LLM generation tokens to a WebSocket channel (`private-chat.session_id`) so the UI sees the AI typing in real-time.
*   **WorkflowsAndTasksHub:**
    *   *Queueing:* Every "Step" in a workflow is a standalone background job. If Step 1 succeeds, it dispatches Step 2.
    *   *WebSockets:* Broadcasts `WorkflowProgressUpdated` events to update the visual progress bar on the frontend.
*   **MemoryHub:**
    *   *Queueing:* Runs heavy `ConsolidateMemoryJob` on the `long_running` queue at 2:00 AM nightly.
*   **LogsHub:**
    *   *WebSockets:* Streams live operational logs to the Nexus Dashboard (like a terminal tail) via a dedicated `private-syslogs` channel.
*   **NotificationHub:**
    *   *Queueing:* Dispatches `SendEmailJob` and `SendSmsJob` via Horizon.
    *   *WebSockets:* Pushes the `NotificationCreated` event to immediately trigger the Red Bell badge on the UI.
*   **ContactsHub / Channels (WAHA):**
    *   *Queueing:* Inbound WhatsApp messages from the Webhook hit the API and are instantly pushed to the `critical` queue for immediate processing, freeing up the Webhook connection.

---

## 5. 🚦 Edge Cases, Constraints & Business Rules

*   **Rule - Prevent Queue Blocking (The "No Sleep" Rule):** A queue worker must NEVER use `sleep()` to wait for a rate limit. If an API is rate-limited (e.g., HTTP 429), the job must `release($seconds)` itself back into the queue to free up the worker for other tasks.
*   **Edge Case - Model Serialization Failure:** If a background job expects an Eloquent Model (e.g., `Contact`) but the record is deleted before the job runs, standard Laravel behavior throws a `ModelNotFoundException`. Nexus explicitly handles this by adding `public $deleteWhenMissingModels = true;` to jobs to silently discard them.
*   **Constraint - Streaming Disconnect:** If the user's browser drops the WebSocket connection while the `AiModelsHub` is streaming a response, the backend must NOT crash. It finishes the generation in the background, saves the complete message to the Database, and the frontend will fetch it via REST upon reconnection.
*   **Rule - Strict Queue Separation:** LLM Requests MUST be isolated to their own queue worker pool (e.g., `llm-workers`). They must not share workers with standard DB tasks to prevent slow AI calls from blocking fast database operations.
 
