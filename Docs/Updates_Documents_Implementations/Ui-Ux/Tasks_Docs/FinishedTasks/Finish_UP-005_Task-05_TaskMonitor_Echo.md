# UP-005_Task-05: TaskMonitor — Echo + Optimistic

## Task Overview
Wire Echo events and add optimistic UI to TaskMonitor.vue.

## Feature Specification
- **Feature ID:** F-VF-05
- **File:** `resources/js/Pages/TaskMonitor.vue` (modify)

## Requirements
1. Wire WorkflowStepCompleted → update task row status
2. Wire JobProgressUpdated → update progress bar
3. Add NxTaskDetailDrawer (B04) slide-in on row click
4. Add optimistic "Retry" button with NxActionButton optimistic=true
5. Add NxLiveLoader (F-UI-04) for running tasks

## Implementation Details
- Task row status: queued → amber; running → blue pulse; completed → emerald; failed → crimson
- NxTaskDetailDrawer: 560px right drawer with trace_id, step logs, JSON payload
- NxLiveLoader: pulsing pill with expandable terminal log

## Verification
- `npm run build` passes
- WorkflowStepCompleted updates task row
- NxTaskDetailDrawer opens on row click
- Optimistic retry works
