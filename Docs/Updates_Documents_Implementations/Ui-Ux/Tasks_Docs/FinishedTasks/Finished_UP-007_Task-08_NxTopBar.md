# 🎯 TASK: UP-007 - Task 08: NxTopBar Component (F-PU-03)
- **Status:** ✅ COMPLETED
- **Dependencies:** useSystem store

## 1. Objective
Create `NxTopBar.vue` — a page-loading progress bar at the top of the viewport that animates from start to complete.

## 2. Files to Create/Modify
- `resources/js/Components/NxTopBar.vue` (new)
- `resources/js/stores/useSystem.js` (update to include `pageLoading` state)

## 3. Implementation Steps
1. Create a fixed top bar with a 3px progress indicator.
2. Read `pageLoading` from `useSystem()` and animate progress from 0% to 100%.
3. Implement crawl behavior: instant 30%, then incremental crawl to 90%, then finish.
4. Fade the bar out after loading completes.

## ✅ Final Verification
- [x] Progress bar appears at top above status bar
- [x] Page loading state animates correctly
- [x] Bar fades out cleanly when loading finishes