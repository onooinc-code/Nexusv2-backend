# Phase 3: Core Hub Implementation

## 🎯 Goal
Implement the core Nexus hubs: AgentsHub, MemoryHub, ContactsHub, and LogsHub. These hubs provide the system's central intelligence and state management.

---

## 1. AgentsHub
### Responsibilities
- Agent CRUD and lifecycle management
- Agent persona, skills, tools, and instructions
- Task orchestration and monitoring
- Agent telemetry and health checks

### Components
- `App\Http\Controllers\AgentsHubController`
- `App\Services\AgentsHubService`
- `App\Models\Agent`
- `App\Repositories\AgentRepository`
- `App\Events\AgentTaskCreated`
- `App\Jobs\ExecuteAgentTaskJob`

### Key Workflows
- Create/update agent profiles and skill sets
- Execute agent tasks via workflow engine
- Log agent actions and outcomes in LogsHub
- Support human handoff and agent escalation

---

## 2. MemoryHub
### Responsibilities
- Store and retrieve all memory types
- Manage memory lifecycles, pruning, and consolidation
- Provide memory search and semantic retrieval
- Expose memory APIs for other hubs

### Components
- `App\Http\Controllers\MemoryHubController`
- `App\Services\MemoryHubService`
- `App\Repositories\MemoryRepository`
- `App\Models\MemoryEntry`
- `App\Vectors\VectorStoreClient`
- `App\Jobs\ConsolidateMemoryJob`

### Key Workflows
- Persist short-term working memory in Redis
- Persist episodic memories in MySQL
- Index semantic memory in vector store
- Normalize and link structured memory
- Maintain graph memory relationships

---

## 3. ContactsHub
### Responsibilities
- Manage contact profiles and relationship graphs
- Handle alias resolution and contact merging
- Provide contact context to the AI and task engines
- Track preferences, privacy settings, and contact metadata

### Components
- `App\Http\Controllers\ContactsHubController`
- `App\Services\ContactsHubService`
- `App\Models\Contact`
- `App\Models\ContactPreference`
- `App\Repositories\ContactRepository`
- `App\Jobs\SyncContactMetadataJob`

### Key Workflows
- Create / update contact profiles
- Resolve duplicate contacts and aliases
- Sync contact metadata from external sources
- Provide relationship-aware persona rules

---

## 4. LogsHub
### Responsibilities
- Capture detailed logs for every hub action
- Support hierarchical log entries and structured metadata
- Publish alerts and trigger automations
- Provide audit and traceability for GDPR and debugging

### Components
- `App\Http\Controllers\LogsHubController`
- `App\Services\LogsHubService`
- `App\Models\SystemLog`
- `App\Repositories\LogRepository`
- `App\Jobs\ArchiveOldLogsJob`

### Key Workflows
- Ingest logs from API calls, jobs, and internal events
- Classify logs by type, source, severity
- Trigger automated workflows based on log conditions
- Provide queryable log endpoints for dashboards

---

## 5. Integration Patterns
- Use Events + Listeners to decouple cross-hub operations
- Expose hub capabilities through shared services
- Ensure each hub publishes health and readiness states
- Use SettingsHub to configure dependencies and policies

---

## 6. Phase Deliverables
- Working implementations of AgentsHub, MemoryHub, ContactsHub, LogsHub
- CRUD APIs and backend services for all core hubs
- End-to-end tests for key workflows
- Hub integration documentation in `03-HUBS_SPECIFICATION`
