# 🎯 TASK: UP-007 - Task 04: NxTaskDetailDrawer Component (F-MOD-04)
- **Status:** ✅ COMPLETED
- **Dependencies:** None

## 1. Objective
Create `NxTaskDetailDrawer.vue` — a task detail drawer showing trace ID, status timeline, step log accordion, assignment info, and raw JSON payload.

## 2. Files to Create/Modify
- `resources/js/Components/NxTaskDetailDrawer.vue` (new)
- `resources/js/Components/NxLiveLoader.vue` (existing component reused for step logs)
- `resources/js/Components/NxActionButton.vue` (action buttons)

## 3. Implementation Steps
1. Create a 560px right-side drawer with a glass background.
2. Add props `taskId` and emit `close` and `retry`.
3. Fetch detail data from `GET /api/v1/tasks/{id}` and logs from `GET /api/v1/tasks/{id}/logs`.
4. Render trace_id with copy-to-clipboard support and syntax-highlighted JSON payload.
5. Subscribe to task channel events to append logs and update the status timeline.

## ✅ Final Verification
- [x] Drawer loads task details and logs
- [x] Trace ID can be copied to clipboard
- [x] Retry button is visible and actionable
- [x] Logs accordion expands/collapses properly
