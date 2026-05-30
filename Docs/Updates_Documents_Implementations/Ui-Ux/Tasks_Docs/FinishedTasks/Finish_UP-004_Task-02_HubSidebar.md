# 🎯 TASK: UP-004 - Task 02: HubSidebar Component (F-NAV-02)
- **Status:** � FINISHED
- **Dependencies:** UP-004_Task-01_NxNavRail

## 1. Objective
Create `HubSidebar.vue` — middle pane entity list with sticky search and sort filters.

## 2. Files to Create/Modify
- `resources/js/Components/HubSidebar.vue` (new)

## 3. Implementation Steps
1. Create `resources/js/Components/HubSidebar.vue`
2. Props: `collapsed: Boolean` (default: false)
3. Template: sticky search header + entity list
4. Script: computed `hubEntities` from route.meta.hub, `filteredEntities` with search/sort
5. Emits: `select`, `create`
6. Styles: 320px width, glass background, sticky header, entity items with status pills
7. Save file and verify

## ✅ Final Verification
- [ ] Component created with collapsed prop
- [ ] Search input filters entities
- [ ] Sort dropdown works
- [ ] Entity items show icon + name + status
- [ ] Empty state with create CTA
- [ ] Glassmorphism styling applied
- [ ] No console errors
