# 05 - WorkflowsHub

## Purpose

The WorkflowsHub defines and executes orchestrated flows across Nexus hubs and services.
It abstracts business processes into reusable workflow patterns, manages state transitions, and connects AI, memory, contacts, settings, and logging into a coherent execution fabric.

## Scope

- Declarative workflow definitions
- Execution orchestration and state management
- Input/output transformation
- Retry, compensation, and error handling
- Integration with external systems and internal hubs
- Observability and auditability for workflow executions

## Core Responsibilities

1. Workflow definition and registration
2. Execution engine and state orchestration
3. Resource and dependency coordination
4. Retry and compensation handling
5. Audit and trace propagation
6. Workflow lifecycle management

## Architecture

WorkflowsHub is composed of the following subcomponents:

- `WorkflowRegistry`
- `WorkflowInterpreter`
- `StateManager`
- `TaskDispatcher`
- `PolicyGuard`
- `ErrorHandler`
- `WorkflowTracer`

### WorkflowRegistry

Stores workflow metadata, versioned definitions, and deployment status.

Responsibilities:

- Register workflow manifests and definitions
- Support versioned workflows and feature-flagged variants
- Validate workflow schemas and link declarative steps to hub actions
- Expose workflow metadata for UI and automation tooling

### WorkflowInterpreter

Executes workflow definitions and resolves actions.

Responsibilities:

- Interpret workflow steps and conditions
- Convert declarative definitions into actionable operations
- Enforce sequencing, branching, and parallel execution semantics
- Map workflow steps to hub APIs and external system adapters

### StateManager

Tracks workflow progress, variables, and execution state.

Responsibilities:

- Persist workflow instance state and intermediate results
- Provide checkpointing for long-running or paused workflows
- Support event-driven and timer-based resume points
- Enable cancellation and safe termination

### TaskDispatcher

Routes and executes individual workflow tasks.

Responsibilities:

- Dispatch tasks to the correct hub or service
- Manage task concurrency and resource limits
- Handle task responses, success/failure reconciliation, and output mapping
- Coordinate sub-workflow invocation

### PolicyGuard

Applies governance and policy checks before execution.

Responsibilities:

- Enforce access control and tenant policies
- Validate budget, quota, and compliance constraints
- Check workflow definitions against security rules
- Block or quarantine unsafe workflows

### ErrorHandler

Manages retry logic and compensation flows.

Responsibilities:

- Classify failures and determine retry strategies
- Execute compensating actions for rollback or cleanup
- Escalate unrecoverable failures to alerting and audit
- Record error context for debugging and recovery

### WorkflowTracer

Captures trace data for workflow observability.

Responsibilities:

- Emit structured trace events for workflow execution paths
- Correlate workflow steps with hub calls and external events
- Support distributed tracing and time-series analysis
- Provide execution timelines for debugging and optimization

## API Contract

### `POST /workflows/execute`

Request body:

- `workflow_id`
- `workflow_version` (optional)
- `workspace_id`
- `input_payload`
- `trigger_source`
- `priority`
- `run_mode`: enum `sync`,`async`,`scheduled`
- `metadata`

Response:

- `execution_id`
- `status`
- `started_at`
- `next_step`

### `GET /workflows/{execution_id}`

Response:

- `execution_id`
- `workflow_id`
- `workflow_version`
- `status`
- `current_step`
- `runtime_state`
- `history`

### `POST /workflows/{execution_id}/cancel`

Response:

- `execution_id`
- `status`
- `cancelled_at`
- `reason`

### `GET /workflows/definitions`

Response:

- `workflow_definitions`
- `version`
- `status`
- `tags`

## Workflow Definition Model

### Step types

- `action`: call a hub or external adapter
- `decision`: conditional branching
- `parallel`: run steps concurrently
- `wait`: delay or pause until an event
- `loop`: iterate over a collection
- `compensate`: rollback or cleanup

### Example definition

```json
{
  "workflow_id": "conversation_summary",
  "version": "1.0",
  "steps": [
    {
      "id": "load_context",
      "type": "action",
      "target": "memory.hub.fetch",
      "input": {
        "workspace_id": "{{workspace_id}}",
        "session_id": "{{session_id}}"
      }
    },
    {
      "id": "summarize",
      "type": "action",
      "target": "ai-models.hub.route",
      "input": {
        "intent": "text_summarization",
        "input_text": "{{load_context.result}}"
      }
    },
    {
      "id": "publish",
      "type": "action",
      "target": "contacts.hub.notify",
      "input": {
        "recipients": "{{recipients}}",
        "message": "{{summarize.result}}"
      }
    }
  ]
}
```

## Execution Patterns

### Synchronous workflows

Used when callers require immediate results.

- Execute all defined steps in one request
- Return final result or failure reason
- Suitable for fast, bounded business logic

### Asynchronous workflows

Used for long-running or event-driven processes.

- Create workflow instance and return execution handle
- Persist state and resume when events arrive
- Support retries and manual intervention

### Scheduled workflows

Used for time-based processing.

- Trigger workflows from cron-like schedules or event windows
- Combine recurrence with stateful execution
- Enqueue workflow instances into execution queue

## Error Handling

### Retry strategies

- `immediate`: retry several times before failing
- `delayed`: wait and retry with backoff
- `conditional`: retry only on transient errors

### Compensation strategies

- `undo`: reverse successful actions when a later step fails
- `cleanup`: remove partial side effects
- `escalate`: forward failure to audit or operator action

### Failure modes

- `retryable`: safe to retry without data loss
- `terminal`: workflow cannot proceed
- `partial_success`: some steps succeeded while others failed

## Observability

- Emit workflow lifecycle events: started, step_started, step_completed, step_failed, completed, cancelled
- Capture execution timing, step durations, and resource usage
- Aggregate metrics by workflow, workspace, and tenant
- Record trace IDs and correlate with hub requests

## Governance

- Support workflow approvals for high-risk or high-cost flows
- Enforce tenant-level throttles and concurrency caps
- Validate workflows against policy rules before execution
- Track workflow ownership and change history

## Integration with Other Hubs

- `AiModelsHub` for model selection and safe provider routing
- `MemoryHub` for context retrieval and persistence
- `ContactsHub` for notifications and contact actions
- `SettingsHub` for feature flags and workflow configuration
- `LogsHub` for audit logging and traceability

## Implementation Notes

- Define workflows as declarative, versioned assets
- Keep task execution pluggable for hub and adapter invocation
- Support human-in-the-loop and approval checkpoints
- Enable replay and rollback for critical flows

## Example Workflow Lifecycle

1. A user or system triggers `conversation_summary`.
2. `WorkflowsHub` validates the workflow and policy guard.
3. The execution engine dispatches `load_context` to `MemoryHub`.
4. The `summarize` action routes to `AiModelsHub`.
5. The `publish` action notifies recipients through `ContactsHub`.
6. The workflow completes with audit logs in `LogsHub`.
