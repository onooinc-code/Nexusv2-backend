# 01 - Core Tables

Purpose
- Define the foundational relational schema used by multiple hubs: contacts, agents, tasks, settings, providers, logs, outbox.

Design principles
- Small, focused tables with explicit foreign keys.
- Use UUIDs for primary keys to simplify cross-hub references.
- Soft deletes (`deleted_at`) and `schema_version` for forward compatibility.
- Outbox pattern for cross-service event publishing.

Tables (overview)
- `contacts`
- `contact_identifiers`
- `agents`
- `tasks`
- `task_checkpoints`
- `settings`
- `providers`
- `api_keys`
- `logs`
- `memories` (basic reference — detailed in Memory tables)
- `outbox`
- `dead_letter_events`

Schema details (columns & notes)

contacts
- `id` UUID PK
- `canonical_name` VARCHAR
- `profile` JSON (denormalized profile snapshot)
- `preferences` JSON
- `emotional_baseline` JSON
- `created_at`, `updated_at`, `deleted_at`, `schema_version` INT

contact_identifiers
- `id` UUID PK
- `contact_id` UUID FK -> contacts.id
- `type` ENUM('phone','email','external')
- `value` VARCHAR (normalized)
- `trusted` BOOL

agents
- `id` UUID PK
- `name` VARCHAR
- `persona` JSON
- `tools` JSON
- `version` INT
- `status` ENUM('idle','running','failed')

tasks
- `id` UUID PK
- `workflow_id` VARCHAR
- `mode` ENUM('sync','async')
- `status` ENUM('queued','running','completed','failed','dead')
- `priority` ENUM('critical','high','normal','low')
- `inputs` JSON
- `result` JSON
- `attempts` INT
- `idempotency_key` VARCHAR
- `scheduled_at` DATETIME
- `created_at`, `updated_at`

task_checkpoints
- `id` UUID PK
- `task_id` UUID FK -> tasks.id
- `step` VARCHAR
- `state` JSON
- `created_at`

settings
- `key` VARCHAR PK
- `value` JSON
- `env` VARCHAR
- `created_at`, `updated_at`

providers
- `id` UUID PK
- `name` VARCHAR
- `type` ENUM('ai','messaging','storage')
- `meta` JSON (rate limits, regions)
- `health` JSON

api_keys
- `id` UUID PK
- `provider_id` UUID FK -> providers.id
- `key_name` VARCHAR
- `key_hash` VARCHAR (store hashed/secrets in vault)
- `rotated_at`

logs
- `id` UUID PK
- `trace_id` UUID
- `hub` VARCHAR
- `level` ENUM('debug','info','warn','error')
- `message` TEXT
- `payload` JSON
- `created_at`

memories (reference)
- `id` UUID PK
- `contact_id` UUID
- `type` VARCHAR
- `content` JSON
- `confidence` FLOAT
- `created_at`, `deleted_at`

outbox
- `id` UUID PK
- `aggregate_type` VARCHAR
- `aggregate_id` UUID
- `event_type` VARCHAR
- `payload` JSON
- `published` BOOL DEFAULT FALSE
- `published_at` DATETIME NULL
- `created_at`

dead_letter_events
- `id` UUID PK
- `source` VARCHAR
- `event_type` VARCHAR
- `payload` JSON
- `error` TEXT
- `attempts` INT
- `created_at`

Indexes & constraints (recommended)
- `contacts` index on `canonical_name` and `deleted_at` (partial)
- `contact_identifiers` unique index on (`type`,`value`)
- `tasks` index on (`status`,`priority`,`scheduled_at`)
- `memories` index on (`contact_id`,`type`,`created_at`)
- `outbox` index on `published`

Example MySQL migration (contacts)

```sql
CREATE TABLE contacts (
  id CHAR(36) PRIMARY KEY,
  canonical_name VARCHAR(255) NOT NULL,
  profile JSON,
  preferences JSON,
  emotional_baseline JSON,
  schema_version INT DEFAULT 1,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at DATETIME NULL
);
CREATE INDEX idx_contacts_name ON contacts (canonical_name);
```

Outbox example migration

```sql
CREATE TABLE outbox (
  id CHAR(36) PRIMARY KEY,
  aggregate_type VARCHAR(128),
  aggregate_id CHAR(36),
  event_type VARCHAR(128),
  payload JSON,
  published BOOL DEFAULT FALSE,
  published_at DATETIME NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX idx_outbox_published ON outbox (published);
```

Operational notes
- Use UUID functions in MySQL (`UUID()` / native) or generate at app layer.
- Keep `profile` denormalized for fast reads; use separate tables for heavy relational needs.
- Retention: purge `logs` and `dead_letter_events` after retention policy (e.g., 90 days) unless archived.

Next steps
- Create `02-MEMORY_TABLES.md` with normalized memory tables, vector references, and retention rules.
