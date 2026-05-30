# 02 - Hub Architecture

Purpose:
- Define hub boundaries, public contracts, independence rules, and example endpoints.

Hub principles:
- Autonomy: Each hub owns its data and exposes APIs; no direct DB access across hubs.
- Minimal surface area: Small, versioned REST APIs for interoperability.
- Idempotency: Write endpoints must be idempotent or provide idempotency keys.
- Observability: Each request annotated with trace IDs and caller metadata.

Hub contract template:
- Base path: `/api/v1/{hub}`
- Common headers: `Authorization: Bearer <token>`, `X-Trace-Id`, `X-Request-Source`
- Versioning: Use path versioning (`/v1`) plus semantic schema versions for payloads.
- Error model: `{ code: string, message: string, details?: object }`

Example endpoints (ContactsHub):
- `GET /api/v1/contacts/{id}` — Retrieve canonical contact
- `POST /api/v1/contacts` — Create or upsert contact (idempotent)
- `POST /api/v1/contacts/{id}/beliefs` — Add belief with source metadata

Example endpoints (MemoryHub):
- `POST /api/v1/memory/search` — Semantic search (query, top_k, filters)
- `POST /api/v1/memory/write` — Persist memory fragment (type, source, confidence)
- `GET /api/v1/memory/{id}` — Retrieve memory record with provenance

Deployment guidance:
- Each hub runs as a separate service (Docker/Kubernetes) with its own DB/schema where required.
- Use service mesh or API gateway for routing, mTLS for intra-cluster calls.

Hub contracts (detailed)

Contract rules (applies to all hubs):
- All hubs expose `/health`, `/metrics`, and `/openapi.json` (or equivalent schema).
- Use path versioning: `/api/v1/{hub}/...` and include `schema_version` in payloads where applicable.
- Every write operation must accept `Idempotency-Key` for safe retries.
- All requests must return `X-Trace-Id` in responses and accept `X-Trace-Id` header.

AgentsHub
- Base: `POST /api/v1/agents` — create or upsert agent
	- Body: `{ id?: uuid, name: string, persona: object, tools?: array, settings?: object }`
	- Response: `{ data: { agent_id, version }, meta: { created_at } }`
- `POST /api/v1/agents/{id}/run` — execute agent (sync/async)
	- Body: `{ input: object, mode: "sync" | "async", callback_url?: string }`
	- Response (sync): `{ data: { result, logs } }`
	- Response (async): `{ data: { task_id } }`

MemoryHub
- `POST /api/v1/memory/write` — write memory fragment
	- Body: `{ contact_id?: uuid, type: string, content: object, source: { hub, id }, confidence: number, timestamp?: iso }`
	- Response: `{ data: { memory_id }, meta: { stored_in_vector: boolean } }`
- `POST /api/v1/memory/search` — semantic search
	- Body: `{ query?: string, vector?: number[], top_k?: number, filters?: object }`
	- Response: `{ data: [{ id, score, snippet, source }], meta: { took_ms } }`
- `DELETE /api/v1/memory/{id}` — logical delete (supports GDPR erase semantics)

ContactsHub
- `GET /api/v1/contacts/{id}` — retrieve canonical contact
	- Response: `{ data: { id, canonical_name, identifiers: [], preferences: {}, beliefs: [], profile: {} } }`
- `POST /api/v1/contacts` — create/upsert contact (idempotent)
	- Body: `{ external_ids?: [], name: string, emails?: [], phones?: [], metadata?: {} }`
- `POST /api/v1/contacts/{id}/merge` — merge another contact into canonical
	- Body: `{ source_contact_id: uuid, strategy: "prefer_new" | "prefer_trusted" }`

AiModelsHub
- `POST /api/v1/models/request` — request model inference
	- Body: `{ model_hint?: string, provider_preferences?: object, prompt: object, tokens_limit?: number }`
	- Response: `{ data: { provider, model, response, usage: { tokens, cost } } }`
- `GET /api/v1/providers` — list providers and health

WorkflowsAndTasksHub
- `POST /api/v1/workflows/definition` — register workflow template
	- Body: `{ id?: string, name: string, steps: [{ id, type, handler }], triggers?: [] }`
- `POST /api/v1/tasks` — run workflow or ad-hoc task
	- Body: `{ workflow_id?: string, inputs?: object, schedule?: cron | datetime, mode: "sync" | "async" }`
- `GET /api/v1/tasks/{id}` — task status & logs

SettingsHub
- `GET /api/v1/settings/{key}` — retrieve setting
- `POST /api/v1/settings` — upsert setting
	- Body: `{ key: string, value: any, env?: string, meta?: {} }`

LogsHub
- `POST /api/v1/logs` — ingest structured log/event
	- Body: `{ trace_id, level, hub, message, payload?: object, timestamp }`
- `GET /api/v1/logs/search` — search logs with filters

Supporting hubs (brief contracts)
- WebhookHub: register webhooks `POST /api/v1/webhooks`, deliver events with retries, signature verification.
- NotificationHub: `POST /api/v1/notifications/send` — send multi-channel message (body includes channel, payload, templates, fallback order).
- SchedulerHub: `POST /api/v1/scheduler/jobs` — schedule, `GET /api/v1/scheduler/jobs/{id}` — status.

Schema examples
- Standard error:
	- `{ "code": "ERR_INVALID_INPUT", "message": "Field X is required", "details": { "field":"x" } }`
- Standard success wrapper:
	- `{ "data": <any>, "meta": { "timestamp": "iso", "schema_version": "1" } }`

Versioning & compatibility
- Support `Accept-Version` header for gradual rollouts.
- Deprecation plan: mark semver-major changes in `openapi.json` and support old versions for at least one major release cycle.

