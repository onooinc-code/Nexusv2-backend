# 🚀 UPDATE BLUEPRINT: UP-008 — Mobile & Touch Enhancements (Phase 4)

## 1. Meta & Pre-flight Analysis

- **Features & Details:**
  - [x] Implement swipe-right-to-go-back gesture (J01)
  - [x] Implement pull-to-refresh on all lists (J02)
  - [x] Create `NxBottomSheet.vue` for mobile menus (J03)
  - [x] Create `NxContextMenu.vue` for long-press context menus (J04)
  - [x] Create `NxFab.vue` floating action button (J05)
  - All touch targets must be `≥ 44×44px` per mobile compliance rule
  - Haptic feedback via `navigator.vibrate()` on mobile

- **Project Context & Versions:**
  - Vue 3 Composition API
  - Touch events: `touchstart`, `touchmove`, `touchend`
  - Mobile breakpoint: `< 768px`
  - Design tokens from UP-001

- **Regression Check:**
  - All new components are mobile-only or enhanced desktop — no LTR breakage
  - Swipe gesture only active on mobile (`< 768px`)
  - Bottom sheet replaces dropdowns on mobile — verify desktop dropdowns still work

---

## 2. Feature Specifications (Per Feature)

### Feature 1: Swipe-Right-to-Go-Back Gesture (F-MOB-01)

- **Feature Name & ID:** Swipe-Back Gesture — F-MOB-01
- **Specs & Requirements:**
  - On mobile, swiping right from left edge (within 50px) initiates go-back
  - Current detail view slides right with finger, revealing list view behind
  - Props: None (global behavior)
  - Animation: panel follows touch with `transform: translateX(${delta}px)`; on release, completes to `100%` or snaps back based on velocity
  - Haptic: `navigator.vibrate([15])` on successful back navigation

- **UI/UX Specs:**
  - Touch threshold: start within `50px` of left edge
  - Velocity threshold: `> 0.3` (px/ms) triggers complete; else snap back
  - Distance threshold: `> 100px` triggers complete regardless of velocity
  - `touch-action: pan-y` on content to allow vertical scroll

- **Logic Workflow:**
  - `touchstart`: record `startX`, `startY`, `timestamp`
  - `touchmove`: calculate `deltaX`, `deltaY`; if horizontal swipe dominant, apply `translateX(deltaX)` to detail panel
  - `touchend`: calculate velocity; if thresholds met, navigate back; else animate back to `translateX(0)`

- **Technical Workflow:**
  - Enhancement to `App.vue` mobile layout
  - Add `@touchstart`, `@touchmove`, `@touchend` to detail view container
  - Computed: `shouldHandleSwipe` (within 50px edge, horizontal dominant)

- **Backend Readiness:** N/A
- **Required Libraries:** None
- **Class/Component Names:** `App.vue` (mobile enhancement)
- **Functions to Modify/Create:**
  - `handleTouchStart(e)` — record start position
  - `handleTouchMove(e)` — apply translateX
  - `handleTouchEnd(e)` — determine complete or snap back

---

### Feature 2: Pull-to-Refresh (F-MOB-02)

- **Feature Name & ID:** Pull-to-Refresh — F-MOB-02
- **Specs & Requirements:**
  - All scrollable lists support pull-to-refresh on mobile
  - Refresh indicator (spinning ring) appears when pulled down past `60px` threshold
  - Animation: elastic pull with `transform: translateY()` follows finger; indicator rotates and bounces
  - Triggers `refresh()` function on parent component

- **UI/UX Specs:**
  - Indicator: `position: absolute; top: 0; left: 50%; transform: translateX(-50%) translateY(-60px); width: 24px; height: 24px; border: 2px solid rgba(255,255,255,0.3); border-top-color: #007AFF; border-radius: 50%`
  - Pull distance: `transform: translateY(${Math.min(pullDistance, 80)}px)`
  - Spinner rotation: `transform: rotate(${pullDistance * 2}deg)`

- **Logic Workflow:**
  - `touchstart`: record `startY`
  - `touchmove`: if at scroll top, calculate `pullDistance = currentY - startY`; apply transform
  - `touchend`: if `pullDistance > 60`, trigger `refresh()`; else animate back to 0

- **Technical Workflow:**
  - File: `resources/js/Components/NxPullRefresh.vue` (new wrapper component)
  - Props: `refresh: Function` (async)
  - Emits: `refresh`
  - Template: `<div class="pull-refresh" @touchstart="onStart" @touchmove="onMove" @touchend="onEnd"><slot /><div class="indicator" :style="indicatorStyle" /></div>`

- **Backend Readiness:** N/A
- **Required Libraries:** None
- **Class/Component Names:** `NxPullRefresh.vue`
- **Functions to Modify/Create:**
  - `onStart(e)` — record startY
  - `onMove(e)` — calculate pullDistance
  - `onEnd(e)` — trigger refresh or snap back

---

### Feature 3: NxBottomSheet.vue — Mobile Bottom Sheet (F-MOB-03)

- **Feature Name & ID:** NxBottomSheet — Mobile Bottom Sheet — F-MOB-03
- **Specs & Requirements:**
  - Replaces dropdown menus and modals on mobile
  - Slides up from bottom with drag handle
  - Can be dragged up for full-screen or down to dismiss
  - Props: `open: Boolean`, `title: String`, `snapPoints: Array<Number>` (e.g., [0.4, 0.9])
  - Animation: `transform: translateY()` follows drag; on release, snaps to nearest snap point with spring physics
  - Backdrop: semi-transparent; tap to dismiss

- **UI/UX Specs:**
  - Sheet: `position: fixed; bottom: 0; left: 0; right: 0; background: rgba(22,27,34,0.95); backdrop-filter: blur(20px); border-top: 1px solid rgba(255,255,255,0.1); border-radius: 16px 16px 0 0; transform: translateY(100%); transition: transform 300ms cubic-bezier(0.4, 0, 0.2, 1)`
  - Drag handle: `width: 40px; height: 4px; background: rgba(255,255,255,0.3); border-radius: 2px; margin: 8px auto`
  - Snap: `transform: translateY(calc(100% - var(--snap-point) * 100vh))`

- **Logic Workflow:**
  - `open = true`: animate to first snap point
  - Drag: calculate `translateY` from touch position
  - Release: find nearest snap point, animate to it
  - Drag past threshold: dismiss

- **Technical Workflow:**
  - File: `resources/js/Components/NxBottomSheet.vue` (new)
  - Props: `open: Boolean`, `title: String`, `snapPoints: Array`
  - Emits: `close`, `snap-change`
  - State: `currentSnap: Number`, `dragY: Number`

- **Backend Readiness:** N/A
- **Required Libraries:** None
- **Class/Component Names:** `NxBottomSheet.vue`
- **Functions to Modify/Create:**
  - `handleDrag(e)` — update translateY
  - `handleRelease()` — snap to nearest point or dismiss

---

### Feature 4: NxContextMenu.vue — Long-Press Context Menu (F-MOB-04)

- **Feature Name & ID:** NxContextMenu — Long-Press Context Menu — F-MOB-04
- **Specs & Requirements:**
  - Long-press (500ms) any list item opens context menu
  - Desktop: right-click also triggers
  - Props: `items: Array<{ label, icon, action, danger? }>`
  - Animation: menu appears from long-press point with scale-in from 0.8 origin
  - Haptic: `navigator.vibrate([20])` on long-press trigger (mobile only)

- **UI/UX Specs:**
  - Menu: `position: fixed; background: rgba(22,27,34,0.95); backdrop-filter: blur(20px); border: 1px solid rgba(255,255,255,0.1); border-radius: 8px; padding: 4px; min-width: 180px; z-index: 100`
  - Item: `display: flex; align-items: center; gap: 8px; padding: 8px 12px; border-radius: 4px; font-size: 14px`
  - Danger item: `color: #EF4444`
  - Animation: `transform: scale(0.8) opacity(0)` → `scale(1) opacity(1)` in `150ms`

- **Logic Workflow:**
  - Long-press: start 500ms timer; if released before, cancel; if completes, open menu
  - Right-click: open menu at cursor position
  - Item click: execute `action`, close menu

- **Technical Workflow:**
  - File: `resources/js/Components/NxContextMenu.vue` (new)
  - Props: `items: Array`, `x: Number`, `y: Number`, `visible: Boolean`
  - Emits: `select`, `close`
  - State: `longPressTimer: Number`

- **Backend Readiness:** N/A
- **Required Libraries:** `lucide-vue-next`
- **Class/Component Names:** `NxContextMenu.vue`
- **Functions to Modify/Create:**
  - `startLongPress(e)` — start 500ms timer
  - `cancelLongPress()` — clear timer
  - `openMenu(x, y)` — position and show
  - `selectItem(item)` — execute action

---

### Feature 5: NxFab.vue — Floating Action Button (F-MOB-05)

- **Feature Name & ID:** NxFab — Floating Action Button — F-MOB-05
- **Specs & Requirements:**
  - Circular FAB in bottom-right corner (above Bottom Tab Bar)
  - Shows primary action for active hub
  - Tap expands to show 3–5 secondary actions as smaller buttons radiating upward
  - Props: `actions: Array<{ icon, label, handler }>`, `mainIcon: String`
  - Animation: expand: secondary buttons scatter upward with 100ms stagger + spring scale; hub change: icon morphs with 200ms cross-fade

- **UI/UX Specs:**
  - Main FAB: `width: 56px; height: 56px; border-radius: 50%; background: #007AFF; color: white; box-shadow: 0 4px 12px rgba(0,122,255,0.4)`
  - Secondary: `width: 44px; height: 44px; border-radius: 50%; background: rgba(22,27,34,0.9); border: 1px solid rgba(255,255,255,0.1)`
  - Hub actions: Contacts → "New Contact"; Memory → "Add Memory"; Workflows → "New Workflow"; Chat → "New Session"

- **Logic Workflow:**
  - `expanded` state toggles on main FAB click
  - Secondary action click: execute `handler`, collapse
  - Hub change: update `mainIcon` and `actions` array

- **Technical Workflow:**
  - File: `resources/js/Components/NxFab.vue` (new)
  - Props: `actions: Array`, `mainIcon: String`
  - Emits: `action`
  - State: `expanded: Boolean`
  - Icons: Lucide icons per action

- **Backend Readiness:** N/A
- **Required Libraries:** `lucide-vue-next`
- **Class/Component Names:** `NxFab.vue`
- **Functions to Modify/Create:**
  - `toggle()` — expand/collapse
  - `handleAction(action)` — emit action event

---

## 3. Testing Strategy

### Automated Testing

- **Unit Tests (Vitest):**
  - `SwipeBack.spec.ts`: Test swipe threshold detection; test velocity calculation; test navigation trigger
  - `NxPullRefresh.spec.ts`: Test pull distance calculation; test refresh trigger at 60px; test snap back
  - `NxBottomSheet.spec.ts`: Test snap points; test drag to dismiss; test backdrop click
  - `NxContextMenu.spec.ts`: Test long-press 500ms trigger; test right-click; test item selection
  - `NxFab.spec.ts`: Test expand/collapse; test action click; test hub change

### Manual Testing Steps

1. **Swipe-Back (Mobile 375px):**
   - Navigate to contact detail → swipe right from left edge → verify view slides back
   - Swipe partially (50px) and release → verify snap back
   - Swipe fast with high velocity → verify completes even at short distance

2. **Pull-to-Refresh:**
   - Pull down on contact list → verify indicator appears at 60px
   - Release → verify refresh triggers, spinner rotates
   - Pull past 80px → verify elastic bounce

3. **Bottom Sheet:**
   - Open bottom sheet → verify slides up to first snap point (40%)
   - Drag up → verify follows finger
   - Release → verify snaps to nearest point
   - Drag down past threshold → verify dismisses

4. **Context Menu:**
   - Long-press contact item (500ms) → verify haptic feedback, menu appears
   - Right-click contact item (desktop) → verify menu appears at cursor
   - Click menu item → verify action executes, menu closes

5. **FAB:**
   - Tap main FAB → verify secondary actions expand upward with stagger
   - Tap secondary action → verify action executes, FAB collapses
   - Navigate to different hub → verify FAB icon morphs

6. **Touch Target Test:**
   - Verify all interactive elements are `≥ 44×44px` using Chrome DevTools device toolbar
   - Test on real mobile device if available

7. **Haptic Test:**
   - Trigger swipe-back → verify light vibration `[15]`
   - Long-press context menu → verify vibration `[20]`
   - Complete action → verify success vibration `[15, 50, 15]`
