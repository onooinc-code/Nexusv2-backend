# 🎯 TASK: UP-004 - Task 04: App.vue 3-Pane Layout Rewrite (F-APP-01)
- **Status:** � FINISHED
- **Dependencies:** UP-004_Task-01_NxNavRail, UP-004_Task-02_HubSidebar, UP-004_Task-03_NxCommandBar

## 1. Objective
Rewrite `App.vue` to implement 3-pane architecture (Navigation Rail → Hub Sidebar → Workspace).

## 2. Files to Create/Modify
- `resources/js/App.vue` (rewrite)

## 3. Implementation Steps
1. Read current `App.vue`
2. Replace 2-pane layout with 3-pane: NxNavRail (80/240px) + HubSidebar (320px) + Workspace (flex)
3. Remove max-w-7xl width cap — workspace fills remaining space
4. Mount NxStatusBar (A01) below workspace header
5. Add breadcrumb trail in workspace header (already exists)
6. Mount MobileFooter.vue for mobile (< 768px)
7. Add NxTopBar (L07) at very top of viewport
8. Add skip-to-content link for accessibility (K01) — already exists
9. Save file and verify

## ✅ Final Verification
- [ ] 3-pane layout renders correctly
- [ ] NxNavRail visible on desktop
- [ ] HubSidebar visible between nav and workspace
- [ ] Workspace fills remaining space (no max-w-7xl)
- [ ] NxStatusBar mounted below header
- [ ] MobileFooter mounted for mobile
- [ ] Breadcrumbs visible in header
- [ ] No console errors
