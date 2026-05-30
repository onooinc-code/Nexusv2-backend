# 🎯 TASK: UP-002 - Task 02: NxTokenMeter Component (F-UI-03)
- **Status:** 🔴 PENDING
- **Dependencies:** UP-001_Task-01_Package_Installation

## 1. Objective
Create `NxTokenMeter.vue` — a horizontal SVG progress bar showing context window usage with color-coded thresholds.

## 2. Files to Create/Modify
- `resources/js/Components/NxTokenMeter.vue` (new)

## 3. Implementation Steps
1. Create `resources/js/Components/NxTokenMeter.vue`
2. Props: `currentTokens: Number`, `maxTokens: Number` (default: 6000)
3. Template:
   ```vue
   <template>
     <svg class="nx-token-meter" viewBox="0 0 100 4" preserveAspectRatio="none">
       <rect class="bg" x="0" y="0" width="100" height="4" rx="2" />
       <rect class="fill" x="0" y="0" :width="barWidth" height="4" rx="2" :fill="thresholdColor" />
     </svg>
   </template>
   ```
4. Computed:
   - `percentage`: `Math.min(currentTokens / maxTokens, 1)`
   - `barWidth`: `${percentage * 100}%`
   - `thresholdColor`: `< 0.7` → `#007AFF`; `0.7-0.9` → `#F59E0B`; `> 0.9` → `#EF4444`
5. Styles:
   - `.nx-token-meter`: `width: 100%; height: 4px; display: block;`
   - `.bg`: `fill: rgba(255,255,255,0.1);`
   - `.fill`: `transition: width 300ms ease, fill 300ms ease;`
6. Save file and verify

## ✅ Final Verification
- [ ] Component created with currentTokens and maxTokens props
- [ ] SVG renders with correct dimensions
- [ ] Color changes at 70% and 90% thresholds
- [ ] Width animates smoothly on value change
- [ ] Used in NxContextBar and NxTokenBudget
- [ ] No console errors
