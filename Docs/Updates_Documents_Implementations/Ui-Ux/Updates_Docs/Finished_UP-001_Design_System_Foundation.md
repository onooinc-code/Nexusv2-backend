# 🚀 UPDATE BLUEPRINT: UP-001 — Design System Foundation & Package Installation

## 1. Meta & Pre-flight Analysis

- **Features & Details:**
  - Fix all 13 CSS/token violations from Section 1 of `uiuv_v2.md`
  - Install missing npm dependencies (Pinia, Echo, Lucide, ECharts, markdown-it, highlight.js)
  - Initialize Pinia store in `app.js`
  - Initialize Laravel Echo + Reverb in `bootstrap.js`
  - Register Lucide icons globally
  - Create 10 Status Bar components (A01–A10) per `NEW_FEATURES_SPEC.md`

- **Project Context & Versions:**
  - Laravel 11 backend with Reverb WebSocket server
  - Vue 3 (Composition API, `<script setup>`)
  - Tailwind CSS v3+
  - Vite build tool
  - Target: 100% design token compliance per `Final Master Specification Report.md` Section 1

- **Regression Check:**
  - Color token changes affect every component that uses `--color-primary`, `--color-success`, `--color-error`, `--color-border-focus`
  - Font change from Figtree → Inter requires Google Fonts import in `app.css`
  - JetBrains Mono must be added to `tailwind.config.js` fontFamily and imported in `app.css`
  - Glass background change from `rgba(255,255,255,0.05)` → `rgba(22,27,34,0.7)` affects all `.glass` surfaces
  - Pinia/Echo initialization is additive — no existing functionality broken
  - Status Bar mount in `App.vue` adds a new DOM element above workspace content

---

## 2. Feature Specifications (Per Feature)

### Feature 1: Color Token Remapping (F-DS-01)

- **Feature Name & ID:** Color Token Remapping — F-DS-01
- **Specs & Requirements:**
  - `--color-primary`: `#4ade80` → `#007AFF` (Nexus Blue)
  - `--color-primary-hover`: `#22c55e` → `#007AFF` (same, use opacity variant for hover)
  - `--color-primary-muted`: `rgba(74, 222, 128, 0.1)` → `rgba(0, 122, 255, 0.1)`
  - `--color-primary-border`: `rgba(74, 222, 128, 0.3)` → `rgba(0, 122, 255, 0.3)`
  - `--color-success`: `#4ade80` → `#10B981` (Emerald)
  - `--color-error`: `#f87171` → `#EF4444` (Crimson)
  - `--color-border-focus`: `#4ade80` → `#007AFF` (Nexus Blue)
  - Add `--color-ai-core`: `#6366F1` (Hédra Purple) — currently missing entirely
  - Add `--color-warning`: `#F59E0B` (Amber) — already exists as `--color-accent-amber` but needs semantic alias

- **UI/UX Specs:**
  - All color tokens defined in `:root` block of `resources/css/app.css` lines 9–66
  - Light theme overrides in `[data-theme="light"]` block lines 69–87 must also be updated
  - `--shadow-glow` must change from green `rgba(74, 222, 128, 0.15)` → blue `rgba(0, 122, 255, 0.15)`

- **Logic Workflow:** N/A — static CSS variable replacement
- **Technical Workflow:** Edit `app.css` → save → Vite HMR hot-reloads all components
- **Backend Readiness:** N/A
- **Required Libraries:** N/A
- **Class/Component Names:** N/A
- **Functions to Modify/Create:** None

---

### Feature 2: Tailwind Config — Fonts & Color Extensions (F-DS-02)

- **Feature Name & ID:** Tailwind Config Extensions — F-DS-02
- **Specs & Requirements:**
  - Font family: `Figtree` → `Inter` (sans), add `JetBrains Mono` (mono)
  - Add Tailwind theme extensions for all spec colors: `surface-high`, `surface-mid`, `action-primary`, `ai-core`, `status-success`, `status-warning`, `status-error`
  - Add `tracking-tight` utility for H1/H2: `-0.02em`
  - Add `font-variant-numeric: tabular-nums` to mono font family

- **UI/UX Specs:**
  - `tailwind.config.js` lines 12–19: extend `fontFamily` and add `colors` block
  - Inter font loaded via Google Fonts `@import` in `app.css`
  - JetBrains Mono loaded via Google Fonts `@import` in `app.css`

- **Logic Workflow:** N/A — config-only change
- **Technical Workflow:** Edit `tailwind.config.js` → Vite rebuild → all components recompile with new theme
- **Backend Readiness:** N/A
- **Required Libraries:** N/A (fonts loaded from Google Fonts CDN)
- **Class/Component Names:** N/A
- **Functions to Modify/Create:** None

---

### Feature 3: Glass Background Fix (F-DS-03)

- **Feature Name & ID:** Glass Background Correction — F-DS-03
- **Specs & Requirements:**
  - `.glass` background: `rgba(255,255,255,0.05)` → `rgba(22,27,34,0.7)` (per spec `Surface-Mid`)
  - `.glass-strong` background: `rgba(255,255,255,0.1)` → `rgba(22,27,34,0.85)`
  - `.glass-subtle` background: `rgba(255,255,255,0.03)` → `rgba(22,27,34,0.5)`
  - Light theme `.glass` background: `rgba(255,255,255,0.7)` → `rgba(245,245,245,0.85)`

- **UI/UX Specs:**
  - `resources/css/app.css` lines 93–115: update all three glass utility classes
  - Per spec: blur `12px`, border `1px solid rgba(255,255,255,0.1)`, radius `8px`

- **Logic Workflow:** N/A — static CSS replacement
- **Technical Workflow:** Edit `app.css` → Vite HMR
- **Backend Readiness:** N/A
- **Required Libraries:** N/A
- **Class/Component Names:** `.glass`, `.glass-strong`, `.glass-subtle`
- **Functions to Modify/Create:** None

---

### Feature 4: Global Typography Fixes (F-DS-04)

- **Feature Name & ID:** Global Typography — F-DS-04
- **Specs & Requirements:**
  - Add `@import` for Inter and JetBrains Mono from Google Fonts in `app.css`
  - Add global `body` styles: `line-height: 1.6`, `font-family: Inter`
  - Add H1/H2 tracking: `letter-spacing: -0.02em`
  - Add `font-variant-numeric: tabular-nums` to `.font-mono` class

- **UI/UX Specs:**
  - `app.css` after `@tailwind` directives, before `:root` block
  - H1/H2 targeting: `h1, h2 { letter-spacing: -0.02em; }`
  - Body: `body { line-height: 1.6; font-family: 'Inter', sans-serif; }`
  - Mono: `.font-mono { font-variant-numeric: tabular-nums; }`

- **Logic Workflow:** N/A
- **Technical Workflow:** Edit `app.css`
- **Backend Readiness:** N/A
- **Required Libraries:** N/A
- **Class/Component Names:** `.font-mono`
- **Functions to Modify/Create:** None

---

### Feature 5: Package Installation (F-DS-05)

- **Feature Name & ID:** NPM Dependencies — F-DS-05
- **Specs & Requirements:**
  - Install: `pinia`, `laravel-echo`, `pusher-js`, `lucide-vue-next`, `vue-echarts`, `echarts`, `markdown-it`, `highlight.js`
  - Verify `package.json` versions are compatible with Vue 3

- **UI/UX Specs:** N/A
- **Logic Workflow:** N/A
- **Technical Workflow:** `npm install pinia laravel-echo pusher-js lucide-vue-next vue-echarts echarts markdown-it highlight.js`
- **Backend Readiness:** Laravel Reverb must be running on `ws://localhost:8080` (or configured env)
- **Required Libraries:** All listed above
- **Class/Component Names:** N/A
- **Functions to Modify/Create:** None

---

### Feature 6: Pinia Initialization (F-DS-06)

- **Feature Name & ID:** Pinia Store Setup — F-DS-06
- **Specs & Requirements:**
  - Import `createPinia` from `pinia`
  - Call `app.use(createPinia())` before mounting
  - File: `resources/js/app.js`

- **UI/UX Specs:** N/A
- **Logic Workflow:** Pinia must be initialized before any component accesses a store
- **Technical Workflow:** Edit `app.js` lines 1–5
- **Backend Readiness:** N/A
- **Required Libraries:** `pinia`
- **Class/Component Names:** `createPinia()`
- **Functions to Modify/Create:** None

---

### Feature 7: Laravel Echo + Reverb Initialization (F-DS-07)

- **Feature Name & ID:** Echo / Reverb WebSocket Init — F-DS-07
- **Specs & Requirements:**
  - Import `Echo` from `laravel-echo`
  - Import ` Pusher` from `pusher-js`
  - Configure `window.Echo = new Echo({ broadcaster: 'reverb', key: env('REVERB_APP_KEY'), wsHost: env('REVERB_HOST'), wsPort: env('REVERB_PORT'), forceTLS: env('REVERB_SCHEME') === 'https', enabled: env('APP_ENV') !== 'local' || true })`
  - File: `resources/js/bootstrap.js`

- **UI/UX Specs:** N/A
- **Logic Workflow:** Echo must be available globally as `window.Echo` before any component subscribes to channels
- **Technical Workflow:** Edit `bootstrap.js` lines 1–4
- **Backend Readiness:** Laravel Reverb server must be running; `.env` must have `REVERB_APP_KEY`, `REVERB_HOST`, `REVERB_PORT`, `REVERB_SCHEME`
- **Required Libraries:** `laravel-echo`, `pusher-js`
- **Class/Component Names:** `window.Echo`, `Echo` class
- **Functions to Modify/Create:** None

---

### Feature 8: Lucide Icon Registration (F-DS-08)

- **Feature Name & ID:** Lucide Vue Next Registration — F-DS-08
- **Specs & Requirements:**
  - Import `LucideVueNext` from `lucide-vue-next`
  - Register as Vue plugin with `app.use(LucideVueNext, { strokeWidth: 2 })`
  - File: `resources/js/app.js`

- **UI/UX Specs:** N/A
- **Logic Workflow:** All `<Icon>` components must be available globally after registration
- **Technical Workflow:** Edit `app.js`
- **Backend Readiness:** N/A
- **Required Libraries:** `lucide-vue-next`
- **Class/Component Names:** `LucideVueNext`
- **Functions to Modify/Create:** None

---

### Feature 9: NxStatusBar Component (A01) (F-A01)

- **Feature Name & ID:** NxStatusBar — Global System HUD — A01
- **Specs & Requirements:**
  - `40px` tall frosted-glass horizontal bar anchored below workspace header
  - Three zones: left (A02 ConnectionDot + A09 ProviderDots), center (A04 JobRail), right (A07 TokenBudget + A03 QueuePill + A10 NotificationBell)
  - `display: flex; justify-content: space-between`
  - Animation: slides down from `translateY(-100%)` to `translateY(0)` in `200ms ease-out` on mount
  - Mobile: collapses to icon-only strip at `< 768px`; tap opens slide-up panel

- **UI/UX Specs:**
  - Glassmorphism: `background: rgba(22,27,34,0.7); backdrop-filter: blur(12px); border-bottom: 1px solid rgba(255,255,255,0.1)`
  - Height: `40px` desktop, `32px` mobile
  - Z-index: `40` (above workspace, below modals)

- **Logic Workflow:**
  - Reads state from `useSystem()` Pinia store: `connectionState`, `activeJobCount`, `queueDepth`, `unreadCount`
  - Subscribes to Echo events: `RateLimitHit`, `JobProgressUpdated`, `AgentExecuted`, `TokenStreamed`, `notification.*`

- **Technical Workflow:**
  - Props: None (reads from Pinia)
  - Emits: None
  - Template: `<header class="nx-status-bar">` with three `<div class="zone">` children
  - Slots: `#left`, `#center`, `#right` for sub-component injection

- **Backend Readiness:**
  - Echo events must exist: `RateLimitHit`, `JobProgressUpdated`, `AgentExecuted`, `TokenStreamed`
  - API: `GET /api/v1/tasks/stats` for queue depth; `GET /api/v1/stats/tokens/today` for token budget

- **Required Libraries:** `pinia`, `laravel-echo`
- **Class/Component Names:** `NxStatusBar.vue`
- **Functions to Modify/Create:**
  - `useSystem()` — add `connectionState`, `jobProgress`, `queueDepth`, `unreadCount` state
  - `setConnectionState(state)` action
  - `updateJobProgress(progress)` action
  - `updateQueueDepth(depth)` action

---

### Feature 10: NxConnectionDot Component (A02) (F-A02)

- **Feature Name & ID:** NxConnectionDot — WebSocket Live Indicator — A02
- **Specs & Requirements:**
  - `10px` circle in status bar left zone
  - States: `connecting` (amber, slow pulse 1.5s), `connected` (emerald `#10B981`, breathing scale 1.0→1.15 at 3s), `disconnected` (crimson `#EF4444`, static), `error` (crimson rapid jitter 100ms)
  - Tooltip on hover: "Connected to Reverb" or "Reconnecting… (attempt X/5)"
  - Mobile: always visible, same dot in mobile status header

- **UI/UX Specs:**
  - Size: `10px` with `border-radius: 50%`
  - Transitions: `all 0.3s ease`

- **Logic Workflow:**
  - Listens to `window.Echo.connector.pusher.connection.bind('connected')` and `'disconnected'`
  - Reads `useSystem().connectionState`

- **Technical Workflow:**
  - Props: `state: 'connecting' | 'connected' | 'disconnected' | 'error'`
  - Emits: None
  - Computed: `dotClass` based on state

- **Backend Readiness:** Echo connection events must fire from Reverb
- **Required Libraries:** `pinia`, `laravel-echo`
- **Class/Component Names:** `NxConnectionDot.vue`
- **Functions to Modify/Create:**
  - `useSystem()` — add `connectionState` ref, `setConnectionState()` action

---

### Feature 11: NxQueuePill Component (A03) (F-A03)

- **Feature Name & ID:** NxQueuePill — Queue Depth Counter — A03
- **Specs & Requirements:**
  - Clickable glass pill showing `queued + running` task count
  - Click opens `NxQueueModal.vue` (B03, Phase 2)
  - Polled from `GET /api/v1/tasks/stats` every `15s`
  - Animation: when `count` increases, pill scales to `1.1` with `200ms` bounce (`cubic-bezier(0.34, 1.56, 0.64, 1)`)
  - Color: `count === 0` → muted grey; `count > 0` → Nexus Blue; `hasFailures` → Crimson
  - Mobile: shows count badge only; tap opens bottom sheet

- **UI/UX Specs:**
  - Glass pill: `background: rgba(22,27,34,0.7); backdrop-filter: blur(12px); border: 1px solid rgba(255,255,255,0.1); border-radius: 9999px; padding: 2px 10px`
  - Font: JetBrains Mono, `font-variant-numeric: tabular-nums`

- **Logic Workflow:**
  - `useSystem().queueDepth` reactive
  - Polling via `setInterval` in `onMounted`; clear in `onUnmounted`

- **Technical Workflow:**
  - Props: `count: Number`, `hasFailures: Boolean`
  - Emits: `click`
  - API call: `axios.get('/api/v1/tasks/stats')` → `{ queued: N, running: N, failed: N }`

- **Backend Readiness:** `GET /api/v1/tasks/stats` endpoint must exist
- **Required Libraries:** `axios`, `pinia`
- **Class/Component Names:** `NxQueuePill.vue`
- **Functions to Modify/Create:**
  - `useSystem()` — add `queueDepth`, `hasQueueFailures` refs; `fetchQueueStats()` action

---

### Feature 12: NxJobRail Component (A04) (F-A04)

- **Feature Name & ID:** NxJobRail — Background Job Progress Rail — A04
- **Specs & Requirements:**
  - `2px` tall full-width progress bar in status bar
  - Activates when any background job is running; shows aggregate progress
  - Props: `progress: Number (0–100)`, `active: Boolean`
  - Animation: smooth `transition: width 300ms ease`; on deactivate, bar slides to `100%` then fades out over `400ms`
  - Color: Nexus Blue `#007AFF` with glowing right edge `box-shadow: 2px 0 8px #007AFF`
  - Echo: `JobProgressUpdated` event updates progress
  - Mobile: same behavior, rendered at very top of viewport

- **UI/UX Specs:**
  - `position: fixed; top: 0; left: 0; right: 0; height: 2px; z-index: 100`
  - `background: linear-gradient(90deg, #007AFF, #007AFF var(--progress), transparent var(--progress))`

- **Logic Workflow:**
  - `useSystem().jobProgress` reactive
  - Echo listener: `window.Echo.listen('.job.progress', (e) => updateJobProgress(e.progress))`

- **Technical Workflow:**
  - Props: `progress`, `active`
  - Emits: None
  - Computed: `style` binding for `--progress` CSS variable

- **Backend Readiness:** `JobProgressUpdated` Echo event must be broadcast from backend
- **Required Libraries:** `pinia`, `laravel-echo`
- **Class/Component Names:** `NxJobRail.vue`
- **Functions to Modify/Create:**
  - `useSystem()` — add `jobProgress` ref, `updateJobProgress()` action

---

### Feature 13: NxAgentBadge Component (A05) (F-A05)

- **Feature Name & ID:** NxAgentBadge — Active Agent Count — A05
- **Specs & Requirements:**
  - Shows count of agents in `running` or `thinking` state
  - Tiny `NxAiPulse` orb precedes the number
  - `NxAiPulse` uses `thinking` state when `count > 0`, `idle` when `count === 0`
  - Echo: `AgentExecuted` event increments/decrements count
  - Click: navigates to Agents Hub filtered to `status=running`

- **UI/UX Specs:**
  - Flex row: `[NxAiPulse 12px] [count text JetBrains Mono]`
  - Clickable with hover background

- **Logic Workflow:**
  - `useSystem().activeAgentCount` reactive
  - Echo: `window.Echo.listen('AgentExecuted', (e) => adjustAgentCount(e.status))`

- **Technical Workflow:**
  - Props: `count: Number`
  - Emits: `click`
  - Router: `router.push({ name: 'agents', query: { status: 'running' } })`

- **Backend Readiness:** `AgentExecuted` Echo event
- **Required Libraries:** `pinia`, `laravel-echo`, `vue-router`
- **Class/Component Names:** `NxAgentBadge.vue`, `NxAiPulse.vue`
- **Functions to Modify/Create:**
  - `useSystem()` — add `activeAgentCount` ref, `incrementAgentCount()`, `decrementAgentCount()` actions

---

### Feature 14: NxRateLimitBanner Component (A06) (F-A06)

- **Feature Name & ID:** NxRateLimitBanner — Rate Limit Warning — A06
- **Specs & Requirements:**
  - Dismissible amber banner below status bar when provider reports `429`
  - Shows: provider name, reset countdown timer, "Switch Provider" CTA
  - Props: `provider: String`, `resetAt: Date`, `visible: Boolean`
  - Animation: slides in from `translateY(-100%)` in `250ms`; shakes gently every `5s`
  - Dismiss: clicking × calls `useSystem().clearRateLimit()`
  - Echo: `RateLimitHit` event

- **UI/UX Specs:**
  - `background: rgba(245, 158, 11, 0.15); border-bottom: 1px solid rgba(245, 158, 11, 0.3); color: #F59E0B`
  - Height: `36px`; padding: `0 16px`
  - Shake animation: `@keyframes shake { 0%, 100% { transform: translateX(0); } 25% { transform: translateX(-2px); } 75% { transform: translateX(2px); } }` at `5s` interval

- **Logic Workflow:**
  - `useSystem().rateLimitInfo` reactive `{ provider, resetAt, visible }`
  - Countdown timer: `setInterval` updates every second until `resetAt`

- **Technical Workflow:**
  - Props: `provider`, `resetAt`, `visible`
  - Emits: `dismiss`, `switch-provider`
  - Computed: `countdown` formatted as `mm:ss`

- **Backend Readiness:** `RateLimitHit` Echo event; `GET /api/v1/ai/providers/health` for provider list
- **Required Libraries:** `pinia`, `laravel-echo`
- **Class/Component Names:** `NxRateLimitBanner.vue`
- **Functions to Modify/Create:**
  - `useSystem()` — add `rateLimitInfo` ref, `setRateLimit()`, `clearRateLimit()` actions

---

### Feature 15: NxTokenBudget Component (A07) (F-A07)

- **Feature Name & ID:** NxTokenBudget — Daily Token Usage Ring — A07
- **Specs & Requirements:**
  - Small SVG ring (24×24px) in status bar showing daily token budget fraction
  - Color: `< 70%` → Blue; `70–90%` → Amber; `> 90%` → Crimson with pulse
  - Props: `used: Number`, `budget: Number`
  - Animation: ring fill animates via `stroke-dashoffset` transition on value change
  - Click: opens `UsageAnalyticsModal.vue` (I01, Phase 3)
  - API: `GET /api/v1/stats/tokens/today`

- **UI/UX Specs:**
  - SVG `<circle>` with `r=10`, `cx=12`, `cy=12`, `stroke-width=2`
  - `stroke-dasharray: 62.83` (2πr); `stroke-dashoffset` computed from percentage
  - Background circle: `rgba(255,255,255,0.1)`; foreground: color per threshold

- **Logic Workflow:**
  - Polls `GET /api/v1/stats/tokens/today` every `60s`
  - Computed: `percentage = used / budget`, `color` from threshold

- **Technical Workflow:**
  - Props: `used`, `budget`
  - Emits: `click`
  - API: `axios.get('/api/v1/stats/tokens/today')` → `{ used, budget }`

- **Backend Readiness:** `GET /api/v1/stats/tokens/today` endpoint
- **Required Libraries:** `axios`
- **Class/Component Names:** `NxTokenBudget.vue`
- **Functions to Modify/Create:** None (self-contained component)

---

### Feature 16: NxMemoryPressure Component (A08) (F-A08)

- **Feature Name & ID:** NxMemoryPressure — Redis Memory Pill — A08
- **Specs & Requirements:**
  - Shows Redis memory usage percentage as small pill
  - Only visible when usage exceeds `60%`
  - Props: `percent: Number`
  - Color: `60–80%` → Amber; `> 80%` → Crimson
  - Animation: pulses softly when in Crimson state
  - API: `GET /api/v1/health` (reads `redis.memory_percent`)

- **UI/UX Specs:**
  - Glass pill: `background: rgba(22,27,34,0.7); backdrop-filter: blur(12px); border: 1px solid rgba(255,255,255,0.1); border-radius: 9999px; padding: 2px 8px; font-size: 11px; font-family: 'JetBrains Mono'`
  - Pulse animation: `@keyframes pulse-red { 0%, 100% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); } 50% { box-shadow: 0 0 8px 2px rgba(239, 68, 68, 0.4); } }`

- **Logic Workflow:**
  - Polls `GET /api/v1/health` every `30s`
  - `v-if="percent > 60"` controls visibility

- **Technical Workflow:**
  - Props: `percent: Number`
  - Emits: None
  - API: `axios.get('/api/v1/health')` → `{ redis: { memory_percent: N } }`

- **Backend Readiness:** `GET /api/v1/health` endpoint with `redis.memory_percent`
- **Required Libraries:** `axios`
- **Class/Component Names:** `NxMemoryPressure.vue`
- **Functions to Modify/Create:** None

---

### Feature 17: NxProviderDots Component (A09) (F-A09)

- **Feature Name & ID:** NxProviderDots — Provider Health Indicators — A09
- **Specs & Requirements:**
  - Row of colored dots (one per configured AI provider) in status bar left zone
  - Color: `online` → emerald; `degraded` → amber; `offline` → crimson
  - Tooltip: "OpenAI · 340ms avg latency · ✓ Online"
  - API: `GET /api/v1/ai/providers/health`; polled every `60s`
  - Animation: dot going offline animates from emerald to crimson with `200ms` color transition

- **UI/UX Specs:**
  - Flex row of `8px` dots with `4px` gap
  - Each dot: `width: 8px; height: 8px; border-radius: 50%; transition: background-color 200ms ease`

- **Logic Workflow:**
  - Polls `GET /api/v1/ai/providers/health` every `60s`
  - Computed: `providers` array with `{ name, latency, status }`

- **Technical Workflow:**
  - Props: `providers: Array<{ name, latency, status }>`
  - Emits: `click` (navigates to provider detail)
  - API: `axios.get('/api/v1/ai/providers/health')`

- **Backend Readiness:** `GET /api/v1/ai/providers/health` endpoint
- **Required Libraries:** `axios`
- **Class/Component Names:** `NxProviderDots.vue`
- **Functions to Modify/Create:** None

---

### Feature 18: NxNotificationBell Component (A10) (F-A10)

- **Feature Name & ID:** NxNotificationBell — Global Notification Bell — A10
- **Specs & Requirements:**
  - Bell icon (Lucide `Bell`) in status bar right zone
  - Shows `unreadCount` badge from `useNotificationStore`
  - Clicking opens `NxNotificationDrawer.vue` slide-in panel from right
  - Animation: new notification → bell shakes `rotate(-15deg)` to `rotate(15deg)` over `400ms`; badge pops in with spring scale
  - Echo: `JobFailedEvent`, `WorkflowCompleted`, `ContactCreated` push to notification store

- **UI/UX Specs:**
  - Bell icon: `width: 20px; height: 20px; color: var(--color-text-secondary)`
  - Badge: `position: absolute; top: -4px; right: -4px; background: #EF4444; color: white; border-radius: 9999px; min-width: 16px; height: 16px; font-size: 10px`
  - Shake animation: `@keyframes bell-shake { 0%, 100% { transform: rotate(0); } 25% { transform: rotate(-15deg); } 75% { transform: rotate(15deg); } }`

- **Logic Workflow:**
  - `useNotificationStore().unreadCount` reactive
  - Watch `unreadCount` — if increases, trigger shake animation on bell element via `ref`
  - Click: `emit('open-drawer')` → parent opens `NxNotificationDrawer.vue`

- **Technical Workflow:**
  - Props: None (reads from store)
  - Emits: `open-drawer`
  - Store: `useNotificationStore()` — `unreadCount`, `notifications` array, `addNotification()` action

- **Backend Readiness:** Echo events: `JobFailedEvent`, `WorkflowCompleted`, `ContactCreated`
- **Required Libraries:** `pinia`, `laravel-echo`, `lucide-vue-next`
- **Class/Component Names:** `NxNotificationBell.vue`, `useNotificationStore.js`
- **Functions to Modify/Create:**
  - `useNotificationStore()` — `unreadCount` ref, `notifications` array, `addNotification(payload)` action, `incrementUnread()`, `markAllRead()`

---

## 3. Testing Strategy

### Automated Testing

- **Unit Tests (Vitest):**
  - `tailwind.config.js`: Assert `colors.action-primary === '#007AFF'`, `colors['status-success'] === '#10B981'`
  - `app.css`: Assert CSS custom properties match spec hex values (parse CSS, check `--color-primary`)
  - `NxStatusBar.spec.ts`: Snapshot test of rendered markup; test zone layout (left/center/right)
  - `NxConnectionDot.spec.ts`: Test all 4 state classes render correct colors; test tooltip text
  - `NxTokenBudget.spec.ts`: Test SVG `stroke-dashoffset` calculation for 0%, 50%, 95%, 100%
  - `NxNotificationBell.spec.ts`: Test badge count display; test shake animation trigger on count increment
  - `useSystem.spec.ts`: Test state mutations (`setConnectionState`, `updateJobProgress`, `updateQueueDepth`)
  - `useNotificationStore.spec.ts`: Test `addNotification()` increments `unreadCount`; test `markAllRead()` resets to 0

- **Visual Regression (Percy/Chromatic):**
  - Capture `App.vue` with `NxStatusBar` mounted — compare against baseline screenshot
  - Capture each status bar sub-component in all states (connected/disconnected/error)

### Manual Testing Steps

1. **Design Token Verification (Desktop Chrome):**
   - Open DevTools → Computed Styles on any button → verify `--color-primary` resolves to `rgb(0, 122, 255)`
   - Check `--color-success` → `rgb(16, 185, 129)`, `--color-error` → `rgb(239, 68, 68)`
   - Verify `--color-bg-glass` → `rgba(22, 27, 34, 0.7)`
   - Check body font-family → `Inter` (not Figtree)

2. **Font Verification:**
   - Navigate to any page with code blocks → verify JetBrains Mono renders
   - Check H1/H2 elements → verify `letter-spacing: -0.02em` in Computed Styles
   - Check `body` → verify `line-height: 1.6`

3. **Pinia Store Test:**
   - Open Vue DevTools → Pinia tab → verify `useChat`, `useContacts`, `useWorkflows`, `useSystem`, `useNotificationStore` all listed
   - Call `useSystem().setConnectionState('connected')` → verify `NxConnectionDot` turns emerald

4. **Echo Connection Test:**
   - Open Console → `window.Echo` → verify object exists with `connector` property
   - Check Network tab → verify WebSocket connection to Reverb server (`ws://localhost:8080` or configured host)
   - Trigger a backend event (e.g., run a workflow) → verify `NxJobRail` progress bar animates

5. **Status Bar Test:**
   - Verify `NxStatusBar` renders below workspace header, `40px` tall
   - Verify all 10 sub-components visible (A02–A10)
   - Hover `NxConnectionDot` → verify tooltip shows connection state
   - Click `NxQueuePill` → verify `NxQueueModal` opens (B03, Phase 2 — stub OK for now)
   - Click `NxNotificationBell` → verify `NxNotificationDrawer` opens (stub OK)
   - Trigger a `JobProgressUpdated` event → verify `NxJobRail` fills

6. **Mobile Test (Chrome DevTools → 375px width):**
   - Verify status bar collapses to icon-only strip
   - Verify `NxNavRail` is hidden (H01, Phase 2)
   - Verify `NxConnectionDot` remains visible

7. **RTL Test:**
   - Set `document.documentElement.dir = 'rtl'` in Console
   - Verify status bar layout mirrors correctly (left/right zones swap)

8. **Accessibility Test:**
   - Tab through status bar → verify `NxConnectionDot`, `NxQueuePill`, `NxNotificationBell` all receive focus
   - Verify `:focus-visible` outline is Nexus Blue `#007AFF` with `2px` offset

---

## 4. Implementation Checklist

### Phase 1 Tasks

- [x] **Task 01: Package Installation** — Completed 2026-05-19
  - Installed: pinia, laravel-echo, pusher-js, lucide-vue-next, vue-echarts, echarts, markdown-it, highlight.js
  - Dev server verified running on http://localhost:5173
  - Note: `lucide-vue-next@0.294.0` is deprecated — migrate to `@lucide/vue` in future update

- [x] **Task 02: Color Token Remapping** — Completed 2026-05-19
  - `--color-primary`: #4ade80 → #007AFF ✓
  - `--color-primary-hover`: #22c55e → #007AFF ✓
  - `--color-primary-muted`: rgba(74,222,128,0.1) → rgba(0,122,255,0.1) ✓
  - `--color-primary-border`: rgba(74,222,128,0.3) → rgba(0,122,255,0.3) ✓
  - `--color-success`: #4ade80 → #10B981 ✓
  - `--color-warning`: #fbbf24 → #F59E0B ✓
  - `--color-error`: #f87171 → #EF4444 ✓
  - `--color-ai-core`: #6366F1 (added) ✓
  - `--color-border-focus`: #4ade80 → #007AFF ✓
  - `--shadow-glow`: rgba(74,222,128,0.15) → rgba(0,122,255,0.15) ✓
  - Light theme `--color-bg-glass`: rgba(255,255,255,0.7) → rgba(245,245,245,0.85) ✓

- [x] **Task 03: Tailwind Config Extensions** — Completed 2026-05-19
  - Font: Figtree → Inter ✓
  - Added JetBrains Mono ✓
  - Added 7 color tokens (surface-high, surface-mid, action-primary, ai-core, status-success, status-warning, status-error) ✓
  - Added tracking-tight: -0.02em ✓
- [x] **Task 04: Glass Background Fix** — Completed 2026-05-19
  - `.glass`: rgba(255,255,255,0.05) → rgba(22,27,34,0.7) ✓
  - `.glass-strong`: rgba(255,255,255,0.1) → rgba(22,27,34,0.85) ✓
  - `.glass-subtle`: rgba(255,255,255,0.03) → rgba(22,27,34,0.5) ✓
  - Light theme `--color-bg-glass`: rgba(255,255,255,0.7) → rgba(245,245,245,0.85) ✓
- [x] **Task 05: Global Typography** — Completed 2026-05-19
  - Added Inter + JetBrains Mono Google Fonts import ✓
  - Body line-height: 1.6 ✓
  - H1/H2 letter-spacing: -0.02em ✓
  - .font-mono font-variant-numeric: tabular-nums ✓
- [x] **Task 06: Pinia Initialization** — Completed 2026-05-19
  - Imported createPinia from 'pinia' ✓
  - Called app.use(createPinia()) before mount ✓
- [x] **Task 07: Echo + Reverb Initialization** — Completed 2026-05-19
  - Imported Echo from 'laravel-echo' ✓
  - Imported Pusher from 'pusher-js' ✓
  - Initialized window.Echo with Reverb config ✓
  - Environment variables used for config ✓
- [x] **Task 08: Lucide Icon Registration** — Completed 2026-05-19
  - Imported LucideVueNext from 'lucide-vue-next' ✓
  - Registered with strokeWidth: 2 ✓
- [x] **Task 09: NxStatusBar Component** — Completed 2026-05-19
  - Created NxStatusBar.vue with 3 zones (left/center/right) ✓
  - Reads state from useSystem() store ✓
  - Mounted in App.vue ✓
  - Slide-down animation on mount ✓
- [x] **Task 10: NxConnectionDot** — Completed 2026-05-19
  - Created NxConnectionDot.vue with 4 states ✓
  - Each state has correct color and animation ✓
  - Tooltip shows connection state ✓
  - Used in NxStatusBar left zone ✓
- [x] **Task 11: NxQueuePill** — Completed 2026-05-19
  - Created NxQueuePill.vue with count and hasFailures props ✓
  - Color changes based on count/failures ✓
  - JetBrains Mono font applied ✓
  - Used in NxStatusBar right zone ✓
- [x] **Task 12: NxJobRail** — Completed 2026-05-19
  - Created NxJobRail.vue with progress and active props ✓
  - Width animates smoothly with transition ✓
  - Color is Nexus Blue #007AFF ✓
  - Glow effect on right edge ✓
  - Used in NxStatusBar center zone ✓
- [x] **Task 13: NxAgentBadge** — Completed 2026-05-19
  - Created NxAgentBadge.vue with count prop ✓
  - NxAiPulse embedded with correct state ✓
  - Pulse state is 'thinking' when count > 0, 'idle' when 0 ✓
  - JetBrains Mono font applied ✓
  - Used in NxStatusBar left zone ✓
- [x] **Task 14: NxAiPulse** — Completed 2026-05-19
  - Created NxAiPulse.vue with 4 states (idle, thinking, speaking, error) ✓
  - All states have correct colors and animations ✓
  - thinking shows conic-gradient rotation ✓
  - error shows jitter animation ✓
  - size prop changes dimensions ✓
- [x] **Task 15: NxRateLimitBanner** — Completed 2026-05-19
  - Created NxRateLimitBanner.vue with provider, resetAt, visible props ✓
  - Countdown timer updates every second ✓
  - Dismiss and Switch Provider buttons work ✓
  - Slides in with animation ✓
  - Shakes gently every 5s ✓
- [x] **Task 16: NxTokenBudget** — Completed 2026-05-19
  - Created NxTokenBudget.vue with used/budget props ✓
  - SVG ring renders correctly ✓
  - Color changes at 70% and 90% thresholds ✓
  - Percentage text centered ✓
  - Used in NxStatusBar right zone ✓
- [x] **Task 17: NxMemoryPressure** — Completed 2026-05-19
  - Created NxMemoryPressure.vue with percent prop ✓
  - Only visible when percent > 60 ✓
  - Color changes at 80% threshold ✓
  - Critical state pulses with glow ✓
  - JetBrains Mono font applied ✓
- [x] **Task 18: NxProviderDots** — Completed 2026-05-19
  - Created NxProviderDots.vue with providers prop ✓
  - Renders one dot per provider ✓
  - Color matches status (online=emerald, degraded=amber, offline=crimson) ✓
  - Tooltip shows provider name, latency, status ✓
  - Used in NxStatusBar left zone ✓
- [x] **Task 19: useSystem Store (Minimal)** — Completed 2026-05-19
  - Created useSystem.js with all required state for status bar ✓
  - All actions defined ✓
  - Vue DevTools shows useSystem store ✓
- [x] **Task 20: NxNotificationBell** — Completed 2026-05-19
  - Created NxNotificationBell.vue with bell icon ✓
  - Badge shows unread count ✓
  - Badge shows '99+' when count > 99 ✓
  - Shake animation triggers on count increase ✓
  - Used in NxStatusBar right zone ✓
- [x] **Task 21: useNotificationStore (Minimal)** — Completed 2026-05-19
  - Created useNotificationStore.js with required state ✓
  - All actions defined ✓
  - Vue DevTools shows useNotificationStore ✓
- [x] **Task 22: Mount NxStatusBar in App.vue** — Completed 2026-05-19
  - NxStatusBar imported in App.vue ✓
  - Rendered below workspace header ✓
  - All sub-components (A02–A10) visible ✓
  - Status bar height is 40px ✓
  - Glassmorphism styling applied ✓

---

*Blueprint Version: 1.0 | Last Updated: 2026-05-19 | Status: IN PROGRESS*
