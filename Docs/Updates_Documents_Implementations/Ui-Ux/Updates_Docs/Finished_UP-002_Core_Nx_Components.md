# 🚀 UPDATE BLUEPRINT: UP-002 — Core Nx Components (Phase 2)

## 1. Meta & Pre-flight Analysis

- **Features & Details:**
  - Create 5 foundational Nx components: `NxAiPulse.vue`, `NxGlassCard.vue`, `NxTokenMeter.vue`, `NxLiveLoader.vue`, `NxActionButton.vue`
  - These components are used throughout the entire application — they are the atomic building blocks
  - All components must follow Glassmorphism 2.0 spec, RTL support, and 44×44px touch target rule

- **Project Context & Versions:**
  - Vue 3 Composition API (`<script setup>`)
  - Tailwind CSS v3+ with design tokens from UP-001
  - Pinia stores available (created in UP-001/Phase 3)
  - Lucide Vue Next registered globally

- **Regression Check:**
  - These are new components — no existing functionality broken
  - `NxActionButton.vue` will eventually replace `Button.vue` usage in new code; old `Button.vue` fixed in Phase 5
  - `NxGlassCard.vue` will replace `.glass` + `Card.vue` pattern in Phase 5
  - All components must work in both RTL and LTR modes

---

## 2. Feature Specifications (Per Feature)

### Feature 1: NxAiPulse.vue — The State Orb (F-UI-01)

- **Feature Name & ID:** NxAiPulse — AI State Orb — F-UI-01
- **Specs & Requirements:**
  - Visual heartbeat of the AI system
  - Props: `state: 'idle' | 'thinking' | 'speaking' | 'error'` (required)
  - Size: `24px` default, accepts `size` prop for scaling
  - Used in: `NxStatusBar` (A05 AgentBadge), `NxAiStatusRow` (D09), agent cards (E01)

- **UI/UX Specs:**
  - `idle`: Slow breathing scale `1.0 → 1.05`, opacity `0.4 → 0.7`, `4s` loop
  - `thinking`: Rapid conic-gradient rotation, `1s` linear loop, AI-Core purple `#6366F1`
  - `speaking`: Sound-reactive/randomized scale based on streaming token chunks
  - `error`: Crimson jitter/shake `translateX(-2px to 2px)` over `100ms`
  - Glass container: `background: rgba(22,27,34,0.7); backdrop-filter: blur(12px); border: 1px solid rgba(255,255,255,0.1); border-radius: 50%`

- **Logic Workflow:**
  - Pure visual component — no API calls
  - `speaking` state receives optional `amplitude` prop (0–1) to modulate scale

- **Technical Workflow:**
  - Props: `state: String`, `size: Number (default: 24)`, `amplitude: Number (default: 0)`
  - Emits: None
  - Computed: `pulseClass`, `animationStyle` based on `state`
  - Template: `<div class="nx-ai-pulse" :class="stateClass" :style="animationStyle" />`

- **Backend Readiness:** N/A
- **Required Libraries:** None (pure Vue + CSS)
- **Class/Component Names:** `NxAiPulse.vue`
- **Functions to Modify/Create:** None

---

### Feature 2: NxGlassCard.vue — Standard Container (F-UI-02)

- **Feature Name & ID:** NxGlassCard — Standard Glass Container — F-UI-02
- **Specs & Requirements:**
  - The standard container for profiles, memories, settings, and panels
  - Props: `elevation: 1 | 2 | 3` (maps to shadow spread), `hoverable: Boolean` (applies `translate-y-[-2px]` on hover)
  - Slots: `#header` (sticky top, `z-index: 10`), `#body` (scrollable, `flex: 1`), `#footer` (actions, sticky bottom)
  - Used in: All hub views, modals, panels

- **UI/UX Specs:**
  - Glassmorphism: `background: rgba(22,27,34,0.7); backdrop-filter: blur(12px); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px`
  - Elevation mapping:
    - `1`: `box-shadow: 0 4px 6px rgba(0,0,0,0.4)`
    - `2`: `box-shadow: 0 10px 15px rgba(0,0,0,0.5)`
    - `3`: `box-shadow: 0 20px 25px rgba(0,0,0,0.6)`
  - Hover: `transform: translateY(-2px); box-shadow: 0 12px 20px rgba(0,0,0,0.5)` (when `hoverable=true`)
  - RTL: `margin-inline-start` instead of `margin-left` for internal spacing

- **Logic Workflow:**
  - Pure presentational component
  - `hoverable` prop toggles hover transform via `group` class

- **Technical Workflow:**
  - Props: `elevation: { type: Number, default: 2 }`, `hoverable: { type: Boolean, default: false }`
  - Emits: None
  - Slots: `header`, `body`, `footer` (all optional)
  - Template: `<article class="nx-glass-card" :class="elevationClass, { hoverable }"> <slot name="header" /> <div class="card-body"><slot /></div> <slot name="footer" /> </article>`

- **Backend Readiness:** N/A
- **Required Libraries:** None
- **Class/Component Names:** `NxGlassCard.vue`
- **Functions to Modify/Create:** None

---

### Feature 3: NxTokenMeter.vue — Context Window Visualizer (F-UI-03)

- **Feature Name & ID:** NxTokenMeter — Context Window Progress — F-UI-03
- **Specs & Requirements:**
  - Real-time Context Window visualization mapping to `meta.tokens_used`
  - Props: `currentTokens: Number`, `maxTokens: Number (default: 6000)`
  - Visual: Horizontal SVG progress bar
  - Threshold colors: `< 70%` → Nexus Blue; `70–90%` → Amber; `> 90%` → Crimson
  - Used in: `NxContextBar` (D06), `NxStatusBar` (A07 TokenBudget)

- **UI/UX Specs:**
  - SVG `<rect>` with `height: 4px`, `rx: 2px` (rounded)
  - Background: `rgba(255,255,255,0.1)`; foreground: color per threshold
  - Width: `100%` of parent container
  - Transition: `width 300ms ease`

- **Logic Workflow:**
  - Computed: `percentage = currentTokens / maxTokens`
  - Computed: `color` from threshold logic
  - Computed: `barWidth = Math.min(percentage * 100, 100)%`

- **Technical Workflow:**
  - Props: `currentTokens: Number`, `maxTokens: { type: Number, default: 6000 }`
  - Emits: None
  - Computed: `percentage`, `thresholdColor`, `barWidth`
  - Template: `<svg class="nx-token-meter"><rect class="bg" /><rect class="fill" :style="{ width: barWidth, fill: thresholdColor }" /></svg>`

- **Backend Readiness:** N/A (receives props from parent)
- **Required Libraries:** None
- **Class/Component Names:** `NxTokenMeter.vue`
- **Functions to Modify/Create:** None

---

### Feature 4: NxLiveLoader.vue — Async Task Indicator (F-UI-04)

- **Feature Name & ID:** NxLiveLoader — Async Job Indicator — F-UI-04
- **Specs & Requirements:**
  - Replaces generic spinners for long-running background jobs
  - Props: `taskId: UUID`, `status: String`
  - Visual: Pulsing glass pill; when expanded, shows terminal-style log feed
  - Log feed: `task_checkpoints` streamed via Reverb
  - Used in: `TaskMonitor.vue`, `WorkflowBuilder.vue`, `NxTaskDetailDrawer.vue` (B04)

- **UI/UX Specs:**
  - Collapsed: `background: rgba(22,27,34,0.7); backdrop-filter: blur(12px); border: 1px solid rgba(255,255,255,0.1); border-radius: 9999px; padding: 4px 12px`
  - Pulsing: `@keyframes pulse { 0%, 100% { opacity: 0.6; } 50% { opacity: 1; } }` at `2s`
  - Expanded: `max-height: 200px; overflow-y: auto; font-family: 'JetBrains Mono'; font-size: 11px; background: rgba(0,0,0,0.3)`
  - Log lines: `color: rgba(255,255,255,0.7); padding: 2px 0; border-bottom: 1px solid rgba(255,255,255,0.05)`

- **Logic Workflow:**
  - Subscribes to Echo channel `private-tasks.{taskId}` for `TaskCheckpoint` events
  - Appends new log lines to local `logs` array
  - Auto-scrolls to bottom on new log (unless user scrolled up)

- **Technical Workflow:**
  - Props: `taskId: String`, `status: String`
  - Emits: `expand`, `collapse`
  - State: `logs: Array<{ timestamp, message }>`, `expanded: Boolean`
  - Echo: `window.Echo.private('tasks.' + taskId).listen('TaskCheckpoint', (e) => logs.push(e))`

- **Backend Readiness:** `TaskCheckpoint` Echo event on `private-tasks.{taskId}` channel
- **Required Libraries:** `laravel-echo`, `pinia`
- **Class/Component Names:** `NxLiveLoader.vue`
- **Functions to Modify/Create:** None

---

### Feature 5: NxActionButton.vue — Standardized Interaction Button (F-UI-05)

- **Feature Name & ID:** NxActionButton — Optimistic Action Button — F-UI-05
- **Specs & Requirements:**
  - Standardized interaction button replacing all ad-hoc button patterns
  - Props: `variant: 'primary' | 'secondary' | 'danger' | 'ghost'`, `loading: Boolean`, `disabled: Boolean`, `optimistic: Boolean`
  - Optimistic behavior: if `optimistic=true`, instantly enters pseudo-success state while awaiting backend ACK; rolls back to `error` if promise rejects
  - Used in: All mutation actions (send message, save workflow, add contact, etc.)

- **UI/UX Specs:**
  - `primary`: `background: #007AFF; color: white; border: none`
  - `secondary`: `background: transparent; color: #007AFF; border: 1px solid rgba(0,122,255,0.3)`
  - `danger`: `background: #EF4444; color: white; border: none`
  - `ghost`: `background: transparent; color: var(--color-text-secondary); border: none`
  - Hover: `opacity: 0.9`; Active: `scale(0.98)`
  - Loading: shows `NxLiveLoader` mini pill inside button
  - Optimistic success: briefly shows `background: #10B981` before settling
  - Touch target: `min-height: 44px; min-width: 44px` (mobile compliance)

- **Logic Workflow:**
  - `optimistic=true`: on click, emit `click` with `{ optimistic: true }`; parent handles promise
  - Parent passes back `optimistic-state: 'pending' | 'success' | 'error'` via `v-model:optimisticState`
  - Button shows visual feedback based on `optimisticState`

- **Technical Workflow:**
  - Props: `variant: { type: String, default: 'primary' }`, `loading: Boolean`, `disabled: Boolean`, `optimistic: Boolean`, `optimisticState: { type: String, default: null }` (for v-model)
  - Emits: `click`, `update:optimisticState`
  - Slots: `#default` (button text/icon), `#loading` (custom loading indicator)
  - Computed: `buttonClass` from variant + state

- **Backend Readiness:** N/A (parent handles API calls)
- **Required Libraries:** None
- **Class/Component Names:** `NxActionButton.vue`
- **Functions to Modify/Create:** None

---

## 3. Testing Strategy

### Automated Testing

- **Unit Tests (Vitest):**
  - `NxAiPulse.spec.ts`: Test all 4 state classes render; test `amplitude` prop affects scale; test `size` prop changes dimensions
  - `NxGlassCard.spec.ts`: Test slot rendering (header/body/footer); test `elevation` prop applies correct shadow; test `hoverable` adds hover class
  - `NxTokenMeter.spec.ts`: Test `stroke-dashoffset` calculation at 0%, 50%, 70%, 90%, 100%; test color threshold switching
  - `NxLiveLoader.spec.ts`: Test log appending; test expand/collapse; test auto-scroll behavior
  - `NxActionButton.spec.ts`: Test all 4 variants render correct classes; test `loading` shows spinner; test `optimistic` state transitions (pending → success → error)

- **Visual Regression:**
  - Snapshot each component in all variant/state combinations
  - Capture at mobile (375px) and desktop (1440px) breakpoints

### Manual Testing Steps

1. **NxAiPulse Test:**
   - Render all 4 states side-by-side; verify animations match spec
   - Check `thinking` state shows conic-gradient rotation
   - Check `error` state shows jitter animation

2. **NxGlassCard Test:**
   - Render with `elevation={1,2,3}`; verify shadow depth increases
   - Hover with `hoverable=true`; verify `translateY(-2px)` lift
   - Test all 3 slots render content in correct positions

3. **NxTokenMeter Test:**
   - Set `currentTokens=4200, maxTokens=6000` (70%) → verify Amber color
   - Set `currentTokens=5400, maxTokens=6000` (90%) → verify Crimson color
   - Animate from 0% to 100%; verify smooth `stroke-dashoffset` transition

4. **NxLiveLoader Test:**
   - Expand loader; verify terminal log feed appears
   - Simulate `TaskCheckpoint` Echo event; verify new log line appends
   - Scroll up in log; verify auto-scroll pauses

5. **NxActionButton Test:**
   - Click with `optimistic=true`; verify instant visual feedback
   - Simulate API rejection; verify rollback to error state
   - Test all 4 variants render correct colors
   - Verify touch target is `≥ 44×44px` on mobile

6. **RTL Test:**
   - Set `dir="rtl"` on `<html>`
   - Verify all components mirror correctly (margins, borders, text alignment)

7. **Accessibility Test:**
   - Tab through all interactive elements; verify `:focus-visible` ring is Nexus Blue `#007AFF`
   - Verify all buttons have `aria-label` or visible text

EOF