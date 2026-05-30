# 🎯 TASK: UP-001 - Task 12: NxJobRail Component (A04)
- **Status:** 🔴 PENDING
- **Dependencies:** UP-001_Task-09_NxStatusBar_Component, UP-001_Task-19_useSystem_Store

## 1. Objective
Create `NxJobRail.vue` — a 2px tall full-width progress bar in the status bar showing aggregate background job progress.

## 2. Files to Create/Modify
- `resources/js/Components/NxJobRail.vue` (new)

## 3. Implementation Steps
1. Create `resources/js/Components/NxJobRail.vue`
2. Props: `progress: Number (0-100)`, `active: Boolean`
3. Template: `<div class="nx-job-rail" :class="{ active }" :style="railStyle" />`
4. Computed:
   - `railStyle`: `{ '--progress': `${progress}%`, width: active ? '100%' : '0%' }`
5. Styles:
   - Base: `position: fixed; top: 0; left: 0; height: 2px; background: linear-gradient(90deg, #007AFF, #007AFF var(--progress), transparent var(--progress)); transition: width 300ms ease, opacity 400ms ease; z-index: 100;`
   - `.active`: `opacity: 1;`
   - Inactive: `opacity: 0;`
6. Add glow effect: `box-shadow: 2px 0 8px #007AFF;` on the right edge
7. Save file and verify

## ✅ Final Verification
- [ ] Component created with progress and active props
- [ ] Width animates smoothly with transition
- [ ] Color is Nexus Blue #007AFF
- [ ] Glow effect on right edge
- [ ] Used in `NxStatusBar` center zone
- [ ] No console errors
