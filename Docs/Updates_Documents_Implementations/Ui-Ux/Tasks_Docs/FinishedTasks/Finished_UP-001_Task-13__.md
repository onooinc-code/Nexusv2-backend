# 🎯 TASK: UP-001 - Task 13: NxAgentBadge Component (A05)
- **Status:** 🔴 PENDING
- **Dependencies:** UP-001_Task-09_NxStatusBar_Component, UP-001_Task-19_useSystem_Store, UP-001_Task-14_NxAiPulse

## 1. Objective
Create `NxAgentBadge.vue` — shows active agent count with embedded `NxAiPulse` orb in the status bar.

## 2. Files to Create/Modify
- `resources/js/Components/NxAgentBadge.vue` (new)

## 3. Implementation Steps
1. Create `resources/js/Components/NxAgentBadge.vue`
2. Props: `count: Number`
3. Emits: `click`
4. Template:
   ```vue
   <template>
     <button class="nx-agent-badge" @click="$emit('click')">
       <NxAiPulse :state="pulseState" :size="12" />
       <span class="count">{{ count }}</span>
     </button>
   </template>
   ```
5. Computed:
   - `pulseState`: `count > 0 ? 'thinking' : 'idle'`
6. Styles:
   - Base: `display: flex; align-items: center; gap: 6px; padding: 4px 10px; background: rgba(22,27,34,0.7); backdrop-filter: blur(12px); border: 1px solid rgba(255,255,255,0.1); border-radius: 9999px; font-family: 'JetBrains Mono'; font-size: 12px; color: var(--color-text-secondary); cursor: pointer; transition: all 0.2s ease;`
   - Hover: `background: rgba(255,255,255,0.05);`
7. Save file and verify

## ✅ Final Verification
- [ ] Component created with count prop
- [ ] `NxAiPulse` embedded with correct state
- [ ] Pulse state is 'thinking' when count > 0, 'idle' when 0
- [ ] JetBrains Mono font applied
- [ ] Click emits event
- [ ] Used in `NxStatusBar` left zone
- [ ] No console errors
