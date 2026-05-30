# 06 - SettingsHub

## Purpose

The SettingsHub provides centralized configuration management, feature toggles, and runtime settings for Nexus.
It allows services and workflows to resolve configuration values consistently while supporting tenant-level overrides, environment-specific rules, and dynamic experiment control.

## Scope

- Global and scoped configuration storage
- Feature flag evaluation
- Runtime overrides and environment differentiation
- Policy-driven configuration enforcement
- Change auditing and rollback
- Integration with workflow and execution guards

## Core Responsibilities

1. Configuration storage and retrieval
2. Feature flag evaluation and rollout management
3. Scoped overrides and inheritance
4. Validation and schema enforcement
5. Change governance and auditing
6. Integration with runtime policy enforcement

## Architecture

SettingsHub is composed of the following subcomponents:

- `ConfigStore`
- `FlagEngine`
- `ScopeResolver`
- `ValidationService`
- `AuditLogger`
- `NotificationBridge`

### ConfigStore

Stores configuration entries and resolves values.

Responsibilities:

- Persist configuration metadata and values
- Support typed values: boolean, string, integer, JSON
- Allow default, workspace, tenant, and user scopes
- Provide read-through and cache-aware lookups

### FlagEngine

Evaluates feature flags and rollout criteria.

Responsibilities:

- Determine if a flag is enabled for a given context
- Support percentage rollouts, attribute targeting, and graduated release
- Integrate with runtime workflows and guard logic
- Provide transparent evaluation reasons for observability

### ScopeResolver

Handles configuration inheritance and override precedence.

Responsibilities:

- Resolve values from global, workspace, tenant, and environment scopes
- Apply policy and safety constraints during resolution
- Support local development, staging, and production variations
- Expose effective configuration values to callers

### ValidationService

Ensures configuration integrity.

Responsibilities:

- Validate configuration schemas and types
- Reject invalid or unsafe changes
- Enforce allowed value ranges and enumerations
- Support declarative config contracts for critical settings

### AuditLogger

Captures changes and access events.

Responsibilities:

- Record configuration writes, overrides, and deletes
- Track who changed settings and when
- Maintain history for rollback and compliance
- Support queryable audit trails

### NotificationBridge

Broadcasts changes to interested subscribers.

Responsibilities:

- Notify dependent services and hubs on config changes
- Invalidate caches when settings change
- Trigger workflow updates and policy re-evaluation
- Integrate with event bus or change streams

## API Contract

### `GET /settings/{key}`

Response:

- `key`
- `value`
- `effective_scope`
- `source`
- `evaluation_reason`

### `POST /settings/{key}`

Request body:

- `value`
- `scope`
- `workspace_id` (optional)
- `tenant_id` (optional)
- `description` (optional)
- `validation_rules` (optional)

Response:

- `key`
- `value`
- `scope`
- `updated_at`

### `GET /settings/flags/{flag_id}`

Response:

- `flag_id`
- `enabled`
- `rollout_percentage`
- `targeting_rules`
- `evaluation_context`

### `POST /settings/flags/evaluate`

Request body:

- `flag_id`
- `workspace_id`
- `tenant_id`
- `user_id`
- `context`

Response:

- `flag_id`
- `enabled`
- `reason`
- `matched_rule`

### `GET /settings/metadata`

Response:

- `keys`
- `scopes`
- `schemas`
- `last_modified`

## Configuration Model

### Configuration scopes

- `global`: system-wide defaults
- `workspace`: organization or project-specific settings
- `tenant`: customer-specific overrides
- `environment`: deployment-specific variants
- `user`: personal or session-specific values when applicable

### Inheritance rules

- More specific scopes override broader scopes
- Undefined values fall back to higher-level defaults
- Safety policies may lock specific keys from override

### Feature flag model

- `flag_id`
- `enabled`
- `rollout_percentage`
- `targeting_conditions`
- `default_behavior`
- `metadata`

### Example setting

```json
{
  "key": "ai_models.max_response_tokens",
  "scope": "workspace",
  "value": 2048,
  "description": "Maximum token limit for AI model responses in a workspace.",
  "validation_rules": {
    "type": "integer",
    "minimum": 256,
    "maximum": 8192
  }
}
```

## Validation and Guardrails

- Config values must conform to declared schemas
- Critical flags require review and approval changes
- There is a separation between runtime-effective config and pending staged config
- Unsafe or high-risk keys can be scoped to administrators only

## Auditing and Change Control

- Record all write operations with actor, source, and reason
- Support rollback to prior config versions
- Provide history queries by key, scope, and actor
- Integrate with LogsHub and governance workflows

## Integration Points

- `WorkflowsHub` uses settings for feature gating and workflow behavior
- `AiModelsHub` uses settings for provider preferences, model caps, and cost policies
- `MemoryHub` uses settings for pruning rules, retention windows, and storage tiers
- `ContactsHub` uses settings for notification channels, delivery rules, and recipient policies
- `LogsHub` uses settings for log retention, sampling, and alert thresholds

## Observability

- Emit configuration access and evaluation events
- Track flag evaluation metrics and false-positive/false-negative ratios
- Monitor config change frequency and drift across environments
- Capture resolution reasons for troubleshooting

## Implementation Notes

- Prefer immutable config change records with current-effective views
- Keep feature flag evaluation fast and cacheable
- Separate read paths from write paths for safety and scale
- Use a config schema registry for critical settings and flags
