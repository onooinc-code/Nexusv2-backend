# 🚀 UPDATE BLUEPRINT: UP-003 AsyncEngine Refactoring

## 📊 TASK EXECUTION CHECKLIST

- [x] **Finished_Task 1:** Horizon Configuration & Base Job Infrastructure
- [x] **Finished_Task 2:** Core Job Classes Implementation  
- [x] **Finished_Task 3:** Event Broadcasting Infrastructure
- [x] **Task 4:** Event Refactoring & Broadcasting Implementation
- [x] **Finished_Task 5:** Laravel Echo & Reverb Frontend Integration
- [x] **Finished_Task 6:** Real-time Components Development
- [x] **Finished_Task 7:** Controller Integration & Job Dispatching
- [ ] **Task 8:** DLQ Management & Resilience Monitoring
- [ ] **Task 9:** Testing, Validation & Documentation

## 📝 Current UP-003 Progress
- Task 5-7 are complete and documented.
- Task 8 is partially implemented: DLQ controller, event listener, health and metrics endpoints, and admin monitoring UI are present. Remaining work includes scheduled Reverb health checks, advanced admin UI polish, and additional resilience hardening.
- Task 9 is partially started: async flow and circuit breaker tests are available. Remaining work includes dedicated WebSocket/DLQ/health coverage, schedule and monitoring tests, and formal AsyncEngine setup/testing docs.

---

## 1. Meta & Pre-flight Analysis
- **Features & Details:** 
  - Implement Laravel Echo & Reverb WebSocket integration for real-time communication
  - Create background job system with proper queue separation and Horizon configuration
  - Develop secure event broadcasting with payload sanitization
  - Implement job resilience patterns (idempotency, safe retries, no sleep)
  - Add frontend components for real-time streaming and job monitoring
  - Implement Dead-Letter Queue management and monitoring
  - Add graceful degradation for WebSocket failures
  - Add Reverb server health monitoring
- **Project Context & Versions:** 
  - Laravel 11.x (based on codebase examination)
  - Laravel Reverb for WebSocket server
  - Laravel Horizon for queue management
  - Redis as queue driver and message broker
  - Vue 3 with Composition API (based on script setup syntax)
  - Laravel Echo for frontend WebSocket client
  - Pinia for state management (to be implemented)
- **Regression Check:** 
  - These changes are additive and will not break existing REST API functionality
  - Existing routes and controllers will remain functional
  - WebSocket features will operate alongside existing REST endpoints
  - Queue system enhancement will improve performance without changing job interfaces
  - Frontend changes will be incremental, adding WebSocket capabilities while maintaining REST fallback

## 2. Feature Specifications (Per Feature)

### Feature Name & ID: FE-001 Laravel Echo & Reverb Integration
- **Specs & Requirements:**
  - Install and configure Laravel Echo and Pusher JS (or Reverb) client libraries
  - Initialize Echo connection in app bootstrap with proper authentication
  - Implement connection status monitoring with reconnection logic
  - Create private channel subscription mechanism for chat sessions
  - Implement token-by-token streaming reception for LLM responses
  - Add graceful fallback to HTTP polling when WebSocket unavailable
- **UI/UX Specs:**
  - Connection status indicator (dot) in header showing connected/disconnected/reconnecting states
  - Silent fallback mechanism (no user disruption when switching to polling)
  - Automatic resync of missed events upon reconnection
- **Logic Workflow:**
  1. Initialize Echo with Reverb connector and auth endpoint
  2. Subscribe to private chat session channels on component mount
  3. Listen for TokenStreamed events to append tokens to message
  4. Listen for MessageCompleted events to finalize message
  5. Monitor connection state via Pinia store
  6. On disconnect, switch to REST polling for critical updates
  7. On reconnect, fetch missed events and resume WebSocket listening
- **Technical Workflow:**
  1. Install npm dependencies: laravel-echo, pusher-js
  2. Configure broadcasting.php to use reverb driver
  3. Update bootstrap.js to initialize Echo
  4. Create useEchoStore.js Pinia store for connection state
  5. Modify ChatInterface.vue to use Echo instead of pure REST
  6. Create LiveChatStream.vue component for streaming-specific logic
  7. Implement syncMissedEvents() method for reconnection handling
- **Backend Readiness:**
  - Routes/channels.php needs to be created (missing)
  - Events need to implement ShouldBroadcast interface
  - Controllers need to dispatch events instead of returning data directly
- **Required Libraries:**
  - laravel-echo
  - pusher-js (or laravel-reverb-js when available)
  - pinia (for Vue store)
- **Class/Component Names:**
  - useEchoStore.js (Pinia store)
  - LiveChatStream.vue (component)
  - ChatInterface.vue (modified component)
  - routes/channels.php (new file)
  - app/Events/TokenStreamed.php (new event)
  - app/Events/MessageCompleted.php (new event)
- **Functions to Modify/Create:**
  - resources/js/bootstrap.js: Add Echo initialization
  - resources/js/stores/useEchoStore.js: Create new Pinia store
  - resources/js/Pages/ChatInterface.vue: Replace fetch logic with Echo listeners
  - resources/js/Pages/Components/LiveChatStream.vue: Create new component
  - routes/channels.php: Create new file with channel authorization
  - app/Events/TokenStreamed.php: Create new event implementing ShouldBroadcastNow
  - app/Events/MessageCompleted.php: Create new event for message completion
  - app/Http/Controllers/ConversationController.php: Modify sendMessage to dispatch events

### Feature Name & ID: FE-002 Background Job System & Horizon Configuration
- **Status:** Finished
- **Specs & Requirements:**
  - Implement Laravel Horizon with proper queue supervision
  - Create separate queue workers for critical, llm-inference, default, batch queues
  - Configure appropriate timeout, retry, and scaling settings per queue type
  - Implement job chaining with failure handling
  - Add payload sanitization for queue jobs (no raw secrets)
  - Implement idempotency patterns in all jobs
  - Add model missing handling with deleteWhenMissingModels
  - Implement rate limit safe release instead of sleep
- **UI/UX Specs:**
  - GlobalJobMonitor.vue component showing batch progress via WebSocket updates
  - Dashboard widgets showing queue depths and processing rates
- **Logic Workflow:**
  1. Configure Horizon supervisors with queue-specific settings
  2. Create base job class with common resilience patterns
  3. Implement specialized jobs for LLM inference, vectorization, etc.
  4. Use Bus::chain() with catch() for job chaining
  5. Dispatch jobs to appropriate queues based on priority
  6. Monitor job batches via database and broadcast progress
  7. Handle failed jobs with DLQ and retry mechanism
- **Technical Workflow:**
  1. Install laravel-horizon via composer
  2. Publish and configure horizon.php
  3. Create base job class with resilience traits
  4. Implement specific jobs (ProcessAiInferenceJob, VectorizeMemoryJob, etc.)
  5. Create job batching and chaining utilities
  6. Implement DLQ monitoring and retry endpoints
  7. Create GlobalJobMonitor.vue component
  8. Add WebSocket listeners for batch progress events
- **Backend Readiness:**
  - No app/Jobs directory exists - needs to be created
  - No Horizon configuration exists
  - Controllers currently process synchronously - need to shift to job dispatching
- **Required Libraries:**
  - laravel/horizon
  - predis/predis (Redis client)
- **Class/Component Names:**
  - app/Jobs/ProcessAiInferenceJob.php
  - app/Jobs/VectorizeMemoryJob.php
  - app/Jobs/SaveToPineconeJob.php
  - app/Jobs/ExtractMemoryJob.php
  - app/Jobs/BaseJob.php (abstract base class)
  - config/horizon.php
  - resources/js/Pages/Components/GlobalJobMonitor.vue
  - app/Events/BatchProgressUpdated.php
- **Functions to Modify/Create:**
  - config/horizon.php: Create new configuration file
  - app/Jobs/BaseJob.php: Create abstract base class with common traits
  - app/Jobs/ProcessAiInferenceJob.php: Implement LLM inference job
  - app/Jobs/VectorizeMemoryJob.php: Implement vectorization job
  - app/Jobs/SaveToPineconeJob.php: Implement storage job
  - app/Jobs/ExtractMemoryJob.php: Implement extraction job
  - app/Http/Controllers/AiModelController.php: Modify execute to dispatch job
  - app/Http/Controllers/MemoryController.php: Modify indexMemory to use job chaining
  - resources/js/Pages/Components/GlobalJobMonitor.vue: Create new component
  - app/Events/BatchProgressUpdated.php: Create event for progress broadcasting

### Feature Name & ID: FE-003 Event Broadcasting & Security Enhancement
- **Status:** Finished
- **Specs & Requirements:**
  - All events must implement ShouldBroadcast or ShouldBroadcastNow
  - Events must define broadcastOn() returning private/presence channels
  - Events must define broadcastWith() to limit payload and prevent data leakage
  - Channel authorization must validate user access via SecurityHub
  - No events should broadcast full Eloquent models
  - Sensitive data must be replaced with UUIDs or references in broadcasts
- **UI/UX Specs:** None (backend-focused)
- **Logic Workflow:**
  1. Create events with ShouldBroadcast interface
  2. Define private channel subscriptions in broadcastOn()
  3. Limit broadcast data to essential fields in broadcastWith()
  4. Implement channel authorization policies
  5. Replace model objects with IDs in event constructors
  6. Ensure all broadcasts go through secure channels
- **Technical Workflow:**
  1. Audit existing events for broadcast safety
  2. Refactor events to implement broadcast interfaces
  3. Create channel authorization in routes/channels.php
  4. Update controllers to fire events instead of returning data
  5. Implement event listeners where needed
  6. Add testing for event payloads and channel security
- **Backend Readiness:**
  - Existing events lack broadcast implementation
  - No channel authorization file exists
  - Controllers return data directly instead of using events
- **Required Libraries:** None (core Laravel functionality)
- **Class/Component Names:**
  - app/Events/MessageSent.php (refactored)
  - app/Events/MessageReceived.php (refactored)
  - app/Events/WorkflowStarted.php (refactored)
  - app/Events/WorkflowStepCompleted.php (refactored)
  - app/Events/AgentExecuted.php (refactored)
  - routes/channels.php (new file)
  - app/Policies/SessionPolicy.php (if not exists)
- **Functions to Modify/Create:**
  - app/Events/MessageSent.php: Add ShouldBroadcast, define broadcastOn/With
  - app/Events/MessageReceived.php: Same as above
  - app/Events/WorkflowStarted.php: Same as above
  - app/Events/WorkflowStepCompleted.php: Same as above
  - app/Events/AgentExecuted.php: Same as above
  - routes/channels.php: Create channel authorization definitions
  - app/Http/Controllers/ConversationController.php: Fire events instead of returning data
  - app/Http/Controllers/WebhookController.php: Fire events for incoming messages
  - app/Policies/SessionPolicy.php: Create or update policy for channel auth

### Feature Name & ID: FE-004 Resilience & Monitoring Features
- **Specs & Requirements:**
  - Implement Dead-Letter Queue (DLQ) monitoring with retry capability
  - Add WebSocket latency and queue throughput monitoring
  - Create graceful degradation mechanism for WebSocket failures
  - Implement Reverb server health monitoring with alerting
  - Add job idempotency protection
  - Implement exponential backoff for rate limit handling
  - Add queue worker separation to prevent slow jobs blocking fast ones
- **UI/UX Specs:**
  - Admin UI for monitoring failed jobs and DLQ
  - Dashboard widgets showing system health metrics
  - Connection status indicators for WebSocket and Reverb health
- **Logic Workflow:**
  1. Configure failed job tracking and notifications
  2. Create admin interface for viewing and retrying failed jobs
  3. Implement health check endpoints for Reverb and queue system
  4. Add monitoring endpoints for latency and throughput
  5. Create fallback mechanisms for critical updates
  6. Implement idempotency keys in job processing
  7. Add circuit breaker patterns for external service calls
- **Technical Workflow:**
  1. Implement JobFailed event listener for notifications
  2. Create admin routes and controllers for DLQ management
  3. Create health check endpoints (Reverb port, queue depth, worker status)
  4. Add monitoring routes for WebSocket latency and job throughput
  5. Implement fallback service for critical updates when WS down
  6. Add idempotency tracking to prevent duplicate processing
  7. Create scheduled health checks via Laravel Scheduler
- **Backend Readiness:**
  - No DLQ monitoring exists
  - No health check or monitoring endpoints
  - No graceful degradation logic
  - No idempotency protection in jobs
- **Required Libraries:**
  - laravel/sanctum (already present)
  - guzzlehttp/guzzle (for health checks)
- **Class/Component Names:**
  - app/Listeners/NotifyJobFailed.php
  - app/Http/Controllers/Admin/DlqController.php
  - app/Http/Controllers/Monitoring/HealthController.php
  - app/Http/Controllers/Monitoring/MetricsController.php
  - app/Jobs/ProcessAiInferenceJob.php (idempotency enhancement)
  - app/Console/Commands/CheckReverbHealth.php
  - resources/js/Pages/Components/ConnectionStatus.vue
- **Functions to Modify/Create:**
  - app/Listeners/NotifyJobFailed.php: Create new event listener
  - app/Providers/EventServiceProvider.php: Add listener mapping
  - routes/api.php: Add admin DLQ routes under middleware
  - routes/api.php: Add monitoring routes
  - app/Http/Controllers/Admin/DlqController.php: Create new controller
  - app/Http/Controllers/Monitoring/HealthController.php: Create new controller
  - app/Http/Controllers/Monitoring/MetricsController.php: Create new controller
  - app/Console/Commands/CheckReverbHealth.php: Create new Artisan command
  - app/Console/Kernel.php: Schedule the health check command
  - resources/js/Pages/Components/ConnectionStatus.vue: Create new component
  - resources/js/Pages/DashboardView.php: Add connection status widget

## 3. Testing Strategy
- **Automated Testing:**
  - Unit tests for all new job classes testing:
    - Idempotency (running twice produces same result)
    - Model missing handling (deleteWhenMissingModels)
    - Rate limit handling (release instead of sleep)
    - Timeout and tries configuration
    - Queue assignment correctness
  - Feature tests for WebSocket functionality:
    - Echo connection establishment
    - Private channel authorization
    - Token streaming reception
    - Message completion handling
    - Reconnection and event resync
  - Feature tests for event broadcasting:
    - Event broadcasting on appropriate actions
    - Channel authorization enforcement
    - Payload sanitization (no full models)
    - broadcastWith() returns limited data
  - Tests for Horizon configuration:
    - Queue supervisor settings validation
    - Job routing to correct queues
    - Retry and timeout behavior
  - Tests for resilience features:
    - DLQ job capture and retry
    - Health check endpoints
    - Graceful degradation fallback
    - Idempotency protection
- **Manual Testing Steps:**
  1. WebSocket Integration Testing:
     - Start Laravel Reverb server
     - Load application and verify Echo connection establishes
     - Send message and observe token-by-token streaming in UI
     - Disconnect network and verify fallback to polling
     - Reconnect network and verify resumption of WebSocket and missed event sync
     - Test private channel security by attempting unauthorized access
  2. Job System Testing:
     - Dispatch LLM inference job and verify it goes to llm-inference queue
     - Monitor job processing via Horizon dashboard
     - Test job chaining with failure scenarios
     - Kill queue worker during job processing and verify safe restart
     - Test rate limit handling by simulating API 429 responses
     - Verify deleted model handling with deleteWhenMissingModels
  3. Event Broadcasting Testing:
     - Trigger events and verify they broadcast on correct channels
     - Use Laravel Echo to listen for broadcasts and validate payload
     - Attempt to subscribe to channels without authorization and verify rejection
     - Check broadcast payloads to ensure no full Eloquent models are leaked
  4. Resilience & Monitoring Testing:
     - Fail a job intentionally and verify it appears in DLQ
     - Use DLQ interface to retry failed job and verify success
     - Stop Reverb server and verify monitoring alerts trigger
     - Test fallback mechanism when WebSocket is unavailable
     - Monitor latency and throughput metrics under load
     - Test idempotency by submitting duplicate job requests
