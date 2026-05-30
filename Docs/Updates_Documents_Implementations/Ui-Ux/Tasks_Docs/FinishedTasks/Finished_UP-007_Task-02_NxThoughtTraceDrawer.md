# 🎯 TASK: UP-007 - Task 02: NxThoughtTraceDrawer Component (F-MOD-02)
- **Status:** ✅ COMPLETED
- **Dependencies:** None

## 1. Objective
Create `NxThoughtTraceDrawer.vue` — a slide-in drawer for real-time agent reasoning trace display with animated step lines and Echo subscription.

## 2. Files to Create/Modify
- `resources/js/Components/NxThoughtTraceDrawer.vue` (new)
- `resources/js/Components/NxLiveLoader.vue` (existing component may be reused)

## 3. Implementation Steps
1. Create a right-side drawer component with a glass panel and close button.
2. Add props `agentId` and `taskId`, and emit `close`.
3. Subscribe on mount to `window.Echo.private('agents.' + agentId).listen('AgentStepCompleted', appendStep)`.
4. Render reasoning steps with color-coded states: thinking, tool-call, observation, response.
5. Auto-scroll to the bottom on new steps and animate each step line with slide-in + opacity.

## ✅ Final Verification
- [x] Drawer opens and closes correctly
- [x] Agent steps append in real-time
- [x] Step colors match reasoning states
- [x] Auto-scroll works on new events
- [x] Drawer is responsive and uses glass styling
