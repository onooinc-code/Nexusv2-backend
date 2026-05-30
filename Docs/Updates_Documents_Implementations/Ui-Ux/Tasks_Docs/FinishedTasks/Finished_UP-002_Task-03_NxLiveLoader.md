# 🎯 TASK: UP-002 - Task 03: NxLiveLoader Component (F-UI-04)
- **Status:** 🔴 PENDING
- **Dependencies:** UP-001_Task-01_Package_Installation, UP-001_Task-07_Echo_Reverb_Initialization

## 1. Objective
Create `NxLiveLoader.vue` — a pulsing glass pill that expands to show a terminal-style log feed for async task monitoring.

## 2. Files to Create/Modify
- `resources/js/Components/NxLiveLoader.vue` (new)

## 3. Implementation Steps
1. Create `resources/js/Components/NxLiveLoader.vue`
2. Props: `taskId: String`, `status: String`
3. Emits: `expand`, `collapse`
4. Template:
   ```vue
   <template>
     <div class="nx-live-loader" :class="{ expanded }">
       <button class="loader-pill" @click="toggle">
         <span class="pulse" />
         <span class="status-text">{{ statusText }}</span>
         <Icon :name="expanded ? 'chevron-up' : 'chevron-down'" :size="14" />
       </button>
       <div v-if="expanded" class="log-feed">
         <div v-for="(log, i) in logs" :key="i" class="log-line">
           <span class="timestamp">{{ log.timestamp }}</span>
           <span class="message">{{ log.message }}</span>
         </div>
       </div>
     </div>
   </template>
   ```
5. Script:
   - `expanded` ref (default false)
   - `logs` ref (empty array)
   - `toggle()` toggles expanded, emits expand/collapse
   - `onMounted`: subscribe to `window.Echo.private('tasks.' + taskId).listen('TaskCheckpoint', (e) => logs.push(e))`
   - `onUnmounted`: leave channel
6. Styles:
   - `.loader-pill`: `display: flex; align-items: center; gap: 8px; padding: 4px 12px; background: rgba(22,27,34,0.7); backdrop-filter: blur(12px); border: 1px solid rgba(255,255,255,0.1); border-radius: 9999px; cursor: pointer;`
   - `.pulse`: `width: 8px; height: 8px; border-radius: 50%; background: #007AFF; animation: pulse 2s ease-in-out infinite;`
   - `.log-feed`: `max-height: 200px; overflow-y: auto; font-family: 'JetBrains Mono'; font-size: 11px; background: rgba(0,0,0,0.3); margin-top: 8px; border-radius: 8px; padding: 8px;`
   - `.log-line`: `padding: 2px 0; border-bottom: 1px solid rgba(255,255,255,0.05); color: rgba(255,255,255,0.7);`
7. Save file and verify

## ✅ Final Verification
- [ ] Component created with taskId and status props
- [ ] Pulsing pill renders with pulse animation
- [ ] Click toggles expand/collapse
- [ ] Log feed shows when expanded
- [ ] Echo subscription for TaskCheckpoint events
- [ ] JetBrains Mono font in log feed
- [ ] No console errors
