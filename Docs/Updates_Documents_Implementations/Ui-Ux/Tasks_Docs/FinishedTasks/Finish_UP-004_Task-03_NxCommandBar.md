# 🎯 TASK: UP-004 - Task 03: NxCommandBar Component (F-NAV-03)
- **Status:** � FINISHED
- **Dependencies:** UP-004_Task-02_HubSidebar

## 1. Objective
Create `NxCommandBar.vue` — universal command bar with fuzzy search triggered by Cmd+K/Ctrl+K.

## 2. Files to Create/Modify
- `resources/js/Components/NxCommandBar.vue` (new)

## 3. Implementation Steps
1. Create `resources/js/Components/NxCommandBar.vue`
2. Props: none
3. Emits: none (exposes `open` and `close` methods)
4. Template: overlay + centered dialog with input + results list
5. Script: keydown listener for Cmd+K/Ctrl+K, fuzzy search via API, localStorage for recent searches, keyboard navigation
6. Styles: frosted glass overlay, centered dialog, result groups, keyboard hints
7. Save file and verify

## ✅ Final Verification
- [ ] Component created
- [ ] Cmd+K/Ctrl+K opens command bar
- [ ] Fuzzy search works
- [ ] Recent searches shown from localStorage
- [ ] Keyboard navigation (↑/↓/Enter/Escape)
- [ ] Results grouped by type
- [ ] No console errors
