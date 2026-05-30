# 🎯 TASK: UP-003 - Task 02: useContacts Store (F-ST-02)
- **Status:** 🔴 PENDING
- **Dependencies:** UP-003_Task-01_useChat_Store

## 1. Objective
Create `useContacts.js` — Pinia store for contact state management including list, selection, search, and optimistic add.

## 2. Files to Create/Modify
- `resources/js/stores/useContacts.js` (new)

## 3. Implementation Steps
1. Create `resources/js/stores/useContacts.js`
2. Import `defineStore` from 'pinia', `ref`, `computed` from 'vue'
3. Define store:
   ```javascript
   export const useContacts = defineStore('contacts', {
     state: () => ({
       contacts: [],
       selected: null,
       loading: false,
       searchQuery: '',
     }),
     getters: {
       filteredContacts: (state) => {
         if (!state.searchQuery) return state.contacts;
         const q = state.searchQuery.toLowerCase();
         return state.contacts.filter(c => 
           c.canonical_name?.toLowerCase().includes(q) ||
           c.email?.toLowerCase().includes(q)
         );
       },
     },
     actions: {
       async fetchContacts() {
         this.loading = true;
         try {
           const { data } = await axios.get('/api/v1/contacts');
           this.contacts = data;
         } finally {
           this.loading = false;
         }
       },
       selectContact(id) {
         this.selected = this.contacts.find(c => c.id === id) || null;
       },
       async addContact(data) {
         // Optimistic add
         const tempId = 'temp-' + Date.now();
         const optimisticContact = { ...data, id: tempId, _optimistic: true };
         this.contacts.unshift(optimisticContact);
         try {
           const { data: created } = await axios.post('/api/v1/contacts', data);
           const index = this.contacts.findIndex(c => c.id === tempId);
           if (index > -1) this.contacts.splice(index, 1, created);
           return created;
         } catch (error) {
           // Revert on error
           const index = this.contacts.findIndex(c => c.id === tempId);
           if (index > -1) this.contacts.splice(index, 1);
           throw error;
         }
       },
       async updateContact(id, data) {
         const index = this.contacts.findIndex(c => c.id === id);
         if (index > -1) {
           const original = { ...this.contacts[index] };
           this.contacts[index] = { ...this.contacts[index], ...data };
           try {
             await axios.put(`/api/v1/contacts/${id}`, data);
           } catch (error) {
             this.contacts[index] = original;
             throw error;
           }
         }
       },
       async deleteContact(id) {
         const index = this.contacts.findIndex(c => c.id === id);
         if (index > -1) {
           const original = this.contacts[index];
           this.contacts.splice(index, 1);
           try {
             await axios.delete(`/api/v1/contacts/${id}`);
           } catch (error) {
             this.contacts.splice(index, 0, original);
             throw error;
           }
         }
       },
       setSearchQuery(query) { this.searchQuery = query; },
     },
   });
   ```
4. Save file and verify

## ✅ Final Verification
- [ ] Store created with all required state
- [ ] Getters: filteredContacts
- [ ] Actions: fetchContacts, selectContact, addContact (optimistic), updateContact (optimistic), deleteContact (optimistic), setSearchQuery
- [ ] `npm run dev` works without errors
- [ ] Vue DevTools shows useContacts store
- [ ] No console errors
