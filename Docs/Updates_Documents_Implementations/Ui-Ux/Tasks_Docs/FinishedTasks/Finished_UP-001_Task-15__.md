# 🎯 TASK: UP-001 - Task 15: NxRateLimitBanner Component (A06)
- **Status:** 🔴 PENDING
- **Dependencies:** UP-001_Task-09_NxStatusBar_Component, UP-001_Task-19_useSystem_Store

## 1. Objective
Create `NxRateLimitBanner.vue` — a dismissible amber banner that appears when a provider reports rate limit (429).

## 2. Files to Create/Modify
- `resources/js/Components/NxRateLimitBanner.vue` (new)

## 3. Implementation Steps
1. Create `resources/js/Components/NxRateLimitBanner.vue`
2. Props: `provider: String`, `resetAt: Date`, `visible: Boolean`
3. Emits: `dismiss`, `switch-provider`
4. Template:
   ```vue
   <template>
     <Transition name="slide-down">
       <div v-if="visible" class="nx-rate-limit-banner">
         <span class="message">
           <Icon name="alert-triangle" :size="16" />
           {{ provider }} rate limited. Resets in {{ countdown }}.
         </span>
         <button class="switch-btn" @click="$emit('switch-provider')">Switch Provider</button>
         <button class="dismiss-btn" @click="$emit('dismiss')">
           <Icon name="x" :size="14" />
         </button>
       </div>
     </Transition>
   </template>
   ```
5. Script:
   - Import `ref`, `computed`, `onMounted`, `onUnmounted` from 'vue'
   - `countdown` computed from `resetAt` (format mm:ss)
   - `setInterval` to update countdown every second
6. Styles:
   - Base: `height: 36px; background: rgba(245, 158, 11, 0.15); border-bottom: 1px solid rgba(245, 158, 11, 0.3); color: #F59E0B; display: flex; align-items: center; justify-content: center; gap: 16px; padding: 0 16px; font-size: 13px;`
   - Animation: `@keyframes shake { 0%, 100% { transform: translateX(0); } 25% { transform: translateX(-2px); } 75% { transform: translateX(2px); } }` — apply every 5s
7. Save file and verify

## ✅ Final Verification
- [ ] Component created with provider, resetAt, visible props
- [ ] Countdown timer updates every second
- [ ] Dismiss button works
- [ ] Switch Provider button emits event
- [ ] Slides in with animation
- [ ] Shakes gently every 5s
- [ ] No console errors
