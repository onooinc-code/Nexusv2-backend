# 🚀 UPDATE BLUEPRINT: UP-004 — Layout & Navigation Overhaul (Phase 4)

## Status: Completed ✅

## 1. Meta & Pre-flight Analysis

- **Features & Details:**
  - Rewrite `App.vue` to implement 3-pane architecture (Navigation Rail → Hub Sidebar → Workspace)
  - Create `NxNavRail.vue` — collapsible 80px/240px navigation rail (replaces `Navigation.vue`)
  - Create `HubSidebar.vue` — middle pane entity list with sticky search and sort filters
  - Create `NxCommandBar.vue` — Cmd+K universal command bar with fuzzy search
  - Fix `MobileFooter.vue` — mount in `App.vue`, fix tabs to Home/Memory/Contacts/Tasks/Search
  - Add breadcrumb trail to workspace header
  - Implement full RTL support (logical CSS properties: `border-e/s`, `ms-3` instead of `ml-3`)
  - Remove `max-w-7xl` width cap from `App.vue`

- **Project Context & Versions:**
  - Vue 3 Composition API with Vue Router
  - Tailwind CSS v3+ with design tokens from UP-001
  - Pinia stores available (UP-003)
  - Lucide icons registered globally (UP-001)

- **Regression Check:**
  - Complete `App.vue` rewrite — all existing route views continue to work
  - `Navigation.vue` replaced by `NxNavRail.vue` — all hub navigation links are preserved
  - Mobile footer now renders on devices below 768px and supports search voice input
  - RTL changes use logical properties — no LTR regressions expected

- **Progress:** 6/6 UP-004 tasks completed

---

## 2. Feature Specifications (Per Feature)

### Feature 1: NxNavRail.vue — Collapsible Navigation Rail (F-NAV-01)

- **Feature Name & ID:** NxNavRail — Collapsible Navigation Rail — F-NAV-01
- **Specs & Requirements:**
  - Replaces `Navigation.vue` (file: `resources/js/Components/Navigation.vue`)
  - Collapsible: `80px` (icon-only) ↔ `240px` (expanded with labels)
  - Collapse toggle: chevron button at bottom of rail

### Feature 2: HubSidebar.vue — Entity List Middle Pane (F-NAV-02)

- **Feature Name & ID:** HubSidebar — Entity List Middle Pane — F-NAV-02
- **Specs & Requirements:**
  - Sticky search bar, sort controls, list items with labels and status pills
  - Empty state with create CTA
  - Entity list changes by active hub context

### Feature 3: NxCommandBar.vue — Cmd+K Universal Search (F-NAV-03)

- **Feature Name & ID:** NxCommandBar — Universal Command Bar — F-NAV-03
- **Specs & Requirements:**
  - Open via Cmd+K / Ctrl+K or search button
  - Local fuzzy search across route hub names
  - Keyboard navigation and recent searches

### Feature 4: App.vue — 3-Pane Layout Rewrite (F-APP-01)

- **Feature Name & ID:** App Layout — F-APP-01
- **Specs & Requirements:**
  - Nav rail + hub sidebar + workspace layout
  - Includes `NxTopBar`, `Breadcrumbs`, `NxStatusBar`, and mobile footer

### Feature 5: MobileFooter.vue — Fixed Mobile Tabs (F-NAV-04)

- **Feature Name & ID:** MobileFooter — Mobile Tab Bar — F-NAV-04
- **Specs & Requirements:**
  - Tabs: Home, Memory, Contacts, Tasks, Search
  - Floating voice orb above the bar
  - Only visible on mobile widths

### Feature 6: Breadcrumbs — Workspace Header Trail (F-NAV-05)

- **Feature Name & ID:** Breadcrumbs — Workspace Header Trail — F-NAV-05
- **Specs & Requirements:**
  - Computed from `route.matched`
  - Clickable breadcrumb segments
  - Animated link hover state

---

## 3. Verification

- [x] `npm run build` passes for all updated components
- [x] `App.vue` renders a 3-pane layout on desktop
- [x] Mobile footer is visible on small screens
- [x] Cmd+K opens the command bar overlay
- [x] Breadcrumb trail renders from route metadata
- [x] RTL-friendly CSS applied to navigation and sidebar components
