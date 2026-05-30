# Mobile‑First Responsive Design

## Breakpoints
- Mobile: 320–767 px
- Tablet: 768–1439 px
- Desktop: 1440 px+

## Layout Transitions
| Component | Mobile | Tablet | Desktop |
|-----------|--------|--------|---------|
| Nav | Hamburger → `MobileHeader.vue` | Hamburger → `MobileHeader.vue` | `NxNavRail.vue` left rail |
| Content | Full‑width stacked | 2‑column | 3‑pane |
| Modals | Full‑screen overlay | Centered modal | Centered modal |
| Tables | Card list | Card list | Data table |

## Touch Targets
- Minimum 44 × 44 px for all interactive elements.
- `NxFab.vue` uses `48 px` radius.
- `TouchControls.vue` handles swipe gestures.

## Performance on Mobile
- Lazy load heavy components (`ContactAnalytics`, `NxProviderHealthModal`).
- Reduce animation complexity on low‑end devices.
- Use `prefers-reduced-motion` media query.

## Haptic Feedback
```js
// useHaptic.js
if (navigator.vibrate) {
  navigator.vibrate(10);
}
```
