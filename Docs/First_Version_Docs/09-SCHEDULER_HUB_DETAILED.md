# SchedulerHub — Detailed Specification

## Purpose
Centralized hub for scheduling and managing background jobs: message scheduling, recurring workflows, timed triggers, and ad-hoc job execution. Must be robust, observable, and support cross-hub integrations.

## API Surface (REST + optional GraphQL)
- `GET /api/v1/scheduler/jobs` — list jobs (filters: status, owner, next_run, tag)
- `POST /api/v1/scheduler/jobs` — create job (payload: type, payload, schedule, owner_id, priority, idempotency_key)
- `GET /api/v1/scheduler/jobs/{id}` — job detail + history
- `PUT /api/v1/scheduler/jobs/{id}` — update schedule or payload
- `POST /api/v1/scheduler/jobs/{id}/run` — trigger manual run
- `POST /api/v1/scheduler/jobs/{id}/cancel` — cancel pending job
- `POST /api/v1/scheduler/claim` — worker claim endpoint for batching jobs (atomic)
- `POST /api/v1/scheduler/{id}/heartbeat` — worker heartbeat for long-running jobs
- `GET /api/v1/scheduler/stats` — health & metrics

## Data Model: `scheduler_jobs` table
- id (uuid) PK
- owner_id (nullable)
- type (string) — e.g., message_send, workflow_dispatch
- payload (json) — job-specific data
- schedule (cron | rrule | timestamp) — normalized schedule expression
- next_run_at (datetime)
- last_run_at (datetime)
- status (enum: pending|running|failed|completed|cancelled)
- attempts (int)
- max_attempts (int)
- backoff_strategy (json) — e.g., {type: exponential, base: 2, max_delay: 3600}
- priority (int)
- idempotency_key (string)
- locked_by (nullable string)
- locked_at (nullable datetime)
- created_at, updated_at

Indexes: `next_run_at`, `status`, composite (`status`, `next_run_at`, `priority`)

## Worker Claiming & Processing Flow
1. Worker polls `POST /api/v1/scheduler/claim` with capacity and capability filters.
2. Server returns a batch of jobs and atomically sets `locked_by` and `locked_at` (use DB transaction and `FOR UPDATE SKIP LOCKED` when supported).
3. Worker acknowledges and begins processing; it calls `heartbeat` periodically for long jobs.
4. On success, worker `POST` job result to `/jobs/{id}/complete` (internal endpoint) and updates `last_run_at`, resets `next_run_at` (if recurring) and sets status.
5. On failure, server applies backoff_strategy to compute `next_run_at`, increments attempts, and sets `failed` or `pending` based on attempts left.
6. If worker crashes without completing, a reaper process reclaims locks older than a configured threshold and returns jobs to pending.

## Concurrency & Atomicity
- Prefer DB-level claim using `SELECT ... FOR UPDATE SKIP LOCKED` to avoid double-processing.
- Use idempotency_key and job-run UUIDs to make job handlers idempotent.

## Retry and Backoff
- Default: exponential backoff with jitter, max attempts 5. Allow job-level override via `max_attempts` and `backoff_strategy`.

## Monitoring & Observability
- Emit metrics: `scheduler.jobs.claimed`, `scheduler.jobs.completed`, `scheduler.jobs.failed`, `scheduler.lock.reclaimed`, queue depth per job type.
- Logs: structured JSON with job_id, run_id, start_ts, end_ts, worker_id, error_trace.
- Dashboard: show next-run heatmap, worker pool health, longest-running jobs, retry loops.

## Web UI
- Job list with filters, inline quick actions (Run now, Cancel, Edit schedule).
- Cron/Rule builder modal (human-friendly, preview of next 10 run times).
- Job detail view with run history, logs and raw payload inspector.

## Security & RBAC
- API scoped: `scheduler.read`, `scheduler.write`, `scheduler.run`.
- `Run now` and `edit` actions limited to job owner or admin roles.

## Integration Points
- Composer scheduling in Conversation Interface enqueues a `message_send` job with channel metadata and message payload.
- Hubs (Analytics, Notifications) can create recurring reports via SchedulerHub.

## Implementation tasks (small)
- Create `scheduler_jobs` migration and Eloquent model with appropriate indexes.
- Implement claim endpoint using `SKIP LOCKED` queries and DB transactions.
- Add worker connector (artisan command) that polls claim endpoint and executes jobs with heartbeat support.
- Add webhook/logging sink to persist run logs per job and expose via job detail API.
- Add basic UI: Job list, cron-builder modal, job detail view.
- Write integration tests ensuring claim semantics and retry/backoff behavior.
