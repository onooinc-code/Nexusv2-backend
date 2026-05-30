# 🎯 TASK: UP-003 - Task 2: Core Job Classes Implementation
- **Status:** � IMPLEMENTED - Ready for Testing
- **Dependencies:** Task 1 (Horizon Configuration & Base Job Infrastructure)

## 1. Objective
Implement four core async job classes for LLM inference, memory vectorization, Pinecone storage, and memory extraction. Each job must handle idempotency, model missing scenarios, and rate limits safely. Jobs will be dispatched to appropriate queues (llm-inference for LLM calls, default for others).

## 2. Files to Create/Modify
- `app/Jobs/ProcessAiInferenceJob.php`: New job for LLM inference with streaming support
- `app/Jobs/VectorizeMemoryJob.php`: New job for memory vectorization using embedding models
- `app/Jobs/SaveToPineconeJob.php`: New job for storing vectorized memory to Pinecone
- `app/Jobs/ExtractMemoryJob.php`: New job for extracting structured memory from conversations

## 3. Implementation Steps
1. **ProcessAiInferenceJob**
   - Extend BaseJob class
   - Constructor: Accept `$conversationId`, `$messageId`, `$prompt`, `$modelId`, `$providerId`
   - Queue: `llm-inference` (for performance prioritization)
   - Implementation:
     - Call LLM API using AiModelHub service with streaming support
     - For each token received, dispatch `TokenStreamed` event with `$conversationId`, `$messageId`, `$token`
     - On completion, dispatch `MessageCompleted` event
     - Use idempotency check to prevent duplicate inference
     - Handle rate limits (429 responses) by releasing job with exponential backoff
     - Store inference result in database with metadata (tokens used, cost, latency)
   - Retry: 3 times with exponential backoff
   - Timeout: 600 seconds (10 minutes for long inference calls)

2. **VectorizeMemoryJob**
   - Extend BaseJob class
   - Constructor: Accept `$memoryId`, `$content`
   - Queue: `default`
   - Implementation:
     - Retrieve Memory model using `safelyGetModel(Memory::class, $memoryId)`
     - Use embedding API (OpenAI Embeddings or similar) to generate vector
     - Store vector in `embeddings` column of memory record
     - Dispatch `MemoryVectorized` event with `$memoryId`, `$vectorDimensions`
     - Use idempotency to prevent re-vectorizing same memory
     - Handle rate limits by releasing with backoff
   - Retry: 2 times
   - Timeout: 120 seconds

3. **SaveToPineconeJob**
   - Extend BaseJob class
   - Constructor: Accept `$memoryId`, `$vector`, `$metadata`
   - Queue: `default`
   - Implementation:
     - Prepare Pinecone record: `{id: $memoryId, values: $vector, metadata: $metadata}`
     - Call Pinecone API (upsert operation) to save vector
     - Update Memory model with `pinecone_id` field
     - Dispatch `MemoryIndexed` event with `$memoryId`
     - Use idempotency to prevent duplicate Pinecone entries
     - Handle Pinecone API rate limits
   - Retry: 2 times
   - Timeout: 60 seconds

4. **ExtractMemoryJob**
   - Extend BaseJob class
   - Constructor: Accept `$conversationId`
   - Queue: `default`
   - Implementation:
     - Retrieve Conversation with messages using `safelyGetModel(Conversation::class, $conversationId)`
     - Use NLP/LLM service to extract key memories/facts from conversation
     - Create Memory model records for each extracted item
     - Chain jobs: Create `SaveToPineconeJob` chain after memory extraction
     - Dispatch `MemoriesExtracted` event with count of extracted memories
     - Use idempotency to prevent duplicate extraction
   - Retry: 2 times
   - Timeout: 300 seconds

## ✅ Final Verification Checklist
- [ ] All 4 job classes created with BaseJob extension
- [ ] Each job implements `handle()` method with complete logic
- [ ] Idempotency checks implemented in all jobs
- [ ] Rate limit handling uses `release()` instead of `sleep()`
- [ ] Model missing handling via `deleteWhenMissingModels()` or `safelyGetModel()`
- [ ] Events dispatched at appropriate points in job execution
- [ ] Queue assignments correct (llm-inference for ProcessAiInferenceJob, default for others)
- [ ] Retry and timeout values appropriate for each job type
- [ ] Jobs can be dispatched and processed via `php artisan queue:work`
- [ ] Failed jobs properly captured without breaking dependent features
