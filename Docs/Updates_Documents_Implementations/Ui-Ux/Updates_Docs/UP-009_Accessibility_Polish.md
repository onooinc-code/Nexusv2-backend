# 🚀 UPDATE BLUEPRINT: UP-009 — Accessibility & UX Polish (Phase 4–5)

## 1. Meta & Pre-flight Analysis

- **Features & Details:**
  - [x] Implement skip-to-content link (K01)
  - [x] Enhance custom keyboard focus ring (K02)
  - [x] Create `NxLiveRegion.vue` for screen reader announcements (K03)
  - [x] Add High Contrast theme (K04)
  - [x] Add Reduced Motion preference support (K05)
  - [x] Create `NxOfflineBanner.vue` with request queue (K06)
  - [x] Create `NxCelebration.vue` for milestone completions (K07)
  - Target: WCAG 2.1 AA compliance

- **Project Context & Versions:**
  - Vue 3 Composition API
  - CSS media queries for accessibility preferences
  - `localStorage` for offline queue persistence
  - Design tokens from UP-001

- **Regression Check:**
  - All accessibility enhancements are additive — no existing functionality broken
  - High Contrast theme adds new CSS variable block — verify existing themes unaffected
  - Reduced Motion wraps all animations — verify animations still work when preference is `no-preference`

---

## 2. Feature Specifications (Per Feature)

### Feature 1: Skip-to-Content Link (F-ACC-01)

- **Feature Name & ID:** Skip-to-Content — F-ACC-01
- **Specs & Requirements:**
  - Visually hidden `<a href="#main-content">Skip to main content</a>` link
  - Becomes visible on keyboard focus
  - Positioned at top of DOM before all navigation
  - Styling: `position: fixed; top: 0; left: 0; z-index: 9999` on `:focus`

- **UI/UX Specs:**
  - Default: `position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0, 0, 0, 0); white-space: nowrap; border: 0`
  - Focus: `position: fixed; top: 8px; left: 8px; width: auto; height: auto; padding: 8px 16px; background: #007AFF; color: white; border-radius: 4px; z-index: 9999`

- **Logic Workflow:** N/A — static HTML/CSS
- **Technical Workflow:**
  - File: `resources/js/App.vue` (add at top of template)
  - Add `<a href="#main-content" class="skip-link">Skip to main content</a>`
  - Add `#main-content` id to main content container

- **Backend Readiness:** N/A
- **Required Libraries:** None
- **Class/Component Names:** `App.vue`
- **Functions to Modify/Create:** None

---

### Feature 2: Custom Keyboard Focus Ring (F-ACC-02)

- **Feature Name & ID:** Custom Focus Ring — F-ACC-02
- **Specs & Requirements:**
  - Enhance `:focus-visible` to use Nexus Blue `#007AFF` as outline color with `2px` offset
  - Remove default browser ring while maintaining keyboard accessibility
  - Already partially implemented at `app.css:151` — needs color fix from `--color-border-focus` (currently `#4ade80`) to `#007AFF`

- **UI/UX Specs:**
  - `:focus-visible { outline: 2px solid #007AFF; outline-offset: 2px; }`
  - `:focus:not(:focus-visible) { outline: none; }` — remove ring for mouse clicks

- **Logic Workflow:** N/A — CSS only
- **Technical Workflow:**
  - File: `resources/css/app.css` (modify lines 151–154)
  - Change `outline-color: var(--color-border-focus)` to `outline-color: #007AFF`
  - Add `:focus:not(:focus-visible) { outline: none; }`

- **Backend Readiness:** N/A
- **Required Libraries:** None
- **Class/Component Names:** `app.css`
- **Functions to Modify/Create:** None

---

### Feature 3: NxLiveRegion.vue — Screen Reader Announcements (F-ACC-03)

- **Feature Name & ID:** NxLiveRegion — Screen Reader Live Region — F-ACC-03
- **Specs & Requirements:**
  - ARIA live region that announces dynamic content changes
  - Props: `message: String`, `politeness: 'polite' | 'assertive'`
  - Mounted in `App.vue`; `useNotificationStore` writes to it on each new notification
  - Used for: toast notifications, task completion, agent status changes

- **UI/UX Specs:**
  - `position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0, 0, 0, 0); white-space: nowrap; border: 0` — visually hidden but accessible
  - `aria-live="polite"` or `aria-live="assertive"` based on politeness prop
  - `aria-atomic="true"` — announce entire message

- **Logic Workflow:**
  - Watch `message` prop — when it changes, update `innerText` of live region
  - Clear message after announcement (set to empty string after 1s)

- **Technical Workflow:**
  - File: `resources/js/Components/NxLiveRegion.vue` (new)
  - Props: `message: String`, `politeness: String`
  - Template: `<div :aria-live="politeness" :aria-atomic="true" role="status">{{ message }}</div>`
  - Watch: `watch(message, (newMsg) => { regionRef.innerText = newMsg; setTimeout(() => regionRef.innerText = '', 1000); })`

- **Backend Readiness:** N/A
- **Required Libraries:** None
- **Class/Component Names:** `NxLiveRegion.vue`
- **Functions to Modify/Create:** None

---

### Feature 4: High Contrast Theme (F-ACC-04)

- **Feature Name & ID:** High Contrast Theme — F-ACC-04
- **Specs & Requirements:**
  - Fourth theme option in `NxThemeSwitcher`: "High Contrast"
  - Replaces all semi-transparent surfaces with fully opaque ones
  - Increases text contrast ratio to `≥ 7:1` (WCAG AAA)
  - CSS: `[data-theme="high-contrast"]` CSS variable block

- **UI/UX Specs:**
  - `--color-bg-primary: #000000; --color-bg-secondary: #000000; --color-bg-tertiary: #1a1a1a`
  - `--color-bg-glass: #000000` (no transparency)
  - `--color-text-primary: #ffffff; --color-text-secondary: #f0f0f0; --color-text-muted: #d0d0d0`
  - `--color-border: #ffffff; --color-border-hover: #ffffff`
  - Remove all `backdrop-filter: blur()` in high-contrast mode

- **Logic Workflow:**
  - `NxThemeSwitcher` adds "High Contrast" option
  - Selecting sets `data-theme="high-contrast"` on `<html>`
  - Persists in `localStorage`

- **Technical Workflow:**
  - File: `resources/css/app.css` (add `[data-theme="high-contrast"]` block)
  - File: `resources/js/Components/NxThemeSwitcher.vue` (add option)
  - Override all glass backgrounds to solid colors

- **Backend Readiness:** N/A
- **Required Libraries:** None
- **Class/Component Names:** `NxThemeSwitcher.vue`, `app.css`
- **Functions to Modify/Create:**
  - `setTheme('high-contrast')` — apply theme

---

### Feature 5: Reduced Motion Preference (F-ACC-05)

- **Feature Name & ID:** Reduced Motion — F-ACC-05
- **Specs & Requirements:**
  - All CSS animations and transitions wrapped in `@media (prefers-reduced-motion: no-preference)`
  - When user has reduced motion enabled, all animations are instant (`duration → 0.01ms`)

- **UI/UX Specs:**
  - `@media (prefers-reduced-motion: reduce) { *, *::before, *::after { animation-duration: 0.01ms !important; animation-iteration-count: 1 !important; transition-duration: 0.01ms !important; } }`

- **Logic Workflow:** N/A — CSS media query
- **Technical Workflow:**
  - File: `resources/css/app.css` (add at end of file)
  - Wrap all existing `@keyframes` and transitions in media query

- **Backend Readiness:** N/A
- **Required Libraries:** None
- **Class/Component Names:** `app.css`
- **Functions to Modify/Create:** None

---

### Feature 6: NxOfflineBanner.vue — Offline Indicator (F-ACC-06)

- **Feature Name & ID:** NxOfflineBanner — Offline Indicator — F-ACC-06
- **Specs & Requirements:**
  - When browser goes offline (`navigator.onLine` / `offline` event), persistent amber banner slides down
  - Message: "You're offline. Changes will sync when reconnected."
  - Queued mutations stored in `localStorage` and replayed on reconnection
  - Animation: slides in from `translateY(-100%)` in `250ms`; slides out on reconnection

- **UI/UX Specs:**
  - Banner: `position: fixed; top: 0; left: 0; right: 0; height: 36px; background: rgba(245, 158, 11, 0.9); color: #000; display: flex; align-items: center; justify-content: center; font-size: 14px; z-index: 1000`
  - Below `NxTopBar` (3px) and above `NxStatusBar` (40px)

- **Logic Workflow:**
  - `window.addEventListener('offline', showBanner)`
  - `window.addEventListener('online', hideBanner + replayQueue)`
  - Queue mutations: intercept API calls when offline, store in `localStorage`, replay on reconnect

- **Technical Workflow:**
  - File: `resources/js/Components/NxOfflineBanner.vue` (new)
  - Props: None
  - Emits: None
  - State: `online: Boolean` (from `navigator.onLine`)
  - Storage: `localStorage.getItem('offline-queue')` → array of pending mutations

- **Backend Readiness:** N/A
- **Required Libraries:** None
- **Class/Component Names:** `NxOfflineBanner.vue`
- **Functions to Modify/Create:**
  - `setupOfflineListeners()` — add event listeners
  - `queueMutation(mutation)` — store in localStorage
  - `replayQueue()` — replay on reconnect

---

### Feature 7: NxCelebration.vue — Success Celebration (F-ACC-07)

- **Feature Name & ID:** NxCelebration — Success Celebration — F-ACC-07
- **Specs & Requirements:**
  - Triggered on milestone completions (workflow finished, first memory consolidated, agent task succeeded)
  - Brief particle burst (canvas-based confetti) for `1.5s` then disappears
  - Props: `trigger: Boolean`, `intensity: 'light' | 'full'`
  - Mobile haptic: `navigator.vibrate([15, 50, 15])`

- **UI/UX Specs:**
  - Canvas: `position: fixed; inset: 0; pointer-events: none; z-index: 9999`
  - Particles: 50 (light) or 150 (full) colored squares/circles
  - Animation: particles fall from top with gravity and rotation

- **Logic Workflow:**
  - Watch `trigger` prop — when `true`, start particle animation
  - After `1500ms`, stop animation, clear canvas
  - Haptic: `navigator.vibrate([15, 50, 15])` on trigger

- **Technical Workflow:**
  - File: `resources/js/Components/NxCelebration.vue` (new)
  - Props: `trigger: Boolean`, `intensity: String`
  - Emits: None
  - Canvas animation: `requestAnimationFrame` loop

- **Backend Readiness:** N/A
- **Required Libraries:** None
- **Class/Component Names:** `NxCelebration.vue`
- **Functions to Modify/Create:**
  - `startCelebration()` — init particles
  - `animateParticles()` — RAF loop
  - `stopCelebration()` — clear canvas

---

## 3. Testing Strategy

### Automated Testing

- **Unit Tests (Vitest):**
  - `NxLiveRegion.spec.ts`: Test message announcement; test politeness attribute
  - `NxOfflineBanner.spec.ts`: Test offline/online event handling; test queue storage
  - `NxCelebration.spec.ts`: Test trigger starts animation; test intensity changes particle count

- **Accessibility Tests (axe-core):**
  - Run axe on all pages → verify no violations
  - Test skip-to-content link works with `Tab` key
  - Test all interactive elements have `:focus-visible` ring
  - Test all images have `alt` text
  - Test all form inputs have labels

### Manual Testing Steps

1. **Keyboard Navigation:**
   - Press `Tab` from page top → verify skip-to-content link appears
   - Press `Enter` on skip link → verify focus moves to main content
   - Tab through all interactive elements → verify Nexus Blue focus ring visible

2. **Screen Reader (NVDA/JAWS):**
   - Navigate with screen reader → verify all content announced
   - Trigger notification → verify `NxLiveRegion` announces message
   - Open modal → verify focus trapped inside

3. **High Contrast:**
   - Enable High Contrast theme → verify all text is readable
   - Verify no transparency/blur in high-contrast mode
   - Verify contrast ratio `≥ 7:1` using DevTools contrast checker

4. **Reduced Motion:**
   - Enable "Reduce Motion" in OS settings → verify all animations are instant
   - Verify page transitions don't animate
   - Verify hover effects still work (no motion)

5. **Offline:**
   - Disconnect network → verify `NxOfflineBanner` appears
   - Perform action while offline → verify mutation queued
   - Reconnect → verify banner disappears, queued mutations replay

6. **Celebration:**
   - Complete workflow → verify celebration animation plays
   - Verify haptic feedback on mobile
   - Verify animation lasts exactly 1.5s then disappears
