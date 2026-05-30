# 🎯 TASK: UP-001 - Task 18: NxProviderDots Component (A09)
- **Status:** 🔴 PENDING
- **Dependencies:** UP-001_Task-09_NxStatusBar_Component

## 1. Objective
Create `NxProviderDots.vue` — a row of colored dots (one per AI provider) showing provider health status in the status bar.

## 2. Files to Create/Modify
- `resources/js/Components/NxProviderDots.vue` (new)

## 3. Implementation Steps
1. Create `resources/js/Components/NxProviderDots.vue`
2. Props: `providers: Array<{ name, latency, status }>`
3. Template:
   ```vue
   <template>
     <div class="nx-provider-dots">
       <div
         v-for="provider in providers"
         :key="provider.name"
         class="provider-dot"
         :class="`status-${provider.status}`"
         :title="`${provider.name} · ${provider.latency}ms · ${provider.status}`"
       />
     </div>
   </template>
   ```
4. Styles:
   - `.nx-provider-dots`: `display: flex; align-items: center; gap: 4px;`
   - `.provider-dot`: `width: 8px; height: 8px; border-radius: 50%; transition: background-color 200ms ease;`
   - `.status-online`: `background: #10B981;`
   - `.status-degraded`: `background: #F59E0B;`
   - `.status-offline`: `background: #EF4444;`
5. Save file and verify

## ✅ Final Verification
- [ ] Component created with providers prop
- [ ] Renders one dot per provider
- [ ] Color matches status (online=emerald, degraded=amber, offline=crimson)
- [ ] Tooltip shows provider name, latency, status
- [ ] Used in `NxStatusBar` left zone
- [ ] No console errors
