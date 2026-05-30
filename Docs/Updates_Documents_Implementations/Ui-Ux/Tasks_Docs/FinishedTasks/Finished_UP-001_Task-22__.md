# 🎯 TASK: UP-001 - Task 22: Mount NxStatusBar in App.vue
- **Status:** 🔴 PENDING
- **Dependencies:** UP-001_Task-09_NxStatusBar_Component, UP-001_Task-10_NxConnectionDot, UP-001_Task-11_NxQueuePill, UP-001_Task-12_NxJobRail, UP-001_Task-13_NxAgentBadge, UP-001_Task-15_NxRateLimitBanner, UP-001_Task-16_NxTokenBudget, UP-001_Task-17_NxMemoryPressure, UP-001_Task-18_NxProviderDots, UP-001_Task-20_NxNotificationBell

## 1. Objective
Mount all Status Bar components (A01–A10) in `App.vue` below the workspace header.

## 2. Files to Create/Modify
- `resources/js/App.vue`: Add `<NxStatusBar />` below workspace header

## 3. Implementation Steps
1. Open `resources/js/App.vue`
2. Import `NxStatusBar` from `./Components/NxStatusBar.vue`
3. In the template, add `<NxStatusBar />` below the workspace header (after the header element)
4. Ensure the status bar is inside the workspace flex column layout
5. Save file and verify

## ✅ Final Verification
- [ ] `NxStatusBar` imported in `App.vue`
- [ ] `<NxStatusBar />` rendered below workspace header
- [ ] All sub-components (A02–A10) visible in status bar
- [ ] Status bar height is 40px
- [ ] Glassmorphism styling applied
- [ ] No console errors
