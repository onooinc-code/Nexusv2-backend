# UP-004_Task-07: Breadcrumbs — Workspace Header Trail

## Task Overview
Add breadcrumb trail to workspace header in App.vue.

- **Status:** 🟢 FINISHED

## Feature Specification
- **Feature ID:** F-NAV-05
- **File:** `resources/js/Components/Breadcrumbs.vue` (modify) or inline in App.vue

## Requirements
1. Add breadcrumb to workspace header in App.vue
2. Format: Hub / Entity / Detail (e.g., Contacts / Ahmed Ali / Profile)
3. Each segment is clickable (navigates to that level)
4. Animation: new crumb slides in from translateX(8px) opacity(0) in 150ms; old crumbs slide out left

## Implementation Details
- Computed from route.matched array
- Each matched route has meta.breadcrumb label
- Click navigates to that route level

## Verification
- `npm run build` passes
- Breadcrumb shows correct path
- Click navigates to that level
- Animation works on route change
