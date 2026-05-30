# 🎯 TASK: UP-003 - Task 03: useWorkflows Store (F-ST-03)
- **Status:** 🔴 PENDING
- **Dependencies:** UP-003_Task-01_useChat_Store

## 1. Objective
Create `useWorkflows.js` — Pinia store for workflow state management including workflows list, current workflow, selected step, and execution progress.

## 2. Files to Create/Modify
- `resources/js/stores/useWorkflows.js` (new)

## 3. Implementation Steps
1. Create `resources/js/stores/useWorkflows.js`
2. Import `defineStore` from 'pinia', `ref`, `computed` from 'vue'
3. Define store:
   ```javascript
   export const useWorkflows = defineStore('workflows', {
     state: () => ({
       workflows: [],
       current: null,
       selectedStep: null,
       executionProgress: 0,
     }),
     getters: {
       currentWorkflowSteps: (state) => state.current?.steps || [],
       isExecuting: (state) => state.executionProgress > 0 && state.executionProgress < 100,
     },
     actions: {
       async fetchWorkflows() {
         const { data } = await axios.get('/api/v1/workflows');
         this.workflows = data;
       },
       selectWorkflow(id) {
         this.current = this.workflows.find(w => w.id === id) || null;
         this.selectedStep = null;
         this.executionProgress = 0;
       },
       selectStep(stepId) {
         this.selectedStep = this.current?.steps.find(s => s.id === stepId) || null;
       },
       updateStepStatus(stepId, status) {
         const step = this.current?.steps.find(s => s.id === stepId);
         if (step) step.status = status;
       },
       setExecutionProgress(progress) {
         this.executionProgress = Math.min(100, Math.max(0, progress));
       },
     },
   });
   ```
4. Save file and verify

## ✅ Final Verification
- [ ] Store created with all required state
- [ ] Getters: currentWorkflowSteps, isExecuting
- [ ] Actions: fetchWorkflows, selectWorkflow, selectStep, updateStepStatus, setExecutionProgress
- [ ] `npm run dev` works without errors
- [ ] Vue DevTools shows useWorkflows store
- [ ] No console errors
