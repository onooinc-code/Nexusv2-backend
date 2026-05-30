# UI/UX Specifications

## Design System
- **Palette**: Nexus Blue `#007AFF`, Hédra Purple `#6366F1`, Deep Space `#0B0E14`, Surface Dark `#161B22`.
- **Typography**: Inter (primary), JetBrains Mono (code). Headings, body, caption sizes defined in `Docs/First_Version_Docs/01-DESIGN_SYSTEM.md`.
- **Glassmorphism**: `rgba(22,27,34,.7)` background, `backdrop-filter: blur(12px)`, `1px solid rgba(255,255,255,.1)` border.
- **Elevation**: three shadow levels (cards, dropdowns, modals).
- **Spacing**: 4 px baseline grid; S 8 px, M 16 px, L 24 px, XL 32 px, XXL 48 px.
- **Icons**: Lucide Stroke 2 px.
- **Motion**: 300 ms slide‑up/fade‑in, 200 ms hover easing, AI pulsing glow.

## Core Components
- Buttons (`NxActionButton.vue`), inputs, cards (`NxGlassCard.vue`), loading states (`LoadingSpinner.vue`, `SkeletonLoader.vue`).
- Real‑time UI (`NxAiPulse.vue`, `NxLiveLoader.vue`, `GlobalJobMonitor.vue`).
- Navigation (`NxNavRail.vue`, `MobileHeader.vue`, `Breadcrumbs.vue`).
- Data visualisers (`NxTagCloud.vue`, `NxEmotionRadar.vue`, `NxMemoryMiniGraph.vue`).

## Interaction Patterns
- Async first: all >2 s actions dispatch jobs, UI shows loader.
- Private channel broadcasting only; events sanitised.
- Haptic feedback on mobile via `useHaptic.js`.
- Accessibility: focus rings, ARIA live regions (`NxLiveRegion.vue`).