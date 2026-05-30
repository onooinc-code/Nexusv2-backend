# 04 - Indexes & Performance

Purpose
- Provide indexing, partitioning, caching, and database tuning guidance to meet Nexus SLOs (p95 <2s) and scale to thousands of contacts.

General indexing principles
- Index columns used in WHERE, JOIN, ORDER BY, and GROUP BY.
- Prefer composite indexes that match query patterns (left-most prefix).
- Avoid overly broad indexes; monitor index size and maintenance cost.
- Use covering indexes for very hot read paths to avoid lookups.

Recommended indexes (from core schema)
- `contacts`: `idx_contacts_name (canonical_name)` and partial index on `deleted_at`.
- `contact_identifiers`: unique index on (`type`, `value`).
- `tasks`: composite index on (`status`, `priority`, `scheduled_at`) for scheduler queries.
- `memories`: composite index on (`contact_id`, `type`, `created_at`) for recent memory retrieval.
- `memory_vectors`: index on `memory_id` and `index_name`.
- `audit_trails`: index on (`object_type`, `object_id`) and `trace_id` for fast lookup.
- `outbox`: index on `published` for publisher scans.

Partitioning strategies
- Partition large append-only tables by time (e.g., `memories`, `memory_versions`, `logs`) using RANGE on `created_at` or YEAR-based partitions.
- Use hash partitioning for extremely large tables when there's a natural partition key (e.g., `contact_id`) to distribute IO.

Query optimization tips
- Use `EXPLAIN ANALYZE` to inspect slow queries.
- Avoid `SELECT *`; select only needed columns.
- For large result sets, use cursor-based pagination (`WHERE (created_at,id) < (last_ts,last_id) LIMIT 50`).
- Use `JOIN` only when necessary; prefer pre-joined denormalized snapshots for read-heavy paths.

Caching recommendations
- Redis for working memory and hot contact profiles (`contact:{id}:profile`). Keep TTLs short (e.g., 24h) and invalidate on writes.
- Use a two-layer cache for AiModelsHub responses: short-term (Redis) and mid-term (S3 cache blobs) for larger responses.
- Cache semantic search top-k results with hashed query+filters key; invalidate when related memories change.

Vector store performance
- Batch embeddings: group texts into batches to amortize API calls and reduce latency.
- Use efficient metadata filters in Pinecone (contact_id) to narrow search space.
- Monitor index dimension and cost; reduce embedding dimension if cost-constrained.

Connection pool & DB tuning
- Set MySQL `innodb_buffer_pool_size` to ~60-75% of available memory on dedicated DB servers.
- Tune `max_connections`, `wait_timeout`, and `innodb_thread_concurrency` according to workload.
- Use connection pooling (e.g., ProxySQL, pgbouncer for Postgres) or app-level pools.

Read scaling & replicas
- Use read replicas for heavy reporting/analytics queries; direct replicas via read-only DB connections.
- Route real-time writes to primary; stale reads should be acceptable for non-critical UI with TTL-based cache fallback.

Background jobs & batching
- Batch writes to vector store and outbox publishing to reduce per-item overhead.
- Use bulk upserts for `memory_versions` and `memory_vectors` when reindexing.

Monitoring & alerts
- Track slow queries (query time > 200ms), connection pool saturation, replica lag, and missing indexes.
- Alert on `table_scans` increase and `full_table_lock` occurrences.

Maintenance tasks
- Regularly run `ANALYZE TABLE` and `OPTIMIZE TABLE` for fragmented tables (schedule during low-traffic windows).
- Implement automated partition pruning and archive old partitions to S3 for cold data.

Laravel-specific tips
- Use Eloquent `with()` to eager load relationships and avoid N+1 queries.
- Use chunking (`chunkById`) for processing large tables in background jobs.
- Use database transactions for multi-step writes; prefer `DB::beginTransaction()` / `commit()`.

Testing & benchmarking
- Benchmark endpoints with realistic payloads (wrk/hey/vegeta) and simulate provider latency.
- Use explain plans and slow-query logs to iterate on indexes.

Cost optimization
- Monitor index and vector-store costs; use TTL-based vector pruning and cold storage for old vectors.
- Consider adding an ingestion sampling rate for low-value channels to control embedding costs.

Next steps
- Implement index creation scripts and add DB health dashboards (Grafana) for ongoing monitoring.
