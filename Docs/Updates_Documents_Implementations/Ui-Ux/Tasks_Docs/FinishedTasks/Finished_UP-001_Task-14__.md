# 🎯 TASK: UP-001 - Task 14: NxAiPulse Component (F-UI-01)
- **Status:** 🔴 PENDING
- **Dependencies:** UP-001_Task-01_Package_Installation

## 1. Objective
Create `NxAiPulse.vue` — the AI state orb with 4 animation states: idle, thinking, speaking, error. This is a foundational component used throughout the app.

## 2. Files to Create/Modify
- `resources/js/Components/NxAiPulse.vue` (new)

## 3. Implementation Steps
1. Create `resources/js/Components/NxAiPulse.vue`
2. Props: `state: 'idle' | 'thinking' | 'speaking' | 'error'` (required), `size: Number` (default: 24), `amplitude: Number` (default: 0)
3. Template: `<div class="nx-ai-pulse" :class="stateClass" :style="style" />`
4. Computed:
   - `stateClass`: returns `is-idle`, `is-thinking`, `is-speaking`, `is-error`
   - `style`: returns `{ width: `${size}px`, height: `${size}px` }`
5. Styles:
   - Base: `border-radius: 50%; background: rgba(22,27,34,0.7); backdrop-filter: blur(12px); border: 1px solid rgba(255,255,255,0.1); transition: all 0.3s ease;`
   - `.is-idle`: `animation: breathe 4s ease-in-out infinite; opacity: 0.6;`
   - `.is-thinking`: `background: conic-gradient(from 0deg, #6366F1, #8B5CF6, #6366F1); animation: rotate 1s linear infinite;`
   - `.is-speaking`: `animation: speak 0.5s ease-in-out infinite alternate; transform: scale(1 + amplitude * 0.2);`
   - `.is-error`: `background: #EF4444; animation: jitter 100ms linear infinite;`
6. Add keyframes: `breathe` (scale 1→1.05, opacity 0.4→0.7), `rotate` (0→360deg), `speak` (scale 1→1.1), `jitter` (translateX -2px to 2px)
7. Save file and verify

## ✅ Final Verification
- [ ] Component created with state, size, amplitude props
- [ ] All 4 states render with correct colors/animations
- [ ] `thinking` shows conic-gradient rotation
- [ ] `error` shows jitter animation
- [ ] `speaking` scales with amplitude
- [ ] Size prop changes dimensions
- [ ] No console errors
