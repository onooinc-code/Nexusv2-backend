# 🎯 TASK: UP-003 - Task 7: Controller Integration & Job Dispatching
- **Status:** ✅ COMPLETED
- **Dependencies:** Task 2 (Core Job Classes Implementation), Task 4 (Event Refactoring & Broadcasting Implementation)

## 1. Objective
Update existing controllers to dispatch jobs to queues instead of processing requests synchronously. Modify API endpoints to return immediate responses and dispatch long-running operations as background jobs. Ensure events are fired at appropriate points for WebSocket listeners.

## 2. Files to Create/Modify
- `app/Http/Controllers/ConversationController.php`: Modify sendMessage to dispatch ProcessAiInferenceJob
- `app/Http/Controllers/MemoryController.php`: Modify indexMemory to use job chaining for extraction and vectorization
- `app/Http/Controllers/WebhookController.php`: Modify to dispatch jobs for external message handling
- `app/Http/Controllers/AiModelController.php`: Modify execute method to dispatch ExecuteAiModelJob

## 3. Implementation Steps
1. **Update ConversationController::sendMessage()**
   - Current behavior: Likely calls LLM API synchronously and returns response
   - New behavior:
     - Create Message record with status `pending`
     - Return immediate response: `{message_id, status: 'pending', conversation_id}`
     - Dispatch ProcessAiInferenceJob to `llm-inference` queue:
       ```php
       ProcessAiInferenceJob::dispatch($conversationId, $messageId, $prompt, $modelId, $providerId)
       ```
     - Fire MessageSent event for user's message:
       ```php
       event(new MessageSent($conversationId, $userMessageId, $user->name, $messageContent));
       ```
   - The job will handle token streaming via events (Task 2)
   - Handle errors: If job fails, update Message status to `failed` and fire error event

2. **Update MemoryController::indexMemory()**
   - Current behavior: Likely extracts and vectorizes memory synchronously
   - New behavior:
     - Return immediate response with extraction job ID
     - Use job chaining via Bus:
       ```php
       Bus::chain([
         new ExtractMemoryJob($conversationId),
         new VectorizeMemoryJob($memoryId, $content),
         new SaveToPineconeJob($memoryId, $vector, $metadata),
       ])->dispatch();
       ```
     - Fire BatchProgressUpdated event as each job completes (jobs dispatch it)
     - Return response: `{batch_id, status: 'processing', job_count: 3}`
   - Handle errors: Catch batch failures and fire error event

3. **Update WebhookController::handleIncomingMessage() or similar**
   - Current behavior: Processes incoming webhooks synchronously
   - New behavior:
     - Extract and validate webhook payload
     - Create Message record with source `webhook`
     - Dispatch appropriate job based on message type:
       - For chat messages: Dispatch ProcessAiInferenceJob
       - For integrations: Dispatch ExtractMemoryJob or specialized job
     - Return 202 Accepted response immediately
     - Fire appropriate event for downstream listeners

4. **Update AiModelController::execute()**
   - Current behavior: Calls model execution synchronously
   - New behavior:
     - Create execution record with status `queued`
     - Return response with execution_id and status
     - Dispatch ProcessAiInferenceJob with appropriate parameters
     - Fire WorkflowStepCompleted event when job completes

5. **Error Handling & Resilience**
   - Implement JobFailed event listener to:
     - Update associated record status to `failed`
     - Fire error event on appropriate channel
     - Log failure details for admin monitoring
   - Use idempotency keys to prevent duplicate processing
   - Ensure no sensitive data passed to jobs (use IDs, not full models)

6. **Response Format Standardization**
   - Immediate synchronous response format:
     ```json
     {
       "id": "uuid",
       "status": "pending|processing|queued",
       "created_at": "timestamp",
       "_ws_channel": "conversation.{id}" // For client to know which channel to listen
     }
     ```
   - Allows frontend to know which Echo channel to listen for updates

## ✅ Final Verification Checklist
- [ ] ConversationController::sendMessage() dispatches ProcessAiInferenceJob
- [ ] MemoryController::indexMemory() uses job chaining
- [ ] WebhookController returns 202 Accepted immediately
- [ ] AiModelController::execute() dispatches job and returns immediately
- [ ] All endpoints return appropriate status codes and response formats
- [ ] Jobs dispatched to correct queues (llm-inference for AI calls, default for others)
- [ ] Events fired at appropriate points (MessageSent, WorkflowStepCompleted, etc.)
- [ ] No full Eloquent models passed to jobs (only IDs)
- [ ] Error responses include appropriate status codes (400, 401, 422, 500)
- [ ] Idempotency keys used where applicable
- [ ] Response includes channel information for Echo subscription
- [ ] Long-running operations no longer block HTTP response
- [ ] Controller tests updated to verify job dispatching
