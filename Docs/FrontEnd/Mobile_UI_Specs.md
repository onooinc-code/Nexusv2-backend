# Mobile UI Specifications

## Responsive Breakpoints
- Mobile: 375 px max
- Tablet: 768 px max
- Desktop: 1440 px max

## Mobile‑First Components
- `MobileHeader.vue` – top navigation bar with haptic feedback.
- `NxBottomSheet.vue` – sheet action panel.
- `NxFab.vue` – floating action button.
- `NxPullRefresh.vue` – pull‑to‑refresh wrapper.
- `TouchControls.vue` – swipe and tap gestures.
- `NxRateLimitBanner.vue` – sticky error banner.

## Layout
- Stacked‑and‑slide layout replaces 3‑pane on mobile.
- Nav rail becomes hamburger menu.
- Modals stack vertically with `backdrop-blur`.

## Performance
- Lazy‑load analytics and provider onboarding components.
- Virtual scrolling pending for long lists.
- Use `navigator.vibrate` with feature detection.

## Mobile Test Scenarios
- Tap, swipe, pinch gestures on `TouchControls`.
- Keyboard dismissal on input focus.
- Orientation change preserves state.