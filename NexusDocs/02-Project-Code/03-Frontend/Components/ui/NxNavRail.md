# NxNavRail

## Specification
- **File**: `components/layout/NxNavRail.tsx`
- **Width**: 80px (Collapsed) / 240px (Expanded)
- **Transition**: 250ms ease-in-out width

## Features
- **Collapsible State**: Persists in `localStorage` as `nx-nav-rail-state`.
- **Icons**: Lucide React with 2px stroke width.
- **Active State**: High-contrast indicator on the left edge or background highlight.
- **Mobile Behavior**: Hidden by default, toggled via hamburger menu in `NxStatusBar`.