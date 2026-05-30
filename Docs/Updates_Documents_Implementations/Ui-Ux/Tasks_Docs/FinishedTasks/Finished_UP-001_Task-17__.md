# 🎯 TASK: UP-001 - Task 17: NxMemoryPressure Component (A08)
- **Status:** 🔴 PENDING
- **Dependencies:** UP-001_Task-09_NxStatusBar_Component

## 1. Objective
Create `NxMemoryPressure.vue` — shows Redis memory usage percentage as a small pill, only visible when usage exceeds 60%.

## 2. Files to Create/Modify
- `resources/js/Components/NxMemoryPressure.vue` (new)

## 3. Implementation Steps
1. Create `resources/js/Components/NxMemoryPressure.vue`
2. Props: `percent: Number`
3. Template: `<div v-if="percent > 60" class="nx-memory-pressure" :class="colorClass">{{ percent }}%</div>`
4. Computed:
   - `colorClass`: `percent > 80` → `critical`; `percent > 60` → `warning`
5. Styles:
   - Base: `background: rgba(22,27,34,0.7); backdrop-filter: blur(12px); border: 1px solid rgba(255,255,255,0.1); border-radius: 9999px; padding: 2px 8px; font-size: 11px; font-family: 'JetBrains Mono'; font-variant-numeric: tabular-nums;`
   - `.warning`: `color: #F59E0B; border-color: rgba(245, 158, 11, 0.3);`
   - `.critical`: `color: #EF4444; border-color: rgba(239, 68, 68, 0.3); animation: pulse-red 2s ease-in-out infinite;`
6. Add `@keyframes pulse-red` for critical state glow
7. Save file and verify

## ✅ Final Verification
- [ ] Component created with percent prop
- [ ] Only visible when percent > 60
- [ ] Color changes at 80% threshold
- [ ] Critical state pulses with glow
- [ ] JetBrains Mono font applied
- [ ] No console errors
