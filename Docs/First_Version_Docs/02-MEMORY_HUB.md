# MemoryHub

Purpose
- Manage all memory operations for Nexus, including storage, retrieval, consolidation, pruning, and semantic search.
- Provide a unified API for Working Memory, Episodic Memory, Semantic Memory, Structured Memory, and Graph Memory.

Scope
- Memory ingestion and persistence
- Memory retrieval and search
- Memory consolidation, summarization, and pruning
- Vector embedding generation and vector store orchestration
- Memory access control and GDPR compliance

Modules
- Memory API Layer: REST endpoints for write, read, search, and delete.
- Ingestion & Extraction: Normalize memory fragments, validate provenance, enqueuing for async persistence.
- Semantic Search: Vector generation and query APIs.
- Memory Consolidation: Summarize, cluster, and merge related fragments.
- Work Memory Cache: Hot context cache for active conversations.
- Pruning & Retention: Apply retention policies and privacy erasure.
- Graph Memory Interface: Represent entity relationships extracted from memories.

API Endpoints
- `POST /api/v1/memory/write`
  - Body: `{ contact_id?: uuid, type: string, content: object, source: { hub: string, id: string }, confidence: number, timestamp?: iso }`
  - Behavior: persist canonical fragment, enqueue embedding creation if semantic.
  - Idempotency: support `X-Idempotency-Key`.

- `POST /api/v1/memory/search`
  - Body: `{ query?: string, vector?: number[], top_k?: number, filters?: object, contact_id?: uuid }`
  - Behavior: return ranked semantic search results and structured matches.

- `GET /api/v1/memory/{id}`
  - Behavior: retrieve a memory record with provenance and vector metadata.

- `POST /api/v1/memory/recall`
  - Body: `{ contact_id: uuid, intent: string, available_tokens: int, filters?: object }`
  - Behavior: return curated context for prompt assembly.

- `DELETE /api/v1/memory/{id}`
  - Behavior: logical delete with GDPR semantics; optionally queue vector deletion.

- `POST /api/v1/memory/prune`
  - Body: `{ contact_id?: uuid, type?: string, older_than?: duration }`
  - Behavior: trigger retention pruning job.

Data models
- MemoryFragment
  - `id`, `contact_id`, `type`, `canonical_content`, `confidence`, `provenance`, `schema_version`, `created_at`, `updated_at`, `deleted_at`
- MemoryVersion
  - `id`, `memory_id`, `content`, `extraction_meta`, `created_at`
- MemoryVector
  - `id`, `memory_id`, `vector_key`, `index_name`, `dimension`, `created_at`, `deleted_at`
- MemorySearchResult
  - `id`, `score`, `snippet`, `source`, `type`, `confidence`

Memory types
- Working Memory: fast, ephemeral context stored in Redis with short TTL (minutes to hours).
- Episodic Memory: sequential event history of interactions; stored in SQL and optionally summarized.
- Semantic Memory: vector-embeddings for meaning-based retrieval.
- Structured Memory: normalized facts and relationships for deterministic lookup.
- Graph Memory: entity/relationship triples used by graph queries and relationship inference.

Runtime behavior
1. `MemoryHub` receives memory write request and validates payload.
2. Persists base fragment to SQL and writes `memory_versions`.
3. If semantic or relevant to context, request embedding creation from `AiModelsHub`.
4. Create/refresh `memory_vectors` row and upsert into the vector store.
5. Emit `memory.created` / `memory.updated` event.
6. On retrieval, combine SQL memory filtering with semantic search results.

Search and retrieval patterns
- Use `contact_id` plus filters to scope queries.
- For prompt recall, prioritize:
  1. High-confidence beliefs/preferences
  2. Recent episodic memories
  3. Semantic hits matching current intent
  4. Structured rules/facts
- Provide `recall` API that returns a token-aware package of memories.

Consolidation and summarization
- Scheduled consolidation runs nightly or after a threshold of new memories.
- Consolidation modes:
  - `summary`: merge related fragments into `memory_consolidations`
  - `dedupe`: collapse duplicate or near-duplicate memories
  - `abstract`: create higher-level insights from raw fragments
- Store consolidation results separately to preserve original fragments.

Pruning & retention
- Configure retention policies by type:
  - `working`: 24h
  - `ephemeral`: 7d
  - `interaction`: 90d
  - `belief`: 2y
  - `preference`: 1y
- Pruning process:
  1. Mark expired memories with `deleted_at`.
  2. Remove or zero-out associated vectors.
  3. Emit `memory.pruned` events.

Privacy & GDPR
- Memory writes with PII should use field-level encryption or secure references.
- `DELETE /api/v1/memory/{id}` and `DELETE /api/v1/contacts/{id}/erase` must cascade to vector deletion and summary removal.
- Maintain audit trail in `audit_trails` for all erasures.

Graph memory
- Support relationship triples: `(subject, predicate, object)` stored in the graph store or SQL table.
- Expose `POST /api/v1/memory/graph/query` and `GET /api/v1/memory/graph/{contact_id}`.
- Use graph memory for relationship inference, path queries, and contact network exploration.

Security & access
- Only authorized hubs or users may write/read memory; enforce via JWT scopes or service credentials.
- Log access attempts and deny unauthorized memory reads.

Observability
- Metrics: memory writes/sec, semantic search latency, vector store error rate, prune job duration.
- Trace: propagate `trace_id` through write and search requests.

Testing
- Unit tests for write/read/search APIs with mocked vector store.
- Contract tests for semantic search result ordering.
- End-to-end tests covering memory lifecycle and prune/erase flows.

Events
- `memory.created`, `memory.updated`, `memory.deleted`, `memory.pruned`, `memory.consolidated`.
- Published to broker and recorded in `outbox` for durability.

Example OpenAPI snippet
```yaml
paths:
  /api/v1/memory/write:
    post:
      summary: Write a memory fragment
      requestBody:
        content:
          application/json:
            schema:
              type: object
              properties:
                contact_id: { type: string, format: uuid }
                type: { type: string }
                content: { type: object }
                source:
                  type: object
                  properties:
                    hub: { type: string }
                    id: { type: string }
                confidence: { type: number }
      responses:
        '200': { description: OK }
```

Next steps
- Draft `ContactsHub` specification and define memory access patterns across hubs.
