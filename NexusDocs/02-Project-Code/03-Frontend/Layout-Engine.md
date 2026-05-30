# Layout Engine & Customizable Hubs

## Overview
Nexus allows users to customize their dashboard and hub layouts. This is managed by a flexible grid system that persists user preferences.

## 1. Grid System
- **Library**: `react-grid-layout` (or similar responsive grid library).
- **Columns**: 12-column grid system.
- **Breakpoints**: lg: 1200, md: 996, sm: 768, xs: 480.

## 2. Layout Persistence
- **Storage**: User layouts are saved to the Laravel backend via `POST /api/v1/user/settings/layouts`.
- **Local Cache**: Layouts are cached in `localStorage` for immediate application on load before the API responds.

## 3. Component Registry
All "Widget" components must be registered in the `LayoutRegistry`:
- `NxStatusBar`
- `NxAiChat`
- `NxContactList`
- `NxTaskQueue`
- `NxAnalyticsOverview`

## 4. Edit Mode
- **Toggle**: Users enter "Edit Mode" from the System Settings.
- **Interactions**:
  - Drag-and-drop to reposition.
  - Resize handles for supported widgets.
  - "Add Widget" gallery drawer.
- **Saving**: "Save Layout" button triggers the API sync and updates the global `useSystemStore`.