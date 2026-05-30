# 🎯 TASK: UP-001 - Task 19: useSystem Store (Minimal for Status Bar)
- **Status:** 🔴 PENDING
- **Dependencies:** UP-001_Task-06_Pinia_Initialization

## 1. Objective
Create a minimal `useSystem` store with only the state needed for the Status Bar components (A01–A10). The full store implementation with all features will be completed in UP-003.

## 2. Files to Create/Modify
- `resources/js/stores/useSystem.js` (new): Minimal implementation for status bar

## 3. Implementation Steps
1. Create `resources/js/stores/useSystem.js`
2. Import `defineStore` from 'pinia'
3. Define store with minimal state:
   ```javascript
   export const useSystem = defineStore('system', {
     state: () => ({
       connectionState: 'connecting',
       jobProgress: 0,
       queueDepth: 0,
       hasQueueFailures: false,
       activeAgentCount: 0,
       tokenUsed: 0,
       tokenBudget: 6000,
       providers: [],
       rateLimitInfo: null,
     }),
     actions: {
       setConnectionState(state) { this.connectionState = state; },
       updateJobProgress(progress) { this.jobProgress = progress; },
       updateQueueDepth(depth) { this.queueDepth = depth; },
       setQueueFailures(hasFailures) { this.hasQueueFailures = hasFailures; },
       setActiveAgentCount(count) { this.activeAgentCount = count; },
       setTokenUsage(used, budget) { this.tokenUsed = used; this.tokenBudget = budget; },
       setProviders(providers) { this.providers = providers; },
       setRateLimit(info) { this.rateLimitInfo = info; },
       clearRateLimit() { this.rateLimitInfo = null; },
     },
   });
   ```
4. Save file and verify

## ✅ Final Verification
- [ ] Store created with all required state for status bar
- [ ] All actions defined
- [ ] `npm run dev` works without errors
- [ ] Vue DevTools shows `useSystem` store
- [ ] No console errors
