# 🎯 TASK: UP-001 - Task 09: NxStatusBar Component (A01)
- **Status:** 🔴 PENDING
- **Dependencies:** UP-001_Task-06_Pinia_Initialization, UP-001_Task-07_Echo_Reverb_Initialization

## 1. Objective
Create the `NxStatusBar.vue` component — a 40px tall frosted-glass horizontal bar anchored below the workspace header that hosts all status bar sub-components (A02–A10).

## 2. Files to Create/Modify
- `resources/js/Components/NxStatusBar.vue` (new): Create the status bar component
- `resources/js/App.vue` (modify): Mount `NxStatusBar` below workspace header

## 3. Implementation Steps
1. Create `resources/js/Components/NxStatusBar.vue`
2. Template structure:
   ```vue
   <template>
     <header class="nx-status-bar">
       <div class="zone zone-left">
         <slot name="left">
           <NxConnectionDot :state="connectionState" />
           <NxProviderDots :providers="providers" />
         </slot>
       </div>
       <div class="zone zone-center">
         <slot name="center">
           <NxJobRail :progress="jobProgress" :active="hasActiveJobs" />
         </slot>
       </div>
       <div class="zone zone-right">
         <slot name="right">
           <NxTokenBudget :used="tokenUsed" :budget="tokenBudget" />
           <NxQueuePill :count="queueDepth" :has-failures="hasQueueFailures" />
           <NxNotificationBell />
         </slot>
       </div>
     </header>
   </template>
   ```
3. Script setup:
   - Import `useSystem` from `../../stores/useSystem`
   - Import `NxConnectionDot`, `NxProviderDots`, `NxJobRail`, `NxTokenBudget`, `NxQueuePill`, `NxNotificationBell`
   - Get state from `useSystem()`: `connectionState`, `jobProgress`, `queueDepth`, `hasQueueFailures`, `tokenUsed`, `tokenBudget`, `providers`
4. Styles:
   - `.nx-status-bar`: `height: 40px; background: rgba(22,27,34,0.7); backdrop-filter: blur(12px); border-bottom: 1px solid rgba(255,255,255,0.1); display: flex; justify-content: space-between; align-items: center; padding: 0 16px; z-index: 40;`
   - `.zone`: `display: flex; align-items: center; gap: 12px;`
   - Animation: `@keyframes slideDown { from { transform: translateY(-100%); } to { transform: translateY(0); } }` — apply on mount
5. Mount in `App.vue` below workspace header: `<NxStatusBar />`
6. Save files and verify

## ✅ Final Verification
- [ ] `NxStatusBar.vue` created with correct template structure
- [ ] All 3 zones (left, center, right) present
- [ ] Reads state from `useSystem()` store
- [ ] Mounted in `App.vue`
- [ ] Animates in with slide-down on mount
- [ ] Height is 40px
- [ ] Glassmorphism styling applied
- [ ] No console errors
