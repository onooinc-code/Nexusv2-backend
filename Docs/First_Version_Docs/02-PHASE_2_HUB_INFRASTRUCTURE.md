# Phase 2: Hub Infrastructure

## 🎯 Goal
Build the reusable hub framework and shared base classes that form the foundation of Nexus architecture.

---

## 1. Hub Design Principles
- **Decoupled**: Each hub has a single responsibility.
- **Plug-and-play**: Hubs can be added, removed, or upgraded independently.
- **API-first**: All hub functionality is exposed through versioned APIs.
- **Observable**: Every hub emits logs, metrics, and events.
- **Testable**: All hub behavior is covered by unit tests.

---

## 2. Core Hub Components
### Hub Base Classes
- `App\Hubs\BaseHub`
  - Standard lifecycle methods
  - Contract enforcement
  - Metrics and tracing hooks
  - Settings access

- `App\Hubs\Contracts\HubInterface`
  - `getName(): string`
  - `getVersion(): string`
  - `getRoutes(): array`
  - `handleRequest(Request $request)`

### Hub Router
- `App\Hubs\Routing\HubRouter`
  - Routes requests to correct hub controller/service
  - Supports middleware injection and request validation
  - Enables future multi-tenancy or multi-workspace routing

---

## 3. Shared Hub Services
- `App\Services\HubDiscoveryService`
  - Lists available hubs, health status, and capabilities
- `App\Services\HubPermissionService`
  - Guards hub actions based on role and scope
- `App\Services\HubConfigService`
  - Loads hub configuration from SettingsHub or JSON fallback

---

## 4. Hub Registration
### Hub Manifest
Each hub should include a manifest file:\n- `hubs/agents/manifest.json`
- `hubs/memory/manifest.json`
- `hubs/contacts/manifest.json`

Manifest fields:
- `name`
- `slug`
- `version`
- `description`
- `routes`
- `dependencies`
- `capabilities`

### Auto-registration
- Use Laravel service provider discovery to register hubs automatically.
- `App\Providers\HubServiceProvider` reads manifests and resolves bindings.

---

## 5. Hub API Contracts
- Standard request/response structure
- Error envelope with `code`, `message`, `details`
- Pagination metadata for list endpoints
- `meta` block for diagnostics and request trace IDs

Example response:
```json
{
  "success": true,
  "data": {...},
  "meta": {
    "trace_id": "abc123",
    "duration_ms": 62
  }
}
```

---

## 6. Hub Lifecycle
### Startup
- Validate configuration and dependencies
- Register routes and tasks
- Warm cache and model metadata

### Runtime
- Accept API calls
- Publish events through Event Architecture
- Persist logs to LogsHub

### Shutdown
- Flush queues
- Close external connections safely
- Emit graceful shutdown event

---

## 7. Phase Deliverables
- Reusable hub base classes and interfaces
- Hub router and manifest-driven registration
- Shared hub services for discovery, config, and permissions
- API contract documentation for hub entrypoints
- Unit tests for hub lifecycle and base behavior
