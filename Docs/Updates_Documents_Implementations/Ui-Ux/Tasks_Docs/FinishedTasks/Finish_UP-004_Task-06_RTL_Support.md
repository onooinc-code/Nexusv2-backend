# UP-004_Task-06: RTL Support — Logical CSS Properties

## Task Overview
Add RTL support using logical CSS properties throughout the application.

- **Status:** 🟢 FINISHED

## Feature Specification
- **Feature ID:** F-RTL-01
- **Files:** All .vue files

## Requirements
1. Replace all hardcoded directional CSS with logical properties
2. border-r → border-e (end border); border-l → border-s (start border)
3. ml-3 → ms-3 (margin-start); mr-3 → me-3 (margin-end)
4. pl-4 → ps-4 (padding-start); pr-4 → pe-4 (padding-end)
5. text-left → text-start; text-right → text-end
6. Apply to: App.vue, NxNavRail.vue, HubSidebar.vue, NxStatusBar.vue, all existing components

## Implementation Details
- No logic change — purely CSS property substitution
- Tailwind v3+ supports logical properties natively
- When dir="rtl" on <html>, all layouts mirror automatically

## Verification
- `npm run build` passes
- Set document.documentElement.dir = 'rtl'
- Verify Nav Rail moves to right side
- Verify Hub Sidebar moves to left side
- Verify all text aligns right
