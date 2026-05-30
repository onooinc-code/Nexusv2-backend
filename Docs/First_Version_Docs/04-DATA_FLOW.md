# 04 - Data Flow (Detailed)

Purpose
- Describe canonical synchronous and asynchronous flows, error handling, idempotency, priority/queuing, and storage schemas for tasks and memory fragments.

Common headers and metadata
- `Authorization: Bearer <token>`
- `X-Trace-Id: <uuid>`
- `X-Idempotency-Key: <string>`
- `X-Request-Source: <service|gateway|external>`

1) Inbound Message Flow (detailed)

Sequence:
1. External channel (WAHA/webhook) sends inbound payload to `WebhookHub`.
2. `WebhookHub` verifies signature and normalizes to internal `InboundMessage` DTO and POSTs to `WorkflowsAndTasksHub` or `AgentsHub` (`POST /api/v1/webhooks/ingest`).
3. `WorkflowsAndTasksHub` enqueues a `Task` (fast path) or starts `ContextAssemblyPipeline` (sync path) depending on routing rules from `SettingsHub`.
4. `ContextAssemblyPipeline` calls `ContactsHub` (`GET /api/v1/contacts/{id}`), `MemoryHub` (`POST /api/v1/memory/search`), and `SettingsHub` to assemble `Context`.
5. Pipeline uses `PromptBuilder` to assemble prompt and calls `AiModelsHub` (`POST /api/v1/models/request`).
6. On response, `ResponseQualityEngine` evaluates; if passed, `ResponseDeliveryPipeline` formats and instructs `NotificationHub` to send outbound messages.
7. Pipeline emits events: `message.processed`, `memory.extracted` and writes async extraction results to `MemoryHub`.

Notes:
- Use `X-Idempotency-Key` for `POST /api/v1/tasks` and `POST /api/v1/memory/write`.
- For high-throughput channels, prefer async enqueue + worker pools to keep API latency low.

2) Task Execution Flow (long-running & scheduled)

Sequence:
1. Task record persisted in `tasks` table with `status: queued` and `idempotency_key`.
2. Scheduler or worker fetches task from priority queue (Redis stream/Kafka topic) and atomically claims it.
3. Worker creates a local execution checkpoint and runs Pipeline steps; intermediate state written to `task_checkpoints` table.
4. On each step completion emit `task.step.completed` event. On success emit `task.completed` and mark task record.
5. On failure, worker records error, increments retry count, and either requeues (backoff) or moves to `dead_letter_tasks` for manual review.

Best practices:
- Use Redis Streams/Kafka for exact-once or at-least-once semantics with consumer groups.
- Prefer small, single-responsibility workers to allow independent scaling.

3) Memory Write & Semantic Storage Flow

Write schema (example):
- Table `memories`:
  - `id` (uuid), `contact_id` (uuid), `type` (enum), `content` (json), `source_hub` (string), `source_id` (string), `confidence` (float), `created_at`, `updated_at`, `deleted_at` (nullable), `schema_version` (int)
- Vector store entry (Pinecone): key=`memory:{id}`, vector, metadata `{ contact_id, type, timestamp }`

Sequence:
1. Pipeline produces `MemoryFragment` (content + provenance).
2. `MemoryHub` receives `POST /api/v1/memory/write` with `X-Idempotency-Key`.
3. `MemoryHub` persists JSON fragment to `memories` table and, if semantic, computes embeddings (via `AiModelsHub`) and upserts vector into Pinecone.
4. Emit `memory.created` event with `memory_id` and vector status.

Consistency model:
- Persists canonical record first (SQL) then writes vector; use outbox pattern to ensure event + vector write consistency where possible.

4) Error Handling & Retry Patterns

Transient Errors (network/provider rate-limit):
- Retry with exponential backoff and jitter (max 5 attempts).
- Track provider error rates and activate circuit-breaker for degraded providers.

Permanent Errors (invalid input/schema mismatch):
- Fail fast, return 4xx to caller, log full context to `LogsHub`, and optionally create a remediation task for manual review.

Poison and Dead-letter handling:
- After N retries, push to `dead_letter_queue` with full payload and diagnostics. Provide UI in `LogsHub` for replay/manual fix.

Idempotency
- Store `idempotency_key` and the previous response; return stored response on repeat requests.

5) Prioritization & Throttling

- Task priority: `critical`, `high`, `normal`, `low` — use separate queues or priority ordering in Redis streams.
- Rate limiting per client and per provider enforced at `AiModelsHub` and API Gateway.

6) Observability & Telemetry

- Required traces: inbound webhook ID, task id, memory id, provider call id.
- Metrics to emit: API latency, pipeline step latency, provider success/failure rate, queue depth, consumer lag.

7) Security & Compliance in Flows

- Sanitize PII before storing in logs; use field encryption for `content` when `type` ∈ sensitive types.
- Support data erasure endpoint: on `DELETE /api/v1/contacts/{id}/erase` cascade mark memories as deleted and remove vectors (or zero-out vectors) and emit `data.erasure` event.

8) Sample HTTP request/response (Context Assembly)

Request:
```
POST /api/v1/tasks
Headers: Authorization, X-Trace-Id, X-Idempotency-Key
Body: {
  "workflow_id": "context_assembly_v1",
  "inputs": { "message": "Hello, can you remind me about my meeting?", "contact_id": "uuid-123" },
  "mode": "async"
}
```

Response (202 Accepted):
```
{ "data": { "task_id": "task-456" }, "meta": { "queued_at": "iso" } }
```

9) Sagas & Outbox for cross-service consistency

- For multi-service workflows that require atomic-seeming behavior, use Sagas: each step publishes a compensating action on failure.
- Use DB Outbox pattern to write events in same transaction as state changes, then an outbox worker publishes to the broker and marks published.

10) Operational Playbooks (short)

- High queue depth: add worker replicas, confirm DB connection pool sizes, check provider quotas.
- Slow model responses: return fallback response from cache or use lower-cost model; flag the provider in `AiModelsHub` health.
- Data loss suspicion: freeze writes, use read-replicas for audits, and run `memories` vs `vectors` reconciliation job.
# 04 - Data Flow

Key flows described briefly for implementation clarity.

Inbound Message Flow (example: WhatsApp inbound):
1. Edge/API receives webhook from WAHA (WebhookHub) and validates signature.
2. WebhookHub forwards normalized event to `WorkflowsAndTasksHub` or `AgentsHub` depending on routing rules.
3. WorkflowsHub enqueues a Task (fast path) or starts a Pipeline for multi-step extraction.
4. Pipeline calls AiModelsHub for NLU (intent, entities, embeddings) and MemoryHub for context retrieval.
5. Engines produce outputs; Builders assemble response; NotificationHub sends reply.

Task Execution Flow (scheduled/long-running):
- Task persisted in `tasks` table, scheduler enqueues execution job, worker runs Pipeline and updates task status with audit logs in LogsHub.

Memory Write Flow:
- Extraction produces structured memory fragments with provenance: `{ contact_id, type, content, source, confidence, timestamp }`.
- Store canonical fields in MySQL and vectors in Pinecone (if semantic) via MemoryHub API.

Failure & Retry:
- Use exponential backoff for transient provider errors, circuit-breaker for provider saturation, and poison-queue for repeated failures with human review via LogsHub.
