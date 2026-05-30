# 🎯 TASK: UP-008 - Task 04: NxContextMenu Component (F-MOB-04)
- **Status:** ✅ COMPLETED
- **Dependencies:** None

## 1. Objective
Create `NxContextMenu.vue` — a long-press / right-click context menu with haptic feedback and action selection.

## 2. Files to Create/Modify
- `resources/js/Components/NxContextMenu.vue` (new)
- `resources/js/Pages/*` list views that support long-press menus

## 3. Implementation Steps
1. Create a context menu component positioned at touch/click coordinates.
2. Implement a 500ms long-press timer and cancel logic.
3. Support desktop right-click to open the menu.
4. Emit `select` when an item is chosen and `close` when the menu is dismissed.

## ✅ Final Verification
- [x] Long-press opens the menu after 500ms
- [x] Right-click opens the menu on desktop
- [x] Menu items trigger actions
- [x] Haptic feedback triggers on mobile