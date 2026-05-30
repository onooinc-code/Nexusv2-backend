# 🎯 TASK: UP-001 - Task 21: useNotificationStore (Minimal for A10)
- **Status:** 🔴 PENDING
- **Dependencies:** UP-001_Task-06_Pinia_Initialization

## 1. Objective
Create a minimal `useNotificationStore` with only the state needed for `NxNotificationBell` (A10). The full store with Echo listeners and undo functionality will be completed in UP-003.

## 2. Files to Create/Modify
- `resources/js/stores/useNotificationStore.js` (new): Minimal implementation for notification bell

## 3. Implementation Steps
1. Create `resources/js/stores/useNotificationStore.js`
2. Import `defineStore` from 'pinia'
3. Define store with minimal state:
   ```javascript
   export const useNotificationStore = defineStore('notifications', {
     state: () => ({
       toasts: [],
       unreadCount: 0,
       pendingUndo: null,
     }),
     actions: {
       addToast(payload) {
         this.toasts.push({ ...payload, id: Date.now() });
         if (payload.type !== 'success') this.unreadCount++;
       },
       removeToast(id) {
         const index = this.toasts.findIndex(t => t.id === id);
         if (index > -1) this.toasts.splice(index, 1);
       },
       incrementUnread() { this.unreadCount++; },
       markAllRead() { this.unreadCount = 0; },
       setUndo(action) {
         this.pendingUndo = { ...action, expiresAt: Date.now() + 8000 };
       },
       clearUndo() { this.pendingUndo = null; },
     },
   });
   ```
4. Save file and verify

## ✅ Final Verification
- [ ] Store created with required state
- [ ] All actions defined
- [ ] `npm run dev` works without errors
- [ ] Vue DevTools shows `useNotificationStore`
- [ ] No console errors
