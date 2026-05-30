# 🎯 TASK: UP-001 - Task 10: NxConnectionDot Component (A02)
- **Status:** 🔴 PENDING
- **Dependencies:** UP-001_Task-09_NxStatusBar_Component

## 1. Objective
Create `NxConnectionDot.vue` — a 10px circle in the status bar showing WebSocket connection state with color-coded animations.

## 2. Files to Create/Modify
- `resources/js/Components/NxConnectionDot.vue` (new)

## 3. Implementation Steps
1. Create `resources/js/Components/NxConnectionDot.vue`
2. Props: `state: 'connecting' | 'connected' | 'disconnected' | 'error'`
3. Template: `<div class="nx-connection-dot" :class="stateClass" :title="tooltip" />`
4. Computed:
   - `stateClass`: returns class based on state (`is-connecting`, `is-connected`, `is-disconnected`, `is-error`)
   - `tooltip`: returns appropriate text ("Connected to Reverb", "Reconnecting…", etc.)
5. Styles:
   - Base: `width: 10px; height: 10px; border-radius: 50%; transition: all 0.3s ease;`
   - `.is-connecting`: `background: #F59E0B; animation: pulse 1.5s ease-in-out infinite;`
   - `.is-connected`: `background: #10B981; animation: breathe 3s ease-in-out infinite;`
   - `.is-disconnected`: `background: #EF4444;`
   - `.is-error`: `background: #EF4444; animation: jitter 100ms linear infinite;`
6. Add keyframe animations: `pulse` (opacity), `breathe` (scale 1→1.15), `jitter` (translateX -1px to 1px)
7. Save file and verify

## ✅ Final Verification
- [ ] Component created with 4 state classes
- [ ] Each state has correct color and animation
- [ ] Tooltip shows correct text on hover
- [ ] Size is exactly 10px
- [ ] Used in `NxStatusBar` left zone
- [ ] No console errors
