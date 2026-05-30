# 🎯 TASK: UP-001 - Task 11: NxQueuePill Component (A03)
- **Status:** 🔴 PENDING
- **Dependencies:** UP-001_Task-09_NxStatusBar_Component, UP-001_Task-19_useSystem_Store

## 1. Objective
Create `NxQueuePill.vue` — a clickable glass pill showing queue depth count that opens `NxQueueModal` on click.

## 2. Files to Create/Modify
- `resources/js/Components/NxQueuePill.vue` (new)

## 3. Implementation Steps
1. Create `resources/js/Components/NxQueuePill.vue`
2. Props: `count: Number`, `hasFailures: Boolean`
3. Emits: `click`
4. Template: `<button class="nx-queue-pill" :class="colorClass" @click="$emit('click')">{{ count }}</button>`
5. Computed:
   - `colorClass`: `count === 0` → `muted`; `count > 0 && !hasFailures` → `active`; `hasFailures` → `error`
6. Styles:
   - Base: `background: rgba(22,27,34,0.7); backdrop-filter: blur(12px); border: 1px solid rgba(255,255,255,0.1); border-radius: 9999px; padding: 2px 10px; font-family: 'JetBrains Mono'; font-variant-numeric: tabular-nums; font-size: 12px; min-height: 24px;`
   - `.muted`: `color: rgba(255,255,255,0.4);`
   - `.active`: `color: #007AFF;`
   - `.error`: `color: #EF4444;`
7. Save file and verify

## ✅ Final Verification
- [ ] Component created with count and hasFailures props
- [ ] Color changes based on count/failures
- [ ] JetBrains Mono font applied
- [ ] Click emits event
- [ ] Used in `NxStatusBar` right zone
- [ ] No console errors
