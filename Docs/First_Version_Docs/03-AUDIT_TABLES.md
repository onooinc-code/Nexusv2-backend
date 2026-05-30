# 03 - Audit & Compliance Tables

Purpose
- Track immutable audit trails, access logs, consent/erasure requests, and compliance metadata required for GDPR/PIPEDA/enterprise auditing.

Design principles
- Immutable append-only logs where possible.
- Link audit records to `trace_id`, `user_id` (if present), and `hub` for traceability.
- Support efficient querying for legal requests and retention.
- Minimize PII stored in logs; store identifiers and pointers to encrypted blobs when necessary.

Primary tables
- `audit_trails`
- `access_logs`
- `consent_events`
- `erasure_requests`
- `data_access_requests`

audit_trails
- `id` CHAR(36) PK
- `trace_id` CHAR(36)
- `hub` VARCHAR
- `actor_type` ENUM('system','user','agent')
- `actor_id` CHAR(36) NULL
- `action` VARCHAR (e.g., contact.update, memory.write)
- `object_type` VARCHAR
- `object_id` CHAR(36)
- `payload` JSON (encrypted if contains PII)
- `created_at` DATETIME

access_logs
- `id` CHAR(36) PK
- `user_id` CHAR(36) NULL
- `service` VARCHAR
- `endpoint` VARCHAR
- `method` VARCHAR
- `status` INT
- `ip_address` VARCHAR
- `user_agent` VARCHAR
- `trace_id` CHAR(36)
- `created_at` DATETIME

consent_events
- `id` CHAR(36) PK
- `contact_id` CHAR(36)
- `consent_type` VARCHAR (e.g., marketing,email,analytics)
- `action` ENUM('granted','revoked')
- `source` VARCHAR
- `metadata` JSON
- `created_at` DATETIME

erasure_requests
- `id` CHAR(36) PK
- `contact_id` CHAR(36)
- `requested_by` VARCHAR
- `status` ENUM('requested','in_progress','completed','rejected')
- `scope` JSON (which data types to erase)
- `processed_at` DATETIME NULL
- `created_at` DATETIME

data_access_requests
- `id` CHAR(36) PK
- `contact_id` CHAR(36)
- `requested_by` VARCHAR
- `status` ENUM('requested','fulfilled','rejected')
- `delivery_method` JSON
- `delivered_at` DATETIME NULL
- `created_at` DATETIME

Encryption & storage guidance
- Store any PII in encrypted columns or in a secure blob store; logs should contain pointers (blob_id) and a minimal identifier.
- Keys managed via a secrets manager with rotation.

Retention & legal hold
- Default retention: `access_logs` 90 days, `audit_trails` 2 years, `consent_events` permanent unless erased by legal request.
- Support `legal_hold` flag to prevent deletion; maintain audit of holds.

Example migration (audit_trails)

```sql
CREATE TABLE audit_trails (
  id CHAR(36) PRIMARY KEY,
  trace_id CHAR(36),
  hub VARCHAR(128),
  actor_type VARCHAR(32),
  actor_id CHAR(36),
  action VARCHAR(128),
  object_type VARCHAR(64),
  object_id CHAR(36),
  payload JSON,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX idx_audit_trace ON audit_trails (trace_id);
CREATE INDEX idx_audit_object ON audit_trails (object_type, object_id);
```

Operational notes
- Provide APIs in `LogsHub` to export data for legal requests with signatures and reconciliation checks.
- For erasure: `erasure_requests` should trigger cascade processes that mark or remove data according to policy; log every step to `audit_trails`.

Next steps
- Implement `04-INDEXES_AND_PERFORMANCE.md` with index strategies and partitioning recommendations for large tables.
