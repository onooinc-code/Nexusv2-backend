# 07 - LogsHub

## Purpose

The LogsHub centralizes logging, audit trails, and observability events across Nexus.
It provides consistent log ingestion, storage, query, and alerting for application, workflow, compliance, and security-related activity.

## Scope

- Structured log ingestion and normalization
- Audit trail collection for hub and workflow activity
- Event correlation and trace context propagation
- Retention policies and log archival
- Search and analytics integration
- Alerting and anomaly detection hooks

## Core Responsibilities

1. Log ingestion and normalization
2. Audit and compliance logging
3. Structured event enrichment
4. Trace and correlation management
5. Retention and archival policies
6. Integration with observability stacks

## Architecture

LogsHub is composed of the following subcomponents:

- `LogCollector`
- `SchemaNormalizer`
- `EventEnricher`
- `TraceCorrelator`
- `StorageManager`
- `QueryAPI`
- `AlertBridge`

### LogCollector

Ingests logs from internal hubs, external services, and workflow executions.

Responsibilities:

- Receive log events from HTTP, messaging, and SDK sources
- Support event buffering and batching
- Validate incoming events against supported schemas
- Protect against log injection and malformed payloads

### SchemaNormalizer

Standardizes log entries across Nexus.

Responsibilities:

- Map vendor-specific log formats to a common schema
- Preserve source metadata and event types
- Normalize timestamps, severity levels, and identities
- Tag logs with workspace, workflow, and tenant context

### EventEnricher

Attaches derived metadata to logs.

Responsibilities:

- Add trace IDs, workflow IDs, request IDs, and session details
- Resolve user, tenant, and resource context
- Attach policy, compliance, and classification labels
- Add enrichment for cost, provider, and model selection events

### TraceCorrelator

Links logs across distributed workflows.

Responsibilities:

- Propagate trace context through hub calls and async tasks
- Correlate workflow execution events with provider interactions
- Support nested spans and execution timelines
- Expose linked traces for debugging and performance analysis

### StorageManager

Manages retention and archive policies.

Responsibilities:

- Persist logs in primary storage and archive tiers
- Enforce retention windows and compliance holds
- Support deletion or anonymization workflows when required
- Optimize storage by compressing and indexing relevant fields

### QueryAPI

Provides searchable access to log records.

Responsibilities:

- Offer query and filter capabilities for events, traces, and audit records
- Expose paginated results and field projections
- Support saved searches and analytics pipelines
- Integrate with external search engines or analytics stores

### AlertBridge

Routes important log signals to alerting systems.

Responsibilities:

- Detect error spikes, policy violations, and suspicious activity
- Emit notification events to observability and security channels
- Support threshold-based alert rules and dynamic alerts
- Integrate with external alerting platforms

## API Contract

### `POST /logs/events`

Request body:

- `event_id`
- `timestamp`
- `severity`
- `source`
- `workspace_id`
- `tenant_id`
- `workflow_id` (optional)
- `trace_id` (optional)
- `span_id` (optional)
- `message`
- `payload`
- `tags`

Response:

- `status`
- `event_id`
- `received_at`

### `GET /logs/query`

Request params:

- `workspace_id`
- `tenant_id`
- `workflow_id`
- `trace_id`
- `level`
- `start_time`
- `end_time`
- `keywords`
- `tags`

Response:

- `results`
- `total`
- `next_cursor`

### `GET /logs/alerts`

Response:

- `alerts`
- `severity`
- `status`
- `triggered_at`
- `source`

### `POST /logs/retention`

Request body:

- `workspace_id`
- `policy_id`
- `retention_days`
- `archive_tier`
- `compliance_hold`

Response:

- `policy_id`
- `status`
- `effective_at`

## Log Types

- `application`: runtime and operational logs
- `audit`: configuration changes, authorization events, and policy decisions
- `workflow`: workflow lifecycle and step execution logs
- `security`: access, authentication, and anomaly detection logs
- `metrics`: system health, performance, and throughput events
- `provider`: AI provider and model invocation logs

## Correlation and Traceability

- All hubs should propagate consistent `trace_id` and `workflow_id`
- Log entries may include `request_id`, `session_id`, and `step_id`
- Correlation enables end-to-end observability across hub boundaries
- LogsHub should support linking with external tracing systems

## Retention and Compliance

- Support configurable retention per workspace or tenant
- Provide audit-safe retention hold options
- Enable safe deletion or anonymization for data privacy
- Keep immutable audit records for compliance-critical events

## Observability

- Capture ingestion latency and event processing time
- Monitor logging volume, error rates, and backpressure
- Track retention policy enforcement and archive health
- Report alerting and unusual event patterns

## Alerting

- Define alert rules based on severity, frequency, and anomaly detection
- Surface provider failures, workflow outages, and security incidents
- Integrate with incident response and notification pipelines
- Support escalation policies and alert suppression

## Implementation Notes

- Use structured logging with stable fields and event types
- Keep ingestion API lightweight and resilient
- Separate hot log storage from long-term archives
- Enable log forwarding to external observability systems
- Preserve enough context for troubleshooting without overloading storage
