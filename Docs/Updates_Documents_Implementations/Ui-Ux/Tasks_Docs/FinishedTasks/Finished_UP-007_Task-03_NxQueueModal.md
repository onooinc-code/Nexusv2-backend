# 🎯 TASK: UP-007 - Task 03: NxQueueModal Component (F-MOD-03)
- **Status:** ✅ COMPLETED
- **Dependencies:** None

## 1. Objective
Create `NxQueueModal.vue` — a modal manager for queued/running jobs with sortable rows, optimistic cancel/retry actions, and live updates.

## 2. Files to Create/Modify
- `resources/js/Components/NxQueueModal.vue` (new)
- `resources/js/Components/NxActionButton.vue` (existing component used for actions)

## 3. Implementation Steps
1. Build a glass modal with a table of queued and running tasks.
2. Fetch initial jobs from `GET /api/v1/tasks?status=queued,running`.
3. Implement sort controls and row highlight animation on status changes.
4. Add optimistic `cancelJob(id)` and `retryJob(id)` actions using `DELETE /api/v1/tasks/{id}` and `POST /api/v1/tasks/{id}/retry`.
5. Subscribe to relevant Echo events if available to update row status live.

## ✅ Final Verification
- [x] Job list loads in the modal
- [x] Rows are sortable and animate on change
- [x] Cancel and retry actions work optimistically
- [x] Modal backdrop and glass styling are correct
