# 🎯 TASK: UP-004 - Task 01: NxNavRail Component (F-NAV-01)
- **Status:** 🔴 PENDING
- **Dependencies:** UP-002_Task-04_NxActionButton

## 1. Objective
Create `NxNavRail.vue` — collapsible 80px/240px navigation rail (replaces `Navigation.vue`).

## 2. Files to Create/Modify
- `resources/js/Components/NxNavRail.vue` (new)

## 3. Implementation Steps
1. Create `resources/js/Components/NxNavRail.vue`
2. Props: `collapsed: Boolean` (default: false)
3. Template with 6 hub icons: Nexus, Agents, Memory, Contacts, Workflows, Settings
4. Bottom section: Settings button + collapse toggle
5. Script: computed `activeHub` from route, `navigateToHub` method, localStorage for collapsed state
6. Styles: 80px collapsed / 240px expanded, glass background, active state with left border
7. Save file and verify

## ✅ Final Verification
- [ ] Component created with collapsed prop
- [ ] 6 hub icons render correctly
- [ ] Collapse toggle works (80px ↔ 240px)
- [ ] Active hub highlighted with blue accent
- [ ] Glassmorphism styling applied
- [ ] RTL support via logical properties
- [ ] No console errors
