# 🎯 TASK: UP-003 - Task 4: Event Refactoring & Broadcasting Implementation
- **Status:** 🔴 PENDING
- **Dependencies:** Task 3 (Event Broadcasting Infrastructure)

## 1. Objective
Refactor existing events to implement ShouldBroadcast/ShouldBroadcastNow interfaces with secure payload sanitization. Create new events for real-time streaming and job monitoring. All events must define private channels and limit broadcast data to prevent model leakage.

## 2. Files to Create/Modify
- `app/Events/MessageSent.php`: Refactor to implement ShouldBroadcast
- `app/Events/MessageReceived.php`: Refactor to implement ShouldBroadcast
- `app/Events/WorkflowStarted.php`: Refactor to implement ShouldBroadcast
- `app/Events/WorkflowStepCompleted.php`: Refactor to implement ShouldBroadcast
- `app/Events/AgentExecuted.php`: Refactor to implement ShouldBroadcast
- `app/Events/TokenStreamed.php`: Create new for LLM token streaming
- `app/Events/MessageCompleted.php`: Create new for message completion
- `app/Events/BatchProgressUpdated.php`: Create new for job batch progress
- `app/Events/MemoryVectorized.php`: Create new for vectorization completion
- `app/Events/MemoryIndexed.php`: Create new for Pinecone indexing
- `app/Events/MemoriesExtracted.php`: Create new for memory extraction completion

## 3. Implementation Steps
1. **Refactor MessageSent Event**
   - Implement `ShouldBroadcastNow` (for real-time delivery)
   - Constructor: Accept `$conversationId`, `$messageId`, `$senderName`, `$content`
   - `broadcastOn()`: Return `new PrivateChannel("conversation.{$this->conversationId}")`
   - `broadcastWith()`: Return only `['id' => $messageId, 'sender' => $senderName, 'content' => $content, 'timestamp' => now()]`
   - Do NOT include full Message model or User model

2. **Refactor MessageReceived Event**
   - Implement `ShouldBroadcastNow`
   - Constructor: Accept `$conversationId`, `$messageId`, `$agentId`, `$responseData`
   - `broadcastOn()`: Return `new PrivateChannel("conversation.{$this->conversationId}")`
   - `broadcastWith()`: Return only `['id' => $messageId, 'agent_id' => $agentId, 'data' => $responseData]`

3. **Refactor WorkflowStarted Event**
   - Implement `ShouldBroadcast`
   - Constructor: Accept `$workflowId`, `$userId`, `$workflowName`
   - `broadcastOn()`: Return `new PrivateChannel("workflow.{$this->workflowId}")`
   - `broadcastWith()`: Return `['id' => $workflowId, 'name' => $workflowName, 'started_at' => now()]`

4. **Refactor WorkflowStepCompleted Event**
   - Implement `ShouldBroadcast`
   - Constructor: Accept `$workflowId`, `$stepNumber`, `$status`, `$result`
   - `broadcastOn()`: Return `new PrivateChannel("workflow.{$this->workflowId}")`
   - `broadcastWith()`: Return `['step' => $stepNumber, 'status' => $status, 'result' => $result]`

5. **Refactor AgentExecuted Event**
   - Implement `ShouldBroadcast`
   - Constructor: Accept `$agentId`, `$executionId`, `$status`, `$output`
   - `broadcastOn()`: Return `new PrivateChannel("agent.execution.{$executionId}")`
   - `broadcastWith()`: Return `['agent_id' => $agentId, 'status' => $status, 'output_summary' => $output]` (truncate large outputs)

6. **Create TokenStreamed Event**
   - Implement `ShouldBroadcastNow`
   - Constructor: Accept `$conversationId`, `$messageId`, `$token`
   - `broadcastOn()`: Return `new PrivateChannel("conversation.{$this->conversationId}")`
   - `broadcastWith()`: Return `['message_id' => $messageId, 'token' => $token, 'timestamp' => now()]`
   - Purpose: Stream LLM response tokens in real-time to UI

7. **Create MessageCompleted Event**
   - Implement `ShouldBroadcastNow`
   - Constructor: Accept `$conversationId`, `$messageId`, `$finalMessage`
   - `broadcastOn()`: Return `new PrivateChannel("conversation.{$this->conversationId}")`
   - `broadcastWith()`: Return `['message_id' => $messageId, 'complete' => true, 'timestamp' => now()]`

8. **Create BatchProgressUpdated Event**
   - Implement `ShouldBroadcastNow`
   - Constructor: Accept `$batchId`, `$progress`, `$total`, `$status`
   - `broadcastOn()`: Return `new PrivateChannel("job.batch.{$this->batchId}")`
   - `broadcastWith()`: Return `['progress' => $progress, 'total' => $total, 'status' => $status, 'percentage' => ($progress/$total)*100]`

9. **Create MemoryVectorized Event**
   - Implement `ShouldBroadcast`
   - Constructor: Accept `$memoryId`, `$vectorDimensions`
   - `broadcastOn()`: Return `new PrivateChannel("memory.{$this->memoryId}")`
   - `broadcastWith()`: Return `['memory_id' => $memoryId, 'vectorized' => true, 'dimensions' => $vectorDimensions]`

10. **Create MemoryIndexed Event**
    - Implement `ShouldBroadcast`
    - Constructor: Accept `$memoryId`, `$pineconeId`
    - `broadcastOn()`: Return `new PrivateChannel("memory.{$this->memoryId}")`
    - `broadcastWith()`: Return `['memory_id' => $memoryId, 'indexed' => true]`

11. **Create MemoriesExtracted Event**
    - Implement `ShouldBroadcast`
    - Constructor: Accept `$conversationId`, `$extractedCount`
    - `broadcastOn()`: Return `new PrivateChannel("conversation.{$this->conversationId}")`
    - `broadcastWith()`: Return `['extracted_count' => $extractedCount, 'timestamp' => now()]`

## ✅ Final Verification Checklist
- [ ] All 5 existing events refactored with ShouldBroadcast implementation
- [ ] All 6 new events created with ShouldBroadcastNow for real-time delivery
- [ ] `broadcastOn()` returns appropriate private channels
- [ ] `broadcastWith()` limits data to non-sensitive fields only
- [ ] No full Eloquent models included in broadcast payloads
- [ ] All event constructors accept specific IDs/data (not full models)
- [ ] Channel names follow naming pattern: `{type}.{id}`
- [ ] Events implement appropriate broadcast interface (Now vs standard)
- [ ] No database queries in `broadcastWith()` methods
- [ ] All events properly extend Event base class
