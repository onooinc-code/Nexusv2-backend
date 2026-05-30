# AgentsHub

Purpose
- Central orchestration and lifecycle management for autonomous agents.
- Provide APIs to register, run, monitor, and audit agents.

Scope
- Agent registry, persona and skill management, tool discovery, runtime orchestration, monitoring, and analytics.

Modules
- Agent Registry & Lifecycle: CRUD, versioning, status, and lifecycle hooks (activate/deactivate).
- Tool Management: Discover and authorize external tools (APIs, connectors) per agent.
- Skill Management: Upload, version, and test skill bundles (templates/actions).
- Persona Configuration: Store system instructions, persona traits, tone preferences.
- Instruction Templates: Reusable prompt templates and slot variables.
- Task Orchestration: Trigger agents synchronously or asynchronously via WorkflowsHub.
- Monitoring & Analytics: Agent usage, success/failure metrics, decision traces.

API Endpoints (examples)
- `POST /api/v1/agents` — create/upsert an agent
  - Body: `{ id?: uuid, name: string, persona: object, tools?: array, settings?: object }`
  - Idempotency: support `X-Idempotency-Key`

- `GET /api/v1/agents/{id}` — retrieve agent metadata and status

- `POST /api/v1/agents/{id}/run` — trigger an agent
  - Body: `{ input: object, mode: "sync" | "async", callback_url?: string, run_options?: {} }`
  - Sync: returns `result` (if quick). Async: returns `task_id`.

- `GET /api/v1/agents/{id}/tasks/{task_id}` — get execution status and logs

- `POST /api/v1/agents/{id}/tools` — attach a tool (with permissions)

- `GET /api/v1/agents` — list agents with filters (status, owner, tag)

Data models
- Agent record:
  - `id` (uuid), `name`, `owner_id`, `persona` (json), `skills` (json), `tools` (json), `status`, `created_at`, `updated_at`
- AgentRuntimeLog:
  - `id`, `agent_id`, `task_id`, `trace_id`, `step`, `input`, `output`, `duration_ms`, `created_at`

Runtime behavior
1. Agent created via API with persona and allowed tools.
2. Agent run invoked (sync/async). For async, create `task` and enqueue to WorkflowsHub.
3. Agent runtime loads persona, fetches tools' credentials from `SettingsHub`/`ApiKeys` and sets up execution sandbox.
4. Agent executes steps via `WorkflowsAndTasksHub` pipelines; any external IO goes through Services with proper RBAC.
5. Agent emits structured runtime logs to `LogsHub` and events to broker (`agent.started`, `agent.step.completed`, `agent.completed`).
 
Observability & Metrics
- Track: runs per agent, average run duration, success/failure rate, tool usage, cost per run (tokens/calls).
- Expose Prometheus metrics and OpenTelemetry traces.

Testing & Simulation
- Provide a sandbox endpoint to `POST /api/v1/agents/{id}/simulate` with mocked tool responses and assert expected actions.
- Contract tests: validate that agents respect `SettingsHub` feature flags and `contact_rules`.

Events
- `agent.registered`, `agent.updated`, `agent.started`, `agent.step.completed`, `agent.completed`, `agent.failed`.
- Events published to broker and recorded to `outbox` for reliable delivery.

Example OpenAPI snippet
```yaml
paths:
  /api/v1/agents/{id}/run:
    post:
      summary: Run agent
      parameters:
        - name: id
          in: path
          required: true
      requestBody:
        content:
          application/json:
            schema:
              type: object
              properties:
                input: { type: object }
                mode: { type: string, enum: [sync, async] }
      responses:
        '200': { description: OK }
        '202': { description: Accepted (async) }
```

Operational notes
- Rate-limit agent runs per-owner to prevent runaway costs.
- Provide cost-estimation per run based on model selection and historical usage.
- Implement a kill-switch to stop misbehaving agents and quarantine their runtime artifacts.

Next steps
- Draft `MemoryHub` specification and per-agent RBAC rules.
