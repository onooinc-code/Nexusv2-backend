# 02 - Memory Tables

Purpose
- Define schema for storing memory fragments, versioning, vector references, and consolidation metadata used by `MemoryHub`.

Design principles
- Use UUIDs for memory ids; store canonical JSON fragments in SQL for auditability.
- Keep vector store (Pinecone) references separately to allow independent scaling and reindexing.
- Support versioned memories and soft-deletes; retention policies configurable per `type`.

Primary tables
- `memories`
- `memory_versions`
- `memory_vectors`
- `memory_sources`
- `memory_consolidations`

memories
- `id` CHAR(36) PK
- `contact_id` CHAR(36) FK -> contacts.id
- `type` VARCHAR(64) (e.g., belief, preference, fact, interaction)
- `canonical_content` JSON (normalized canonical form)
- `latest_version_id` CHAR(36) FK -> memory_versions.id
- `confidence` FLOAT
- `provenance` JSON (source_hub, source_id, extractor)
- `created_at`, `updated_at`, `deleted_at`, `schema_version` INT

memory_versions
- `id` CHAR(36) PK
- `memory_id` CHAR(36) FK -> memories.id
- `content` JSON (raw extracted fragment)
- `extraction_meta` JSON (model_used, prompt_ref, confidence_breakdown)
- `created_at`

memory_vectors
- `id` CHAR(36) PK
- `memory_id` CHAR(36) FK -> memories.id
- `vector_key` VARCHAR(255) (e.g., memory:{id})
- `index_name` VARCHAR(255) (pinecone index name)
- `dimension` INT
- `created_at`, `deleted_at`

memory_sources
- `id` CHAR(36) PK
- `memory_id` CHAR(36) FK
- `source_hub` VARCHAR
- `source_event_id` VARCHAR
- `raw_payload` JSON
- `created_at`

memory_consolidations
- `id` CHAR(36) PK
- `contact_id` CHAR(36)
- `summary` JSON (consolidated summary)
- `method` VARCHAR (e.g., nightly_summary, on_write)
- `created_at`, `updated_at`

Vector store integration notes
- Vector keys should be deterministic: `memory:{memory_id}`.
- When writing a memory:
  1. Persist `memory_versions` and update `memories.latest_version_id` in a transaction.
  2. Push embedding request to `AiModelsHub`; on success upsert into Pinecone and create `memory_vectors` row.
  3. Use outbox to publish `memory.created` with `memory_id` and `vector_status`.

Retention & pruning
- Configure retention per `type`. Example: `interaction` 90 days, `belief` 2 years, `ephemeral` 24 hours.
- Implement background `prune_memories` job that marks `deleted_at` and removes vectors (or zero-out) from Pinecone.

Indexing & performance
- Index `memories(contact_id, type, created_at)` for fast retrieval.
- Use partitioning by year for very large `memory_versions` tables.

Example migration (memories + vectors)

```sql
CREATE TABLE memories (
  id CHAR(36) PRIMARY KEY,
  contact_id CHAR(36) NOT NULL,
  type VARCHAR(64) NOT NULL,
  canonical_content JSON,
  latest_version_id CHAR(36),
  confidence FLOAT,
  provenance JSON,
  schema_version INT DEFAULT 1,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at DATETIME NULL
);

CREATE TABLE memory_vectors (
  id CHAR(36) PRIMARY KEY,
  memory_id CHAR(36) NOT NULL,
  vector_key VARCHAR(255) NOT NULL,
  index_name VARCHAR(255) NOT NULL,
  dimension INT,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  deleted_at DATETIME NULL
);
CREATE INDEX idx_memory_contact_type ON memories (contact_id, type, created_at);
CREATE INDEX idx_memory_vectors_memory_id ON memory_vectors (memory_id);
```

Operational considerations
- Reindex strategy: add `reindex_jobs` table for tracking full/partial reindex runs.
- For vector deletion use idempotent deletes and verify by fetching vector metadata after deletion.
- Monitor vector-store quotas and costs in `AiModelsHub` metrics.

Next steps
- Create `03-AUDIT_TABLES.md` for audit, access logs, and compliance trails, then `04-INDEXES_AND_PERFORMANCE.md`.
