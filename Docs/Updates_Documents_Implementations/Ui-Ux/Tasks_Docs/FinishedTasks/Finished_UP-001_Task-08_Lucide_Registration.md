# 🎯 TASK: UP-001 - Task 08: Lucide Icon Registration
- **Status:** 🔴 PENDING
- **Dependencies:** UP-001_Task-01_Package_Installation

## 1. Objective
Register Lucide Vue Next as a global Vue plugin with 2px stroke width so that `<Icon>` components are available throughout the app.

## 2. Files to Create/Modify
- `resources/js/app.js`: Register LucideVueNext plugin

## 3. Implementation Steps
1. Open `resources/js/app.js`
2. Add import: `import LucideVueNext from 'lucide-vue-next';`
3. After Pinia registration, add: `app.use(LucideVueNext, { strokeWidth: 2 });`
4. Final file should look like:
   ```javascript
   import './bootstrap';
   import { createApp } from 'vue';
   import { createPinia } from 'pinia';
   import LucideVueNext from 'lucide-vue-next';
   import App from './App.vue';

   const app = createApp(App);
   app.use(createPinia());
   app.use(LucideVueNext, { strokeWidth: 2 });
   app.mount('#app');
   ```
5. Save file and verify no errors

## ✅ Final Verification
- [ ] LucideVueNext imported
- [ ] Registered with `strokeWidth: 2`
- [ ] `npm run dev` works without errors
- [ ] `<Icon name="search" />` renders in any component
- [ ] No console errors about missing icons
