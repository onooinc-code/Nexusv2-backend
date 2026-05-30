# 🎯 TASK: UP-008 - Task 03: NxBottomSheet Component (F-MOB-03)
- **Status:** ✅ COMPLETED
- **Dependencies:** None

## 1. Objective
Create `NxBottomSheet.vue` — a mobile bottom sheet with drag handle, snap points, and dismiss behavior.

## 2. Files to Create/Modify
- `resources/js/Components/NxBottomSheet.vue` (new)
- `resources/js/Pages/*` mobile menus that should use bottom sheet

## 3. Implementation Steps
1. Build a fixed bottom sheet panel with a drag handle.
2. Add props `open`, `title`, and `snapPoints`.
3. Support drag interactions, snapping to nearest point on release.
4. Dismiss when the sheet is dragged down past threshold or backdrop is tapped.

## ✅ Final Verification
- [x] Bottom sheet opens to the first snap point
- [x] Dragging follows the finger
- [x] Release snaps to nearest point
- [x] Backdrop taps dismiss the sheet