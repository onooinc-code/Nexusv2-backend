# 🎯 TASK: UP-003 - Task 01: useChat Store (F-ST-01)
- **Status:** 🔴 PENDING
- **Dependencies:** UP-001_Task-06_Pinia_Initialization

## 1. Objective
Create `useChat.js` — Pinia store for chat state management including messages, streaming state, draft, and session management.

## 2. Files to Create/Modify
- `resources/js/stores/useChat.js` (new)

## 3. Implementation Steps
1. Create `resources/js/stores/useChat.js`
2. Import `defineStore` from 'pinia', `ref`, `computed` from 'vue'
3. Define store:
   ```javascript
   export const useChat = defineStore('chat', {
     state: () => ({
       messages: [],
       streaming: false,
       draft: '',
       sessionId: null,
       contextTokens: 0,
       maxTokens: 6000,
     }),
     getters: {
       currentSessionMessages: (state) => state.messages.filter(m => m.sessionId === state.sessionId),
       tokenPercentage: (state) => (state.contextTokens / state.maxTokens) * 100,
     },
     actions: {
       setSession(id) { this.sessionId = id; },
       setDraft(text) { this.draft = text; },
       clearDraft() { this.draft = ''; },
       addMessage(message) { this.messages.push(message); },
       sendMessage(content) {
         // Optimistic: add user message immediately
         const userMsg = { id: Date.now(), role: 'user', content, sessionId: this.sessionId, timestamp: new Date() };
         this.messages.push(userMsg);
         this.draft = '';
         // API call here (will be implemented in Phase 5)
         return userMsg;
       },
       streamToken(token) {
         if (this.messages.length > 0) {
           const lastMsg = this.messages[this.messages.length - 1];
           if (lastMsg.role === 'agent') {
             lastMsg.content += token;
             this.contextTokens++;
           }
         }
       },
       finalizeMessage() { this.streaming = false; },
       revertLastMessage() {
         if (this.messages.length > 0) {
           const removed = this.messages.pop();
           if (removed?.role === 'user') this.draft = removed.content;
         }
       },
     },
   });
   ```
4. Save file and verify

## ✅ Final Verification
- [ ] Store created with all required state
- [ ] Getters: currentSessionMessages, tokenPercentage
- [ ] Actions: sendMessage, streamToken, finalizeMessage, revertLastMessage
- [ ] `npm run dev` works without errors
- [ ] Vue DevTools shows useChat store
- [ ] No console errors
