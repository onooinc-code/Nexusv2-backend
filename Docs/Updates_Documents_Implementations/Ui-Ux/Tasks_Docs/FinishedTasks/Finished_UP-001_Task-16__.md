# 🎯 TASK: UP-001 - Task 16: NxTokenBudget Component (A07)
- **Status:** 🔴 PENDING
- **Dependencies:** UP-001_Task-09_NxStatusBar_Component

## 1. Objective
Create `NxTokenBudget.vue` — a small SVG ring (24×24px) showing daily token budget usage with color-coded thresholds.

## 2. Files to Create/Modify
- `resources/js/Components/NxTokenBudget.vue` (new)

## 3. Implementation Steps
1. Create `resources/js/Components/NxTokenBudget.vue`
2. Props: `used: Number`, `budget: Number`
3. Emits: `click`
4. Template:
   ```vue
   <template>
     <svg class="nx-token-budget" viewBox="0 0 24 24" @click="$emit('click')">
       <circle class="bg-ring" cx="12" cy="12" r="10" />
       <circle class="fill-ring" cx="12" cy="12" r="10" :style="ringStyle" />
       <text x="12" y="16" text-anchor="middle" class="ring-text">{{ percentage }}%</text>
     </svg>
   </template>
   ```
5. Computed:
   - `percentage`: `Math.round((used / budget) * 100)`
   - `ringColor`: `< 70%` → `#007AFF`; `70-90%` → `#F59E0B`; `> 90%` → `#EF4444`
   - `ringStyle`: `{ stroke: ringColor, strokeDashoffset: circumference - (percentage / 100) * circumference }`
   - `circumference`: `2 * Math.PI * 10 = 62.83`
6. Styles:
   - `.nx-token-budget`: `width: 24px; height: 24px; cursor: pointer; transform: rotate(-90deg);`
   - `.bg-ring`: `fill: none; stroke: rgba(255,255,255,0.1); stroke-width: 2;`
   - `.fill-ring`: `fill: none; stroke-width: 2; stroke-linecap: round; transition: stroke-dashoffset 300ms ease;`
   - `.ring-text`: `font-size: 7px; fill: white; font-family: 'JetBrains Mono'; transform: rotate(90deg); transform-origin: center;`
7. Save file and verify

## ✅ Final Verification
- [ ] Component created with used/budget props
- [ ] SVG ring renders correctly
- [ ] Color changes at 70% and 90% thresholds
- [ ] Percentage text centered
- [ ] Click emits event
- [ ] Used in `NxStatusBar` right zone
- [ ] No console errors
