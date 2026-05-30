# 🎯 TASK: UP-001 - Task 20: NxNotificationBell Component (A10)
- **Status:** 🔴 PENDING
- **Dependencies:** UP-001_Task-09_NxStatusBar_Component, UP-001_Task-19_useSystem_Store_Minimal

## 1. Objective
Create `NxNotificationBell.vue` — a bell icon in the status bar showing unread count badge, with shake animation on new notifications.

## 2. Files to Create/Modify
- `resources/js/Components/NxNotificationBell.vue` (new)

## 3. Implementation Steps
1. Create `resources/js/Components/NxNotificationBell.vue`
2. Props: None (reads from `useNotificationStore`)
3. Emits: `open-drawer`
4. Template:
   ```vue
   <template>
     <button class="nx-notification-bell" @click="$emit('open-drawer')">
       <Icon name="bell" :size="20" />
       <span v-if="unreadCount > 0" class="badge">{{ unreadCount > 99 ? '99+' : unreadCount }}</span>
     </button>
   </template>
   ```
5. Script:
   - Import `useNotificationStore` from `../../stores/useNotificationStore`
   - Get `unreadCount` from store
   - Watch `unreadCount` — when it increases, trigger shake animation on bell element via `ref`
6. Styles:
   - Base: `position: relative; background: none; border: none; color: var(--color-text-secondary); cursor: pointer; padding: 8px; border-radius: 8px; transition: all 0.2s ease;`
   - Hover: `background: rgba(255,255,255,0.05); color: var(--color-text-primary);`
   - `.badge`: `position: absolute; top: 4px; right: 4px; background: #EF4444; color: white; border-radius: 9999px; min-width: 16px; height: 16px; font-size: 10px; display: flex; align-items: center; justify-content: center; padding: 0 4px;`
   - Shake animation: `@keyframes bell-shake { 0%, 100% { transform: rotate(0); } 25% { transform: rotate(-15deg); } 75% { transform: rotate(15deg); } }`
7. Save file and verify

## ✅ Final Verification
- [ ] Component created with bell icon
- [ ] Badge shows unread count
- [ ] Badge shows '99+' when count > 99
- [ ] Shake animation triggers on count increase
- [ ] Click emits open-drawer event
- [ ] Used in `NxStatusBar` right zone
- [ ] No console errors
