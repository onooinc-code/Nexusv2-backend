# 🎯 TASK: UP-003 - Task 9: Testing, Validation & Documentation
- **Status:** 🔴 PENDING (Existing async flow and resilience tests are present; WebSocket/DLQ coverage and docs remain incomplete)
- **Dependencies:** All previous tasks (1-8)

## 0. Current Implementation
- `tests/Feature/AsyncEngineFlowTest.php`: Async dispatch and webhook job queuing are covered.
- `tests/Unit/CircuitBreakerTest.php`: Circuit breaker behavior is validated.
- Existing feature/unit test structure is present for Expandable AsyncEngine coverage.
- Dedicated documentation files `docs/ASYNCENGINE_SETUP.md` and `docs/ASYNCENGINE_TESTING.md` do not yet exist.

## 1. Objective
Implement comprehensive testing suite for all AsyncEngine features including unit tests for jobs, feature tests for WebSocket functionality, event broadcasting tests, and integration tests for the complete system. Document features and provide manual testing procedures. Verify no regressions in existing functionality.

## 2. Files to Create/Modify
- `tests/Unit/Jobs/ProcessAiInferenceJobTest.php`: Unit tests for LLM inference job
- `tests/Unit/Jobs/VectorizeMemoryJobTest.php`: Unit tests for vectorization
- `tests/Unit/Jobs/SaveToPineconeJobTest.php`: Unit tests for Pinecone storage
- `tests/Unit/Jobs/ExtractMemoryJobTest.php`: Unit tests for extraction
- `tests/Feature/WebSocket/EchoConnectionTest.php`: Feature tests for Echo setup
- `tests/Feature/WebSocket/ChannelAuthorizationTest.php`: Feature tests for channel security
- `tests/Feature/WebSocket/TokenStreamingTest.php`: Feature tests for token streaming
- `tests/Feature/Broadcasting/EventBroadcastingTest.php`: Feature tests for event broadcasts
- `tests/Feature/Broadcasting/PayloadSanitizationTest.php`: Test payload security
- `tests/Feature/Jobs/JobChainingTest.php`: Feature tests for job chaining
- `tests/Feature/Resilience/DlqManagementTest.php`: Feature tests for DLQ retry
- `tests/Feature/Health/HealthCheckTest.php`: Feature tests for health endpoints
- `tests/Integration/AsyncEngineE2eTest.php`: End-to-end integration test
- `docs/ASYNCENGINE_SETUP.md`: User-facing setup documentation
- `docs/ASYNCENGINE_TESTING.md`: Testing and troubleshooting guide

## 3. Implementation Steps
1. **Unit Tests - ProcessAiInferenceJobTest.php**
   - Test idempotency: Running job twice with same ID produces single result
   - Test model missing: Job safely handles deleted Conversation
   - Test rate limit handling: 429 response triggers release() not sleep()
   - Test timeout and tries configuration: Verify 3 retries, 600s timeout
   - Test event dispatch: Verify TokenStreamed events fire for each token
   - Test job queue assignment: Verify dispatched to 'llm-inference' queue
   - Mock LLM API calls to test success and failure scenarios

2. **Unit Tests - VectorizeMemoryJobTest.php**
   - Test idempotency: Same memory ID doesn't create duplicate vectors
   - Test model missing: Safe handling when memory deleted
   - Test rate limit: Release instead of sleep
   - Test embedding generation: Verify vector stored correctly
   - Test event dispatch: MemoryVectorized event fires
   - Test queue assignment: Goes to 'default' queue

3. **Unit Tests - SaveToPineconeJobTest.php**
   - Test idempotency: Same memory doesn't create duplicate Pinecone entry
   - Test Pinecone API call: Verify upsert operation
   - Test metadata handling: No sensitive data in metadata
   - Test error handling: Gracefully handles Pinecone API errors
   - Test event dispatch: MemoryIndexed event fires
   - Test retry logic: Job retries on transient failures

4. **Unit Tests - ExtractMemoryJobTest.php**
   - Test extraction logic: Memories extracted from conversation
   - Test job chaining: Properly chains vectorization and storage jobs
   - Test model missing: Safe handling of deleted conversations
   - Test event dispatch: MemoriesExtracted event fires with count
   - Test memory count accuracy: Extracted count matches created records

5. **Feature Tests - EchoConnectionTest.php**
   - Test Echo initialization: Reverb connection establishes
   - Test auth endpoint: Authentication required for private channels
   - Test connection events: 'connected', 'reconnecting', 'error' states
   - Test graceful fallback: Switches to polling when WS unavailable
   - Test missed event resync: Fetches missed events on reconnection

6. **Feature Tests - ChannelAuthorizationTest.php**
   - Test private channel auth: Authorized users can subscribe
   - Test unauthorized access: Unauthorized users rejected
   - Test presence channels: Users tracked accurately
   - Test channel data sanitization: No full models in channel data
   - Test channel cleanup: Subscriptions cleaned on disconnect

7. **Feature Tests - TokenStreamingTest.php**
   - Test token reception: Each token received and displayed
   - Test streaming order: Tokens received in correct order
   - Test message completion: MessageCompleted event received
   - Test streaming interruption: Handles mid-stream disconnection
   - Test streaming UI update: Component updates without full re-render

8. **Feature Tests - EventBroadcastingTest.php**
   - Test MessageSent broadcast: Event fires on correct channel
   - Test payload safety: Broadcast payload omits full models
   - Test MessageReceived broadcast: Event fires with sanitized data
   - Test WorkflowStarted broadcast: Workflow start event broadcasts
   - Test WorkflowStepCompleted broadcast: Step completion broadcasts
   - Test AgentExecuted broadcast: Agent execution broadcasts

9. **Feature Tests - PayloadSanitizationTest.php**
   - Test MessageSent payload: Contains id, sender, content, timestamp only
   - Test MessageReceived payload: Contains id, agent_id, data only
   - Test no model leakage: Full User/Agent/Memory models NOT in payload
   - Test metadata only: Only essential metadata included
   - Test sensitive field exclusion: Passwords, keys, tokens excluded

10. **Feature Tests - JobChainingTest.php**
    - Test chain execution: Jobs execute in sequence
    - Test chain failure: Chain stops on job failure
    - Test catch handling: Catch handler executes on failure
    - Test progress tracking: Each job completion tracks progress

11. **Feature Tests - DlqManagementTest.php**
    - Test DLQ listing: Failed jobs appear in DLQ
    - Test job retry: Retry endpoint re-queues job
    - Test batch retry: Multiple jobs requeued
    - Test DLQ deletion: Failed job removed from DLQ
    - Test admin auth: Requires admin role for access

12. **Feature Tests - HealthCheckTest.php**
    - Test health endpoint: Returns system status
    - Test Reverb health: Detects Reverb connection status
    - Test queue health: Reports queue depths
    - Test metrics endpoint: Returns accurate metrics
    - Test WebSocket metrics: Latency and throughput calculated

13. **Integration Tests - AsyncEngineE2eTest.php**
    - Test complete flow: User sends message → job processes → tokens stream → UI updates
    - Test failure recovery: Failed job retry succeeds
    - Test concurrent jobs: Multiple jobs processed simultaneously
    - Test WebSocket failover: Switch to polling and back
    - Test memory extraction: Memories extracted, vectorized, stored
    - Test health monitoring: System health monitored continuously

14. **Documentation - ASYNCENGINE_SETUP.md**
    - Prerequisites: Redis, Reverb requirements
    - Installation steps: npm install, composer install
    - Configuration: `.env` setup, database migrations
    - Running: `php artisan reverb:start`, `php artisan queue:work`
    - Horizon dashboard: Accessing at `/horizon`
    - Monitoring: Health checks and metrics

15. **Documentation - ASYNCENGINE_TESTING.md**
    - Manual testing procedures:
      1. Start Reverb server and queue workers
      2. Load app and verify Echo connection
      3. Send message and observe token-by-token streaming
      4. Monitor progress via GlobalJobMonitor component
      5. Test disconnection and fallback to polling
      6. Check Horizon for job processing
      7. Verify Pinecone storage via metrics
    - Troubleshooting guide:
      - WebSocket connection failures
      - Job processing delays
      - Failed job recovery
      - Reverb server issues
      - Redis connection problems

## ✅ Final Verification Checklist
- [ ] All unit tests pass (9 test files, 40+ tests)
- [ ] All feature tests pass (8 test files, 50+ tests)
- [ ] Integration test passes (complete E2E flow)
- [ ] Code coverage > 80% for job and service classes
- [ ] No regressions in existing API tests
- [ ] WebSocket functionality works end-to-end
- [ ] Event broadcasting works with proper payload sanitization
- [ ] Job chaining executes correctly
- [ ] DLQ retry mechanism works
- [ ] Health checks return accurate system status
- [ ] Documentation complete and accurate
- [ ] Manual testing procedures verified
- [ ] All database migrations apply cleanly
- [ ] Redis connection working
- [ ] Reverb server can start without errors
- [ ] Queue workers process jobs without errors
- [ ] No console errors or deprecation warnings
- [ ] Load testing: System handles concurrent users
