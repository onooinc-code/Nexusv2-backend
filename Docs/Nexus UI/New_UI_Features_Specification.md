# ✨ Nexus — New UI/UX Features Specification
**Document Type:** Feature Proposal & Design Specification  
**Version:** 1.0  
**Date:** 2026-05-19  
**Author:** Lead Frontend Architect  
**Total Features:** 100 across 12 categories  
**Stack:** Vue 3 (Composition API) · Pinia · Tailwind CSS · Laravel Reverb · ECharts · Lucide-Vue-Next

---

> **Priority Legend**
> - `P1` — Critical path, needed for core experience
> - `P2` — High value, significantly improves UX
> - `P3` — Medium value, polish and depth
> - `P4` — Nice-to-have, future enhancement

---

## Category A — 🖥️ Global Status Bar & System HUD
*A persistent, always-visible system health strip that replaces guesswork with instant awareness. Rendered inside `App.vue` above all hub content.*

---

### A01 — `NxStatusBar.vue` — Global System HUD Strip
- **Component:** `NxStatusBar.vue` (new, mounted in `App.vue`)
- **Hub:** Global (all hubs)
- **Priority:** P1
- **Description:** A `40px` tall frosted-glass horizontal bar anchored below the workspace header. Hosts slots for all sub-indicators (A02–A10). Uses CSS `display: flex; justify-content: space-between` with left, center, and right zones.
- **Props/State:** `useSystem()` Pinia store — `connectionState`, `activeJobCount`, `queueDepth`, `unreadCount`
- **Animation:** On mount, slides down from `translateY(-100%)` to `translateY(0)` in `200ms ease-out`
- **Layout:**
  ```
  ┌─[A02 dot][A09 dots]─────────[A04 job rail]─────────[A07 token][A03 queue][A10 bell]─┐
  └──────────────────────────────────────────────────────────────────────────────────────┘
  ```
- **Mobile:** Collapses to icon-only strip at `< 768px`; tapping opens a slide-up panel with full status detail

---

### A02 — `NxConnectionDot.vue` — WebSocket Live Indicator
- **Component:** `NxConnectionDot.vue` (new)
- **Hub:** Global
- **Priority:** P1
- **Description:** A `10px` circle in the status bar left zone. Reflects Laravel Reverb WebSocket connection state in real-time.
- **Props:** `state: 'connecting' | 'connected' | 'disconnected' | 'error'`
- **Animation:**
  - `connecting`: amber, slow pulse `opacity 1.5s ease-in-out infinite`
  - `connected`: emerald `#10B981`, slow breathing scale `1.0→1.15` at `3s`
  - `disconnected`: crimson `#EF4444`, static
  - `error`: crimson rapid jitter `translateX(-1px,1px)` at `100ms`
- **Echo:** Listens to `window.Echo.connector.pusher.connection.bind('connected')` and `'disconnected'`
- **Tooltip:** Hover shows "Connected to Reverb" or "Reconnecting… (attempt 3/5)"
- **Mobile:** Always visible, same dot in mobile status header

---

### A03 — `NxQueuePill.vue` — Queue Depth Counter
- **Component:** `NxQueuePill.vue` (new)
- **Hub:** Global
- **Priority:** P2
- **Description:** A clickable glass pill showing the current queue depth (`queued` + `running` tasks). Clicking opens `NxQueueModal.vue`.
- **Props:** `count: Number`, `hasFailures: Boolean`
- **State:** `useSystem().queueDepth`; polled from `GET /api/v1/tasks/stats` every `15s`
- **Animation:** When `count` increases, pill briefly scales to `1.1` with a `200ms` bounce (`cubic-bezier(0.34, 1.56, 0.64, 1)`)
- **Color Logic:** `count === 0` → muted grey; `count > 0` → Nexus Blue; `hasFailures` → Crimson
- **Click Action:** Opens `NxQueueModal.vue` (see B03)
- **Mobile:** Shows count badge only; tap opens bottom sheet

---

### A04 — `NxJobRail.vue` — Background Job Progress Rail
- **Component:** `NxJobRail.vue` (new)
- **Hub:** Global
- **Priority:** P1
- **Description:** A `2px` tall progress bar spanning the full width of the status bar, similar to NProgress. Activates whenever any background job is running. Shows aggregate progress of the active job batch.
- **Props:** `progress: Number (0–100)`, `active: Boolean`
- **State:** `useSystem().jobProgress`
- **Animation:** Smooth `transition: width 300ms ease`. When `active` goes from `true` to `false`, bar slides to `100%` then fades out over `400ms`
- **Color:** Nexus Blue `#007AFF` with a glowing right edge `box-shadow: 2px 0 8px #007AFF`
- **Echo:** Listens to `JobProgressUpdated` event to update progress value
- **Mobile:** Same behavior, rendered at the very top of the viewport

---

### A05 — `NxAgentBadge.vue` — Active Agent Count
- **Component:** `NxAgentBadge.vue` (new)
- **Hub:** Global
- **Priority:** P2
- **Description:** Shows the count of agents in `running` or `thinking` state. A tiny `NxAiPulse` orb precedes the number.
- **Props:** `count: Number`
- **State:** `useSystem().activeAgentCount`
- **Animation:** The embedded `NxAiPulse` uses `thinking` state whenever `count > 0`, `idle` when `count === 0`
- **Echo:** `AgentExecuted` event increments/decrements the count
- **Click:** Navigates to Agents Hub filtered to `status=running`

---

### A06 — `NxRateLimitBanner.vue` — Rate Limit Warning
- **Component:** `NxRateLimitBanner.vue` (new)
- **Hub:** Global
- **Priority:** P2
- **Description:** A dismissible amber banner that slides down below the status bar when a provider reports `429 Too Many Requests`. Displays the affected provider name, reset countdown timer, and a "Switch Provider" CTA.
- **Props:** `provider: String`, `resetAt: Date`, `visible: Boolean`
- **Animation:** Slides in from `translateY(-100%)` in `250ms`, shakes gently every `5s` to re-attract attention
- **State:** `useSystem().rateLimitInfo`
- **Echo:** Listens to `RateLimitHit` event (new event needed on backend)
- **Dismiss:** Clicking × sets `useSystem().clearRateLimit()`

---

### A07 — `NxTokenBudget.vue` — Daily Token Usage Ring
- **Component:** `NxTokenBudget.vue` (new)
- **Hub:** Global
- **Priority:** P2
- **Description:** A small SVG ring (24×24px) in the status bar showing the fraction of the daily token budget consumed. Color-coded by threshold identical to `NxTokenMeter`.
- **Props:** `used: Number`, `budget: Number`
- **Animation:** Ring fill animates smoothly when value changes using SVG `stroke-dashoffset` transition
- **Color:** `< 70%` → Blue; `70–90%` → Amber; `> 90%` → Crimson with pulse
- **Click:** Opens `UsageAnalyticsModal.vue` (see I01)
- **API:** `GET /api/v1/stats/tokens/today`

---

### A08 — `NxMemoryPressure.vue` — Redis Memory Pill
- **Component:** `NxMemoryPressure.vue` (new)
- **Hub:** Global
- **Priority:** P3
- **Description:** Shows Redis memory usage percentage as a small pill. Only visible when usage exceeds 60%.
- **Props:** `percent: Number`
- **Color:** `60–80%` → Amber; `> 80%` → Crimson
- **API:** `GET /api/v1/health` (reads `redis.memory_percent`)
- **Animation:** Pulses softly when in Crimson state

---

### A09 — `NxProviderDots.vue` — Provider Health Indicators
- **Component:** `NxProviderDots.vue` (new)
- **Hub:** Global
- **Priority:** P2
- **Description:** A row of colored dots (one per configured AI provider) in the status bar left zone. Each dot color reflects provider health.
- **Props:** `providers: Array<{ name, latency, status }>`
- **Color:** `online` → emerald; `degraded` → amber; `offline` → crimson
- **Tooltip:** Hover shows `"OpenAI · 340ms avg latency · ✓ Online"`
- **API:** `GET /api/v1/ai/providers/health`; polled every `60s`
- **Animation:** A dot that goes offline animates from emerald to crimson with a `200ms` color transition

---

### A10 — `NxNotificationBell.vue` — Global Notification Bell
- **Component:** `NxNotificationBell.vue` (new)
- **Hub:** Global
- **Priority:** P1
- **Description:** Bell icon (Lucide `Bell`) in the status bar right zone. Shows `unreadCount` badge from `useNotificationStore`. Clicking opens a `NxNotificationDrawer.vue` slide-in panel from the right.
- **Props:** None (reads from store)
- **State:** `useNotificationStore().unreadCount`
- **Animation:** When a new notification arrives, bell briefly shakes (`rotate(-15deg)` to `rotate(15deg)` over `400ms`) and the badge pops in with a spring scale
- **Echo:** `JobFailedEvent`, `WorkflowCompleted`, `ContactCreated` all push to notification store
- **Drawer Content:** List of notifications with type icon, message, timestamp, and "Mark all read" button

---

## Category B — 🪟 Modals, Drawers & Overlays

*All overlays follow the `200ms fade-in` spec. Drawers slide in from the right using `300ms cubic-bezier(0.4, 0, 0.2, 1)`. All are Teleport-ed to `<body>`.*

---

### B01 — `NxLogViewerModal.vue` — Full Log Stream Viewer
- **Component:** `NxLogViewerModal.vue` (new)
- **Hub:** Logs Hub, accessible globally via `NxNotificationBell`
- **Priority:** P1
- **Description:** Full-screen glass modal with a real-time log stream. Left sidebar has filter checkboxes (level: debug/info/warning/error; category). Main area is a virtual-scrolled list of log entries in JetBrains Mono. Each entry has a color-coded level pill.
- **Props:** `initialFilter: Object`
- **Animation:** Fade-in backdrop + scale-up panel from `0.95` to `1.0` in `200ms`
- **Features:** Search input with regex support; "Pause stream" toggle; "Export as JSON" button; auto-scroll to bottom (pausable on scroll-up)
- **Echo:** `window.Echo.private('logs').listen('LogCreated', ...)` streams new entries in real-time
- **API:** `GET /api/v1/logs?level=&category=&page=` for initial load

---

### B02 — `NxThoughtTraceDrawer.vue` — Agent Reasoning Inspector
- **Component:** `NxThoughtTraceDrawer.vue` (new)
- **Hub:** Agents Hub
- **Priority:** P1
- **Description:** A slide-in drawer (480px wide) showing the real-time reasoning loop of a selected agent. Glass terminal aesthetic using JetBrains Mono. New reasoning steps append from the bottom with a subtle fade-in. Each step has a step number, timestamp, and status icon.
- **Props:** `agentId: String`, `taskId: String`
- **Animation:** Each new line slides in from `translateX(8px)` to `translateX(0)` with `opacity 0→1` in `150ms`
- **State:** Local; fed by Echo subscription
- **Echo:** `window.Echo.private('agents.{agentId}').listen('AgentStepCompleted', ...)` appends new steps
- **Step States:** thinking (purple pulse) → tool-call (blue) → observation (slate) → response (emerald)

---

### B03 — `NxQueueModal.vue` — Job Queue Manager
- **Component:** `NxQueueModal.vue` (new)
- **Hub:** Global (triggered from A03)
- **Priority:** P2
- **Description:** A centered glass modal showing all queued and running jobs in a sortable table. Columns: `Job Name`, `Status`, `Queue`, `Attempts`, `Progress`, `Actions`. Actions: Pause, Retry, Cancel (with optimistic UI).
- **Props:** None (reads from API)
- **Animation:** Row highlight pulses amber when a job status changes
- **API:** `GET /api/v1/tasks?status=queued,running`; `DELETE /api/v1/tasks/{id}` for cancel; `POST /api/v1/tasks/{id}/retry`
- **Echo:** `WorkflowStepCompleted` updates row status in real-time

---

### B04 — `NxTaskDetailDrawer.vue` — Task Detail Slide-In
- **Component:** `NxTaskDetailDrawer.vue` (new)
- **Hub:** Workflows Hub / Task Monitor
- **Priority:** P1
- **Description:** A right-side drawer (560px) triggered by clicking any task row. Shows full task metadata: `trace_id` (JetBrains Mono, copy button), status timeline, step-by-step log accordion, agent assignment, and raw JSON payload viewer.
- **Props:** `taskId: String`
- **Features:** Copy `trace_id` button; JSON payload with syntax highlighting; step-by-step accordion using `NxLiveLoader`; "Retry Task" button (`NxActionButton` with `optimistic=true`)
- **API:** `GET /api/v1/tasks/{id}`; `GET /api/v1/tasks/{id}/logs`

---

### B05 — `NxMemoryConsolidationModal.vue` — Consolidation Preview
- **Component:** `NxMemoryConsolidationModal.vue` (new)
- **Hub:** Memory Hub
- **Priority:** P2
- **Description:** Triggered when clicking "Consolidate" on a memory cluster. Shows a before/after split view: left = source memories list, right = proposed consolidated output. User can edit the consolidated text before confirming.
- **Props:** `sourceMemoryIds: Array<String>`
- **Animation:** Left panel source cards fade and scale down; right panel consolidated card grows in
- **API:** `POST /api/v1/memories/consolidate` (preview); `PUT /api/v1/memories/{id}` (confirm)

---

### B06 — `NxWorkflowLogModal.vue` — Workflow Execution History
- **Component:** `NxWorkflowLogModal.vue` (new)
- **Hub:** Workflows Hub
- **Priority:** P2
- **Description:** Modal showing full execution log for a workflow run. Timeline view on left (step markers); selected step detail on right. Each step shows: start time, duration, input/output JSON, agent used, status.
- **Props:** `workflowId: String`, `runId: String`
- **Features:** Export execution log as JSON; Re-run from failed step button
- **API:** `GET /api/v1/workflows/{id}/runs/{runId}/logs`

---

### B07 — `NxProviderHealthModal.vue` — Provider Test & Status
- **Component:** `NxProviderHealthModal.vue` (new)
- **Hub:** Settings / AI Models Hub
- **Priority:** P2
- **Description:** Async "Test Connection" modal. On open, immediately pings the provider health endpoint and streams the latency result with a live progress animation. Shows: response time, model list count, authentication status, token limits.
- **Props:** `providerId: String`
- **Animation:** A progress ring animates while pinging; result pops in with green/red color
- **API:** `POST /api/v1/ai/providers/{id}/test`

---

### B08 — `NxApiKeyModal.vue` — API Key Manager
- **Component:** `NxApiKeyModal.vue` (new)
- **Hub:** Settings Hub
- **Priority:** P1
- **Description:** Modal for managing API keys per provider. Keys are displayed as `••••••••[last4]` with a reveal toggle (eye icon). Add/delete key actions use `NxActionButton` with `optimistic=true`.
- **Props:** `providerId: String`
- **Features:** Masked display; reveal toggle; copy-to-clipboard; "Rotate Key" button; usage stats per key
- **Animation:** Reveal animation uses a horizontal wipe using CSS clip-path
- **Security:** Never logs revealed key values; reveal auto-hides after `30s`

---

### B09 — `NxTraceInspectorDrawer.vue` — Trace ID Raw JSON Viewer
- **Component:** `NxTraceInspectorDrawer.vue` (new)
- **Hub:** Logs Hub / Task Monitor
- **Priority:** P2
- **Description:** Clicking any `trace_id` anywhere in the app opens this drawer. Full-height right drawer showing the raw JSON payload chain for that trace: request → AI provider call → response → memory extraction → delivery.
- **Props:** `traceId: String`
- **Features:** Collapsible JSON tree viewer; copy full payload button; timeline of events in the trace; "Open in Logs Hub" link
- **Animation:** JSON tree nodes expand with smooth height animation
- **API:** `GET /api/v1/logs?trace_id={traceId}`

---

### B10 — `NxContactQuickView.vue` — Contact Hover Card
- **Component:** `NxContactQuickView.vue` (new)
- **Hub:** Global (any mention of contact name)
- **Priority:** P3
- **Description:** A hoverable popover that appears when hovering over any contact name anywhere in the app. Shows avatar, canonical name, relationship type, emotional baseline mini-bar, last interaction date, and a "View Full Profile" CTA.
- **Props:** `contactId: String`
- **Animation:** Appears after `400ms` hover delay; fades in with scale from `0.95` to `1.0` in `150ms`; disappears on mouse-leave with `100ms` delay to allow moving cursor to popover
- **API:** `GET /api/v1/contacts/{id}/summary` (lightweight endpoint)

---

## Category C — 👤 Contact Profile — Virtual & Animated Experience

*Transforms the contacts view from a data table into a living, breathing relationship intelligence interface.*

---

### C01 — `NxContactCard3D.vue` — Virtual 3D Flip Card
- **Component:** `NxContactCard3D.vue` (new)
- **Hub:** Contacts Hub
- **Priority:** P1
- **Description:** The primary contact card uses CSS 3D perspective. **Front face**: Large avatar with gradient ring (color = relationship type), canonical name, presence dot (C12), channel status badges (C05), and key stats. **Back face**: AI-generated relationship summary, emotional baseline snapshot, top 3 personality traits, and quick action buttons (Message, Memory, Rules).
- **Props:** `contact: Object`, `flipped: Boolean`
- **Animation:**
  - Flip: `transform: rotateY(180deg)` with `transition: 800ms cubic-bezier(0.23, 1, 0.32, 1)`
  - Avatar ring: animates gradient rotation `360deg` over `4s` when contact is "active today"
  - On hover (desktop): subtle `rotateX(3deg) rotateY(-3deg)` tilt following mouse position via `mousemove` event
- **Trigger:** Click on card header icon flips; auto-flip on mobile tap

---

### C02 — `NxEmotionRadar.vue` — Emotional Baseline Radar Chart
- **Component:** `NxEmotionRadar.vue` (new)
- **Hub:** Contacts Hub
- **Priority:** P1
- **Description:** An ECharts radar chart mapping `DB contacts.emotional_baseline` JSON to 6 axes: Joy, Trust, Anticipation, Surprise, Sadness, Anger. Displays current baseline as a filled polygon with `AI-Core` purple color.
- **Props:** `baseline: Object`, `history: Array` (for animated transitions between states)
- **Animation:** On mount, the polygon fills in from center outward using ECharts `animationEasing: 'elasticOut'` at `800ms`. On data update, morphs between shapes.
- **Features:** Toggle between "Current" and "Historical Average"; hover on axis shows raw score
- **Data Source:** `DB contacts.emotional_baseline` JSON field

---

### C03 — `NxRelationTimeline.vue` — Animated Relationship Timeline
- **Component:** `NxRelationTimeline.vue` (new)
- **Hub:** Contacts Hub
- **Priority:** P2
- **Description:** A vertical scrollable timeline of key relationship events (first contact, memory milestones, sentiment shifts, workflow interactions). Each event is a dot on a vertical line with an expandable card.
- **Props:** `contactId: String`, `events: Array`
- **Animation:**
  - On scroll-into-view: each event card flies in from `translateX(-20px)` alternating left/right
  - The connecting line draws downward using SVG `stroke-dashoffset` animation on mount
  - Milestone events (first contact, anniversary) have a gold glow pulse
- **API:** `GET /api/v1/contacts/{id}/timeline`
- **Mobile:** Horizontal scroll timeline instead of vertical

---

### C04 — `NxEngagementRing.vue` — Animated Engagement Score Ring
- **Component:** `NxEngagementRing.vue` (new)
- **Hub:** Contacts Hub
- **Priority:** P2
- **Description:** A large SVG ring meter (120×120px) showing the contact's computed engagement score (0–100). The ring fill speed and color reflect relationship health.
- **Props:** `score: Number`, `trend: 'up' | 'down' | 'stable'`
- **Animation:** On mount, ring fills from 0 to `score` using SVG `stroke-dashoffset` over `1200ms` with `ease-out`. Trend arrow animates in after ring completes.
- **Color Logic:** `0–40` → Crimson; `40–70` → Amber; `70–100` → Emerald
- **Center Text:** Score number counts up during fill animation using `requestAnimationFrame`

---

### C05 — `NxChannelStatus.vue` — Communication Channel Badges
- **Component:** `NxChannelStatus.vue` (new)
- **Hub:** Contacts Hub
- **Priority:** P2
- **Description:** A row of channel indicator badges showing live status for each communication channel configured for the contact (WhatsApp, SMS, Email). Each badge has the channel icon and a colored dot.
- **Props:** `channels: Array<{ type, status, lastMessageAt }>`
- **Animation:** Status dots use `NxConnectionDot`-style animations; new message indicator pulses briefly
- **Click:** Clicking a channel badge opens the PeopleConnect tab filtered to that channel
- **Channel Icons:** WhatsApp (green), SMS (blue), Email (slate) — Lucide icons

---

### C06 — `NxMemoryMiniGraph.vue` — Contact Memory Graph
- **Component:** `NxMemoryMiniGraph.vue` (new)
- **Hub:** Contacts Hub
- **Priority:** P2
- **Description:** A compact (300×200px) force-directed graph showing memory nodes related to this contact. Nodes are colored by memory type (episodic=blue, semantic=purple, structured=emerald). Edges show relationships between memories.
- **Props:** `contactId: String`, `maxNodes: Number (default: 20)`
- **Library:** ECharts graph type with `layout: 'force'`
- **Animation:** Nodes spawn from center with physics simulation on load; hover on node shows memory snippet tooltip
- **Click:** Clicking a node opens `NxTraceInspectorDrawer` or full Memory Hub filtered to that memory
- **API:** `GET /api/v1/contacts/{id}/memories/graph`

---

### C07 — `NxActivityHeatmap.vue` — Interaction Frequency Heatmap
- **Component:** `NxActivityHeatmap.vue` (new)
- **Hub:** Contacts Hub
- **Priority:** P3
- **Description:** A GitHub contribution-style heatmap showing interaction frequency over the past 52 weeks. Each cell represents one day; color intensity = number of interactions.
- **Props:** `contactId: String`, `data: Array<{ date, count }>`
- **Color Scale:** `0` → `Surface-Mid`; `1–3` → light blue; `4–7` → Nexus Blue; `8+` → bright blue
- **Animation:** On mount, cells fade in column by column from left to right over `600ms`
- **Hover:** Cell tooltip shows date and interaction count
- **API:** `GET /api/v1/contacts/{id}/activity/heatmap`

---

### C08 — `NxConflictDiff.vue` — Conflict Resolution Diff View
- **Component:** `NxConflictDiff.vue` (new)
- **Hub:** Contacts Hub
- **Priority:** P1
- **Description:** When `DB contacts conflict_with_id` is set, the specific data card glows Crimson. Clicking it expands into a split-pane diff view: left = current value, right = conflicting value. Two action buttons: `[Keep This]` and `[Keep Other]`.
- **Props:** `conflictId: String`, `field: String`, `currentValue: any`, `conflictValue: any`
- **Animation:**
  - Card border pulses crimson `box-shadow: 0 0 0 2px #EF4444` at `1.5s` interval
  - On expand: split-pane slides open with `height` animation from `0` to `auto`
  - On resolution: chosen value slides to center; other slides out; border fades to emerald
- **API:** `POST /api/v1/contacts/{id}/resolve-conflict`

---

### C09 — `NxVersionHistory.vue` — Belief Version History
- **Component:** `NxVersionHistory.vue` (new)
- **Hub:** Contacts Hub
- **Priority:** P2
- **Description:** A collapsible accordion showing the version history of any belief/fact on the profile. Superseded entries are displayed with `text-decoration: line-through` and `opacity: 0.5`. Clicking any entry shows the full "Diff" in a popover.
- **Props:** `fieldKey: String`, `versions: Array<{ value, updatedAt, source, supersededAt }>`
- **Animation:** Accordion opens with `height` transition; struck-through text animates in with a red underline drawing left-to-right
- **Trigger:** `DB contacts.superseded_at` field is set
- **Diff View:** Inline `+`/`-` diff with green/red highlighting

---

### C10 — `NxTagCloud.vue` — Animated Tag Chips
- **Component:** `NxTagCloud.vue` (new)
- **Hub:** Contacts Hub
- **Priority:** P3
- **Description:** Contact preference and personality tags rendered as glass pill chips. On profile load, each chip flies in with a staggered delay (50ms per chip). Tags are color-coded by category.
- **Props:** `tags: Array<{ label, category, color }>`, `editable: Boolean`
- **Animation:** Each chip animates from `scale(0) opacity(0)` to `scale(1) opacity(1)` with a spring bounce, staggered by index × 50ms
- **Editable Mode:** Click `+` to add tag (shows autocomplete input); click `×` on existing chip removes with shrink animation
- **Categories:** personality (purple), preference (blue), topic (emerald), flag (amber)

---

### C11 — `NxPersonalityBars.vue` — Trait Strength Bars
- **Component:** `NxPersonalityBars.vue` (new)
- **Hub:** Contacts Hub
- **Priority:** P2
- **Description:** Horizontal bars showing personality trait strengths extracted from AI analysis. Each bar fills from 0 to the trait score on mount.
- **Props:** `traits: Array<{ name, score, description }>`
- **Animation:** Width fills using `transition: width 800ms ease-out` with `100ms` stagger per bar
- **Hover:** Bar highlights with a glow; tooltip shows the trait description
- **Color:** Gradient from `AI-Core` purple to `Action-Primary` blue

---

### C12 — `NxPresenceDot.vue` — Last-Active Presence Indicator
- **Component:** `NxPresenceDot.vue` (new)
- **Hub:** Contacts Hub, `NxContactQuickView`
- **Priority:** P3
- **Description:** A color-coded dot showing when the contact was last active in the system.
- **Props:** `lastSeenAt: Date`
- **Color Logic:** `today` → emerald pulse; `this week` → amber; `this month` → slate; `older` → grey static
- **Animation:** Emerald (today) state has a breathing pulse `scale(1.0)→scale(1.4)` at `2s`
- **Tooltip:** "Last active: 2 hours ago"

---

## Category D — 💬 Chat & AI Interface Enhancements

*Elevates HedraSouly from a basic chat to a true AI collaboration surface.*

---

### D01 — Token Stream Typing Effect
- **Component:** Enhancement to `ChatInterface.vue` + `NxAiBubble.vue` (new)
- **Hub:** Nexus Hub / HedraSouly
- **Priority:** P1
- **Description:** AI responses render character-by-character as `TokenStreamed` Echo events arrive. Each token chunk appends to the current message with a blinking cursor `|` at the end while streaming.
- **Animation:** Cursor blinks at `500ms` interval using `@keyframes blink`; smooth text flow with `overflow: hidden`
- **Echo:** `window.Echo.private('chat.{sessionId}').listen('TokenStreamed', e => appendToken(e.token))`
- **On Complete:** `MessageCompleted` event removes cursor; `NxAiPulse` transitions from `speaking` to `idle`

---

### D02 — `NxVoiceOrb.vue` — Voice Dictation with Waveform
- **Component:** `NxVoiceOrb.vue` (new)
- **Hub:** Nexus Hub, Mobile (floating above Bottom Tab Bar)
- **Priority:** P2
- **Description:** The floating Hédra Action Orb for voice dictation. On activation, expands to show a live audio waveform visualization using the Web Audio API `AnalyserNode`. Transcription appears in the composer input in real-time.
- **Props:** `active: Boolean`
- **Animation:**
  - Idle: slow breathing glassmorphism orb with `AI-Core` purple glow
  - Active: expands; waveform bars animate based on microphone amplitude (20 frequency bars)
  - Success: shrinks back with a flash of emerald
- **Tech:** `navigator.mediaDevices.getUserMedia({ audio: true })` → `AnalyserNode` → canvas bars
- **Mobile:** Fixed position above Bottom Tab Bar center; Desktop: floating above composer

---

### D03 — `NxAiBubble.vue` — Enhanced AI Message Renderer
- **Component:** `NxAiBubble.vue` (new, replaces `.message.agent` div)
- **Hub:** Nexus Hub / HedraSouly
- **Priority:** P1
- **Description:** Full markdown renderer using `markdown-it` + `highlight.js`. Renders: headers, bold/italic, bullet lists, numbered lists, tables, inline code, fenced code blocks with language detection and syntax highlighting, blockquotes.
- **Props:** `content: String`, `confidence: Number (0–1)`, `streaming: Boolean`
- **Features:**
  - Copy button on code blocks
  - Confidence badge (bottom-right corner): `> 0.8` → emerald; `0.6–0.8` → amber; `< 0.6` → crimson (per spec)
  - "Regenerate" button (ghost) appears on hover
- **Styling:** `prose` typography with JetBrains Mono for code; dark code theme matching Surface-High

---

### D04 — `NxMessageReactions.vue` — Emoji Reactions
- **Component:** `NxMessageReactions.vue` (new)
- **Hub:** Nexus Hub / HedraSouly
- **Priority:** P3
- **Description:** Hovering a message reveals a reaction picker (6 common emojis). Reactions are stored and displayed as pill counters below the message. Multiple users can react to the same message.
- **Props:** `messageId: String`, `reactions: Array<{ emoji, count, userReacted }>`
- **Animation:** Picker appears with scale spring from `0.5` to `1.0`; selected emoji bounces `scale(1.4)` then settles
- **API:** `POST /api/v1/messages/{id}/reactions`

---

### D05 — `NxPinnedMessages.vue` — Pinned Message Section
- **Component:** `NxPinnedMessages.vue` (new)
- **Hub:** Nexus Hub / HedraSouly
- **Priority:** P3
- **Description:** A collapsible strip at the top of the chat showing pinned messages. Pin icon appears on message hover. Pinned messages show a preview snippet.
- **Props:** `sessionId: String`, `pins: Array<Message>`
- **Animation:** Strip collapses/expands with smooth height transition
- **Echo:** `MessagePinned` event updates the pinned list in real-time

---

### D06 — `NxContextBar.vue` — Chat Context Token Meter
- **Component:** `NxContextBar.vue` (new)
- **Hub:** Nexus Hub / HedraSouly
- **Priority:** P1
- **Description:** `NxTokenMeter` embedded in the chat header. Shows context window usage for the current session. At `> 90%`, displays a "Trim Context" button that opens a modal to remove old messages from the context window.
- **Props:** Reads from `useChat().contextTokens` and `useChat().maxTokens`
- **Echo:** `TokenStreamed` event carries cumulative token count metadata

---

### D07 — Quick Actions Horizontal Scroll
- **Component:** Enhancement to `QuickActions.vue`
- **Hub:** Nexus Hub / HedraSouly
- **Priority:** P2
- **Description:** The quick actions chip row above the composer becomes a horizontally scrollable list with momentum scroll. Chips are personalized based on recent usage patterns. Long-press a chip to pin/unpin it.
- **Animation:** Chips scroll with `scroll-snap-type: x mandatory`; selected chip briefly scales to `1.05`
- **State:** `useChat().pinnedQuickActions` persists to localStorage

---

### D08 — `NxConversationExport.vue` — Export Modal
- **Component:** `NxConversationExport.vue` (new)
- **Hub:** Nexus Hub / HedraSouly
- **Priority:** P3
- **Description:** Modal to export the current conversation in multiple formats: Markdown, JSON, or PDF. Shows a preview of the exported content.
- **Props:** `sessionId: String`
- **Animation:** Format toggle uses a sliding indicator
- **API:** `GET /api/v1/conversations/{id}/export?format=markdown|json|pdf`

---

### D09 — `NxAiStatusRow.vue` — AI Thinking Status Row
- **Component:** `NxAiStatusRow.vue` (new)
- **Hub:** Nexus Hub / HedraSouly
- **Priority:** P1
- **Description:** While AI is generating, a status row appears above the response showing the current processing step: "Understanding intent → Searching memories → Generating response → Streaming". Each step transitions with a slide animation.
- **Props:** `step: 'intent' | 'memory' | 'generating' | 'streaming'`
- **Animation:** Step label slides in from right; previous step slides out to left; `NxAiPulse` in `thinking` state left of the text
- **Echo:** Each processing stage fires a WebSocket event to update the step

---

### D10 — Channel Switcher in PeopleConnect
- **Component:** Enhancement to `PeopleChat.vue`
- **Hub:** Nexus Hub / PeopleConnect
- **Priority:** P2
- **Description:** Animated tab switcher between WhatsApp and SMS views. Switching animates a sliding indicator. WhatsApp messages show tick icons: 1 tick (sent), 2 grey ticks (delivered), 2 blue ticks (read). DateTime picker triggers scheduled sends.
- **Props:** `activeChannel: 'whatsapp' | 'sms'`
- **Animation:** Channel switch slides content with `300ms cubic-bezier(0.4, 0, 0.2, 1)` (matches page-slide spec)
- **Delivery Icons:** Lucide `Check`, `CheckCheck` with color state mapping

---

## Category E — 🤖 Agent Hub Enhancements

---

### E01 — Agent Status Orb Cards with `NxAiPulse`
- **Component:** Enhancement to `AgentsView.vue` agent cards
- **Hub:** Agents Hub
- **Priority:** P1
- **Description:** Each agent card in the registry grid gains an embedded `NxAiPulse` orb whose state maps to the agent's live status: `idle` → idle animation; `running` → thinking animation; `failed` → error animation.
- **State Mapping:** `agent.status === 'idle'` → `NxAiPulse state="idle"` etc.
- **Echo:** `AgentExecuted` updates the status in real-time without page refresh

---

### E02 — `NxAgentWorkloadChart.vue` — Workload Distribution
- **Component:** `NxAgentWorkloadChart.vue` (new)
- **Hub:** Agents Hub
- **Priority:** P2
- **Description:** An ECharts donut chart showing task distribution across all agents. Each segment = one agent, sized by active task count. Center shows total active tasks.
- **Props:** `agents: Array<{ name, activeTasks }>`
- **Animation:** ECharts `animationEasing: 'elasticOut'` on mount; segment updates animate smoothly

---

### E03 — `NxAgentSparkline.vue` — Performance Sparklines
- **Component:** `NxAgentSparkline.vue` (new)
- **Hub:** Agents Hub
- **Priority:** P3
- **Description:** A tiny inline ECharts line chart (80×30px) showing tasks completed per hour over the last 24 hours. Embedded in each agent card's stats section.
- **Props:** `data: Array<Number>` (24 hourly values)
- **Color:** Emerald line with a subtle area fill gradient

---

### E04 — Thought-Trace Glass Terminal
- **Component:** `NxThoughtTraceDrawer.vue` (see B02, full spec)
- **Hub:** Agents Hub
- **Priority:** P1
- **Cross-ref:** B02 — full specification there

---

### E05 — `NxMultiAgentTimeline.vue` — Multi-Agent Coordination View
- **Component:** `NxMultiAgentTimeline.vue` (new)
- **Hub:** Agents Hub
- **Priority:** P2
- **Description:** A Gantt-style horizontal timeline showing the execution sequence of multiple agents on a shared workflow. Each agent has a row; colored bars show active execution periods; overlap indicates parallel execution.
- **Props:** `agents: Array`, `tasks: Array`
- **Animation:** Bars grow from left to right as tasks complete; new bars animate in via Echo events
- **Echo:** `AgentExecuted` and `WorkflowStepCompleted` update the timeline in real-time

---

### E06 — Agent Capability Tags with Hover Details
- **Component:** Enhancement to existing agent cards
- **Hub:** Agents Hub
- **Priority:** P3
- **Description:** Agent capability tags become interactive. Hovering a capability chip shows a tooltip with a description of that capability, the tools it uses, and the last time it was invoked.
- **Animation:** Tooltip appears with `150ms` scale-in from `0.9` origin

---

### E07 — `NxAgentCompare.vue` — A/B Agent Comparison
- **Component:** `NxAgentCompare.vue` (new)
- **Hub:** Agents Hub
- **Priority:** P3
- **Description:** A split-screen view for comparing two agent configurations side-by-side. Select Agent A on the left, Agent B on the right. Run the same prompt against both and see responses, latency, token count, and confidence side-by-side.
- **Props:** `agentAId: String`, `agentBId: String`
- **API:** `POST /api/v1/agents/compare`

---

### E08 — Agent Version Switcher
- **Component:** Enhancement to agent detail panel
- **Hub:** Agents Hub
- **Priority:** P3
- **Description:** A version history dropdown in the agent detail sidebar. Select a previous version to preview its configuration. "Restore this version" rolls back with optimistic UI.
- **Props:** `agentId: String`, `versions: Array`
- **API:** `GET /api/v1/agents/{id}/versions`; `POST /api/v1/agents/{id}/restore/{version}`

---

## Category F — 🧠 Memory Hub Enhancements

---

### F01 — Memory Decay Opacity Timeline
- **Component:** Enhancement to `MemoryView.vue` + new `NxMemoryTimeline.vue`
- **Hub:** Memory Hub
- **Priority:** P1
- **Description:** Episodic memories rendered in a vertical timeline where `opacity` maps directly to `DB memories.decay_weight`. Recent high-weight memories are fully opaque; older decayed memories fade toward `opacity: 0.3`.
- **Props:** `memories: Array (episodic type)`
- **Animation:** On data load, memories fade in to their computed opacity. A slow `0.5s` transition means decay updates look organic.
- **Filter:** A slider control lets the user filter by minimum decay weight

---

### F02 — `NxConsolidationGraph.vue` — Force-Directed Memory Graph
- **Component:** `NxConsolidationGraph.vue` (new)
- **Hub:** Memory Hub
- **Priority:** P2
- **Description:** Full-panel ECharts graph visualization showing how scattered facts (`source_event_id`) have merged into semantic insights (`memory_consolidations`). Node types: `source` (small blue circles) → `consolidated` (larger purple hexagons).
- **Props:** `nodes: Array`, `edges: Array`
- **Animation:** D3/ECharts force simulation; nodes repel and settle; edges draw with `stroke-dashoffset` animation
- **Interaction:** Click node → view memory detail; drag nodes to reposition; zoom/pan support
- **API:** `GET /api/v1/memories/consolidation-graph`
- **Mobile:** Replaced by drill-down accordion list (tab-accessible)

---

### F03 — `NxConfidenceBadge.vue` — Memory Confidence Badge
- **Component:** `NxConfidenceBadge.vue` (new)
- **Hub:** Memory Hub, Contacts Hub
- **Priority:** P1
- **Description:** A small colored pill showing the AI confidence score for any memory or belief. Maps `DB memories.confidence` column.
- **Props:** `score: Number (0–1)`
- **Color:** `> 0.8` → emerald `#10B981`; `0.6–0.8` → amber `#F59E0B`; `< 0.6` → crimson `#EF4444`
- **Display:** Shows percentage e.g. "94%" in JetBrains Mono with `tabular-nums`
- **Animation:** On score change, number counts up/down with a `300ms` animation

---

### F04 — Memory Decay Slider Filter
- **Component:** Enhancement to `MemoryView.vue`
- **Hub:** Memory Hub
- **Priority:** P2
- **Description:** A range slider control in the memory list header. Drag to filter memories by minimum decay weight. The list updates in real-time as the slider moves (client-side filter, no API call).
- **Props/State:** `minDecay: Number (0.0–1.0)` — local state
- **Animation:** Filtered-out items fade and slide up with `opacity 0, translateY(-8px)` in `200ms`

---

### F05 — `NxMemoryDiff.vue` — Before/After Consolidation Diff
- **Component:** `NxMemoryDiff.vue` (new)
- **Hub:** Memory Hub
- **Priority:** P2
- **Description:** A side-by-side or unified diff view showing how a memory changed during consolidation. Added content highlighted in emerald, removed content in crimson, same content in slate.
- **Props:** `before: String`, `after: String`, `mode: 'split' | 'unified'`
- **Tech:** Word-level diff algorithm (pure JS, no external dep needed)
- **Animation:** Changes highlight with a brief pulse on mount

---

### F06 — `NxSemanticCluster.vue` — Memory Cluster View
- **Component:** `NxSemanticCluster.vue` (new)
- **Hub:** Memory Hub
- **Priority:** P3
- **Description:** Groups semantic memories into topic clusters displayed as `NxGlassCard` groups. Each cluster has a topic label and a count badge. Clicking a cluster expands it to show individual memories.
- **Props:** `clusters: Array<{ topic, memories }>`
- **Animation:** Cluster cards expand with height animation; memories inside stagger in

---

### F07 — Memory Import/Export Modal
- **Component:** `NxMemoryImportExport.vue` (new)
- **Hub:** Memory Hub
- **Priority:** P3
- **Description:** Import memories from JSON/CSV or export all memories. Import shows a preview of parsed records before confirming. Export supports contact-scoped and global export.
- **API:** `POST /api/v1/memories/import`; `GET /api/v1/memories/export`

---

### F08 — Memory Tagging & Smart Filter
- **Component:** Enhancement to `MemoryView.vue`
- **Hub:** Memory Hub
- **Priority:** P3
- **Description:** Users can add tags to memories from the detail view. The memory list gains a multi-tag filter bar at the top. Tags are auto-suggested by AI based on memory content.
- **State:** Tags stored in `DB memories.tags` JSON column
- **API:** `PUT /api/v1/memories/{id}/tags`

---

## Category G — ⚡ Workflow Canvas Enhancements

---

### G01 — Snap-to-Grid Canvas with Ghost Preview
- **Component:** Enhancement to `WorkflowBuilder.vue`
- **Hub:** Workflows Hub
- **Priority:** P2
- **Description:** The workflow canvas gains a subtle dot-grid background. Dragged steps snap to the nearest grid point (24px grid). While dragging, a ghost/shadow preview of the step's target position is shown.
- **Animation:** Snap movement uses `transition: left 80ms, top 80ms` for a satisfying magnetic feel
- **Grid:** CSS `radial-gradient` dot grid `rgba(255,255,255,0.05)` at 24px spacing

---

### G02 — Animated SVG Flow Lines
- **Component:** Enhancement to `WorkflowBuilder.vue` SVG layer
- **Hub:** Workflows Hub
- **Priority:** P2
- **Description:** Connection lines between steps become animated flowing dashes indicating data/execution direction. When a workflow is running, the line from the active step glows Nexus Blue.
- **Animation:** CSS `stroke-dashoffset` animation on path creates a flowing dash effect; active path has `stroke: #007AFF` with `filter: drop-shadow(0 0 4px #007AFF)`

---

### G03 — Step Status Color Indicators
- **Component:** Enhancement to step nodes in `WorkflowBuilder.vue`
- **Hub:** Workflows Hub
- **Priority:** P1
- **Description:** Each step node shows its execution status via a colored border: `pending` → slate; `running` → Nexus Blue pulse; `completed` → emerald; `failed` → crimson jitter.
- **Echo:** `WorkflowStepCompleted` updates step status in real-time during execution

---

### G04 — `NxBranchVisualizer.vue` — Conditional Branch Diamonds
- **Component:** `NxBranchVisualizer.vue` (new)
- **Hub:** Workflows Hub
- **Priority:** P2
- **Description:** Conditional steps render as diamond shapes on the canvas. True/False branches extend from the diamond's right and bottom corners respectively. Branch labels show the condition expression.
- **Animation:** Diamond node uses a subtle rotate-hover effect (`rotate(45deg)` base + `rotate(1deg)` on hover)

---

### G05 — Workflow Execution Progress Overlay
- **Component:** Enhancement to `WorkflowBuilder.vue`
- **Hub:** Workflows Hub
- **Priority:** P1
- **Description:** When a workflow is actively running, an execution progress overlay appears at the top of the canvas. Shows: current step name, step X of Y, elapsed time, and an `NxJobRail`-style progress bar.
- **Echo:** `WorkflowStepCompleted` updates step counter in real-time

---

### G06 — Workflow Version History Panel
- **Component:** Enhancement to `WorkflowBuilder.vue` right panel
- **Hub:** Workflows Hub
- **Priority:** P3
- **Description:** A "History" tab in the right configuration panel showing a list of saved versions. Click to restore any previous version. Diff between current and selected version shown inline.
- **API:** `GET /api/v1/workflows/{id}/versions`; `POST /api/v1/workflows/{id}/restore/{version}`

---

### G07 — Step Error Details Popover
- **Component:** Enhancement to step nodes
- **Hub:** Workflows Hub
- **Priority:** P2
- **Description:** Failed step nodes show a red badge with error count. Clicking shows a popover with: error message, stack trace (JetBrains Mono, scrollable), retry count, and a "Retry Step" button with `optimistic=true`.
- **Animation:** Popover appears with scale-in from `0.9`; error text scrolls into view automatically

---

## Category H — 🗺️ Navigation & Shell Upgrades

---

### H01 — Collapsible Navigation Rail
- **Component:** `NxNavRail.vue` (new, replaces `Navigation.vue`)
- **Hub:** Global
- **Priority:** P1
- **Description:** Navigation Rail implementing the spec's `80px` (icon-only) / `240px` (expanded) collapsible behavior. Collapse button is a chevron at the bottom of the rail. State persists in `localStorage`.
- **Animation:** `transition: width 250ms cubic-bezier(0.4, 0, 0.2, 1)` with icon labels fading out/in
- **Icon-only Mode:** Shows only Lucide icons; labels hidden; active state shown by left border accent
- **Lucide Icons:** Each hub gets a semantic Lucide icon (2px stroke width)
- **Mobile:** Rail completely hidden; replaced by Bottom Tab Bar

---

### H02 — Universal Command Bar (Cmd+K)
- **Component:** `NxCommandBar.vue` (new)
- **Hub:** Global
- **Priority:** P1
- **Description:** Full-featured fuzzy search overlay. Opens with `Cmd+K` / `Ctrl+K`. Frosted glass overlay centered on screen. Searches contacts, memories, agents, workflows, and system routes.
- **Props:** None (global)
- **Animation:** Overlay backdrop fades in `150ms`; panel scales from `0.95` to `1.0` in `200ms`
- **Keyboard:** `↑`/`↓` navigate results; `Enter` selects; `Escape` closes
- **Result Types:** Each type has a distinct icon and color; recent searches shown before typing
- **API:** `GET /api/v1/search?q={query}&types=contacts,memories,agents,workflows`
- **Fuzzy Logic:** Client-side scoring on results using match position and score weighting

---

### H03 — Recent Items Quick Panel
- **Component:** Enhancement to Hub Sidebar
- **Hub:** Global
- **Priority:** P2
- **Description:** Below the entity list search in the Hub Sidebar, a collapsible "Recents" section shows the last 5 items visited (contacts, memories, tasks). Each item has a type icon, name, and "last visited X ago" timestamp.
- **State:** `useSystem().recentItems` stored in `localStorage`; max 10 items

---

### H04 — Pinned Hubs
- **Component:** Enhancement to `NxNavRail.vue`
- **Hub:** Global
- **Priority:** P3
- **Description:** Long-press any hub icon in the nav rail to pin it to the top of the list. Pinned hubs are visually separated with a faint divider. Drag to reorder. State persists in `localStorage`.
- **Animation:** Drag-to-reorder uses FLIP animation (`@vueuse/core` `useSortable`)

---

### H05 — Hub State Persistence
- **Component:** Enhancement to `App.vue`
- **Hub:** Global
- **Priority:** P2
- **Description:** Each hub remembers its last state (active tab, selected entity, scroll position, filter state) when navigating away and restoring. Uses Pinia persist plugin with `sessionStorage`.
- **State:** Each hub store has a `persist: true` flag on relevant state slices

---

### H06 — Animated Breadcrumb Trail
- **Component:** Enhancement to workspace header in `App.vue`
- **Hub:** Global
- **Priority:** P2
- **Description:** The workspace header shows a breadcrumb (`Hub / Entity / Detail`) with click-to-navigate. Breadcrumb items animate in/out on route change using slide-right transitions.
- **Animation:** New crumb slides in from `translateX(8px) opacity(0)` to `translateX(0) opacity(1)` in `150ms`; old crumbs slide out left

---

### H07 — Theme Switcher
- **Component:** `NxThemeSwitcher.vue` (new)
- **Hub:** Global (in Nav Rail bottom section)
- **Priority:** P2
- **Description:** Three-option toggle: Dark / Light / System Auto. Persists in `localStorage`. Light theme overrides CSS variables per the existing `[data-theme="light"]` block (extending it with spec-correct colors). System Auto listens to `prefers-color-scheme` media query.
- **Animation:** Theme switch applies `transition: background-color 300ms` to `:root` — all colors cross-fade

---

### H08 — Accessibility Font Scale Slider
- **Component:** `NxFontScale.vue` (new)
- **Hub:** Settings Hub
- **Priority:** P3
- **Description:** A slider (0.85× to 1.3×) that adjusts the root font size. Updates `document.documentElement.style.fontSize` in real-time. Persists in `localStorage`.
- **Preview:** A sample text paragraph shows live preview as slider moves

---

## Category I — 📊 Data Visualization Dashboards

---

### I01 — `NxUsageAnalytics.vue` — Usage Analytics Dashboard
- **Component:** `NxUsageAnalytics.vue` (new)
- **Hub:** Settings Hub / accessible from A07
- **Priority:** P2
- **Description:** Full dashboard panel showing: token usage over time (line chart), API calls per provider (bar chart), cost estimate (area chart), top intents triggered (pie chart). Date range selector (Today / 7d / 30d / Custom).
- **Library:** ECharts (`vue-echarts`)
- **API:** `GET /api/v1/stats/usage?range=7d`

---

### I02 — Provider Latency Comparison Chart
- **Component:** `NxLatencyChart.vue` (new)
- **Hub:** Settings / AI Models Hub
- **Priority:** P2
- **Description:** ECharts horizontal bar chart comparing P50, P95, P99 latency for each configured AI provider. Auto-refreshes every `60s`. Color-codes bars: `< 500ms` → emerald; `500ms–2s` → amber; `> 2s` → crimson.
- **API:** `GET /api/v1/ai/providers/latency-stats`

---

### I03 — Task Completion Rate Line Chart
- **Component:** `NxTaskCompletionChart.vue` (new)
- **Hub:** Workflows Hub / Dashboard
- **Priority:** P3
- **Description:** ECharts line chart showing task completion rate (%) and failure rate (%) over time. Two lines: emerald (success) and crimson (failures). Area fill between them.
- **API:** `GET /api/v1/tasks/stats/history`

---

### I04 — Memory Growth Timeline Chart
- **Component:** `NxMemoryGrowthChart.vue` (new)
- **Hub:** Memory Hub
- **Priority:** P3
- **Description:** ECharts stacked area chart showing growth of each memory type (episodic, semantic, structured, graph) over time. Illustrates the platform's learning curve.
- **API:** `GET /api/v1/memories/stats/growth`

---

### I05 — Agent Activity Heatmap
- **Component:** `NxAgentHeatmap.vue` (new)
- **Hub:** Agents Hub
- **Priority:** P3
- **Description:** GitHub-style contribution heatmap showing agent activity (tasks executed) per day over 52 weeks. Each row = one agent. Color intensity = task count.
- **API:** `GET /api/v1/agents/activity/heatmap`

---

## Category J — 📱 Mobile & Touch Enhancements

---

### J01 — Swipe-Right-to-Go-Back Gesture
- **Component:** Enhancement to mobile navigation in `App.vue`
- **Hub:** Global (Mobile)
- **Priority:** P2
- **Description:** On mobile, swiping right from the left edge (within 50px) initiates a go-back gesture. The current detail view slides right with the user's finger, revealing the list view behind it.
- **Tech:** `touchstart`/`touchmove`/`touchend` event handling; `transform: translateX()` follows finger
- **Animation:** Panel follows touch with `transform: translateX(${delta}px)`; on release, completes to `100%` or snaps back based on velocity threshold
- **Haptic:** Light `navigator.vibrate([15])` on successful back navigation

---

### J02 — Pull-to-Refresh on All Lists
- **Component:** `NxPullRefresh.vue` (new wrapper)
- **Hub:** Global (Mobile)
- **Priority:** P2
- **Description:** All scrollable lists support pull-to-refresh on mobile. A refresh indicator (spinning ring) appears at the top when pulled down past `60px` threshold.
- **Animation:** Elastic pull with `transform: translateY()` follows finger; indicator rotates and bounces in
- **Tech:** `touchstart`/`touchmove`/`touchend` on the scroll container

---

### J03 — Bottom Sheet for Mobile Menus
- **Component:** `NxBottomSheet.vue` (new)
- **Hub:** Global (Mobile)
- **Priority:** P2
- **Description:** Replaces dropdown menus and modals on mobile. Slides up from the bottom with a drag handle. Can be dragged up for full-screen or down to dismiss. Uses Glassmorphism background.
- **Props:** `open: Boolean`, `title: String`, `snapPoints: Array<Number>` (e.g., [0.4, 0.9] for 40% and 90% screen height)
- **Animation:** `transform: translateY()` follows drag; on release, snaps to nearest snap point with spring physics
- **Backdrop:** Semi-transparent backdrop; tap to dismiss

---

### J04 — Long-Press Context Menu
- **Component:** `NxContextMenu.vue` (new)
- **Hub:** Global (Mobile & Desktop)
- **Priority:** P3
- **Description:** Long-pressing (500ms) any list item opens a context menu with relevant quick actions. Desktop: right-click also triggers. Actions vary by item type (contact: Message/Memory/Pin; task: Retry/Cancel/View; memory: Edit/Delete/Export).
- **Props:** `items: Array<{ label, icon, action, danger? }>`
- **Animation:** Menu appears from the long-press point with a scale-in from `0.8` origin
- **Haptic:** `navigator.vibrate([20])` on long-press trigger (mobile only)

---

### J05 — Floating Action Button (FAB)
- **Component:** `NxFab.vue` (new)
- **Hub:** Global (Mobile)
- **Priority:** P2
- **Description:** A circular FAB in the bottom-right corner (above Bottom Tab Bar) showing the primary action for the active hub. Tapping expands to show 3–5 secondary actions as smaller buttons radiating upward.
- **Props:** `actions: Array<{ icon, label, handler }>`, `mainIcon: String`
- **Animation:**
  - Expand: secondary buttons scatter upward with `100ms` stagger + spring scale
  - Active hub changes: FAB icon morphs with a `200ms` cross-fade
- **Hub Actions:** Contacts → "New Contact"; Memory → "Add Memory"; Workflows → "New Workflow"; Chat → "New Session"

---

## Category K — ♿ Accessibility & UX Polish

---

### K01 — Skip-to-Content Link
- **Component:** Enhancement to `App.vue`
- **Hub:** Global
- **Priority:** P2
- **Description:** A visually hidden `<a href="#main-content">Skip to main content</a>` link that becomes visible on keyboard focus. Positioned at the top of the DOM before all navigation.
- **Styling:** Appears with `position: fixed; top: 0; left: 0; z-index: 9999` on `:focus`

---

### K02 — Custom Keyboard Focus Ring
- **Component:** Global CSS enhancement in `app.css`
- **Hub:** Global
- **Priority:** P2
- **Description:** The `:focus-visible` style is enhanced to use Nexus Blue `#007AFF` as the outline color with a `2px` offset. Removes the default browser ring while maintaining keyboard accessibility.
- **CSS:** Already partially implemented at `app.css:151`; needs color fix from `--color-border-focus` (currently `#4ade80`) to `#007AFF`

---

### K03 — Screen Reader Live Region Announcements
- **Component:** `NxLiveRegion.vue` (new)
- **Hub:** Global
- **Priority:** P2
- **Description:** An ARIA live region that announces dynamic content changes to screen readers. New toast notifications, task completion, and agent status changes are announced via `aria-live="polite"`.
- **Props:** `message: String`, `politeness: 'polite' | 'assertive'`
- **Usage:** Mounted in `App.vue`; `useNotificationStore` writes to it on each new notification

---

### K04 — High Contrast Mode
- **Component:** Enhancement to `NxThemeSwitcher.vue`
- **Hub:** Global
- **Priority:** P3
- **Description:** A fourth theme option "High Contrast" that replaces all semi-transparent surfaces with fully opaque ones and increases text contrast ratio to `≥ 7:1` (WCAG AAA).
- **CSS:** `[data-theme="high-contrast"]` CSS variable block with adjusted colors

---

### K05 — Reduced Motion Preference
- **Component:** Global CSS enhancement in `app.css`
- **Hub:** Global
- **Priority:** P2
- **Description:** All CSS animations and transitions are wrapped in `@media (prefers-reduced-motion: no-preference)`. When the user has reduced motion enabled, all animations are instant (duration → `0.01ms`).
- **CSS:** Add `@media (prefers-reduced-motion: reduce) { * { animation-duration: 0.01ms !important; transition-duration: 0.01ms !important; } }`

---

### K06 — Offline Indicator & Request Queue
- **Component:** `NxOfflineBanner.vue` (new)
- **Hub:** Global
- **Priority:** P2
- **Description:** When the browser goes offline (`navigator.onLine` / `offline` event), a persistent amber banner slides down from the top: "You're offline. Changes will sync when reconnected." Queued mutations are stored in `localStorage` and replayed on reconnection.
- **Animation:** Banner slides in from `translateY(-100%)` in `250ms`; slides out on reconnection
- **Echo:** Connection restore triggers queue replay

---

### K07 — Success Celebration Animation
- **Component:** `NxCelebration.vue` (new)
- **Hub:** Global
- **Priority:** P4
- **Description:** Triggered on milestone completions (workflow finished, first memory consolidated, agent task succeeded). A brief particle burst (canvas-based confetti using CSS animations) appears for `1.5s` then disappears.
- **Props:** `trigger: Boolean`, `intensity: 'light' | 'full'`
- **Mobile Haptic:** Double-tap pattern `navigator.vibrate([15, 50, 15])` (matches spec success haptic)

---

## Category L — 🚀 Power User & Advanced Features

---

### L01 — Multi-Select Bulk Action Mode
- **Component:** Enhancement to all list views
- **Hub:** All Hubs
- **Priority:** P2
- **Description:** Long-pressing any list item enters "multi-select mode". A checkbox appears on all items; selected items show a count badge in the header. A floating action bar appears at the bottom with bulk actions relevant to the current hub.
- **Animation:** Checkboxes slide in from `translateX(-8px)` with stagger; action bar slides up from `translateY(100%)`
- **Bulk Actions by Hub:** Contacts → Bulk tag, export, delete; Memory → Bulk delete, export, tag; Tasks → Bulk retry, cancel

---

### L02 — Global Keyboard Shortcut Map
- **Component:** `NxShortcutMap.vue` (new)
- **Hub:** Global
- **Priority:** P3
- **Description:** Pressing `?` anywhere in the app (when no input is focused) opens a modal showing all available keyboard shortcuts organized by category.
- **Shortcuts:** `Cmd+K` Command Bar; `Cmd+/` Toggle sidebar; `G then A` Go to Agents; `G then C` Go to Contacts; `G then M` Go to Memory; `Escape` Close modal/drawer
- **Animation:** Modal uses `200ms` fade-in; shortcut keys are rendered as `<kbd>` elements with a pill styling

---

### L03 — Split-Screen Hub View
- **Component:** Enhancement to `App.vue`
- **Hub:** Global (Desktop only, `> 1280px`)
- **Priority:** P3
- **Description:** A split-screen mode allowing two hubs to be viewed simultaneously side-by-side. Activated from a button in the workspace header. A draggable divider adjusts the split ratio.
- **Animation:** Second pane slides in from `translateX(100%)` in `300ms`; divider drag updates widths in real-time

---

### L04 — Live Data Export Center
- **Component:** `NxExportCenter.vue` (new)
- **Hub:** Settings Hub
- **Priority:** P3
- **Description:** A dedicated export center with options to export all data by type (contacts, memories, workflows, logs, agent configs) in JSON, CSV, or PDF. Shows export history and download links.
- **API:** `GET /api/v1/export?type=contacts&format=json`

---

### L05 — Intent Routing Neural Grid
- **Component:** `NxIntentGrid.vue` (new)
- **Hub:** Settings Hub / AI Models Hub
- **Priority:** P1
- **Description:** The 2D intent routing matrix UI. Rows = intent names (summarization, sentiment_analysis, extract_memory, etc.). Columns = cost profiles (Fast, Quality, Budget). Each cell contains a dropdown showing available provider/model pairs. Changing a cell updates `intent_routing` table.
- **Props:** `intents: Array`, `providers: Array`, `models: Array`
- **Animation:** Cell dropdown opens with scale-in; saving flashes the cell emerald briefly
- **Mobile:** Transforms to a vertically stacked accordion (per spec)
- **API:** `PUT /api/v1/ai/intents/routing`

---

### L06 — Dynamic Provider Add Form
- **Component:** `NxAddProviderForm.vue` (new)
- **Hub:** Settings Hub / AI Models Hub
- **Priority:** P1
- **Description:** A multi-step form for adding a new AI provider without code changes. Steps: (1) Basic info (name, base URL); (2) Auth configuration (Bearer / custom header / API key format); (3) Test connection (live ping); (4) Model sync (shows fetched models).
- **Props:** None (creates new provider)
- **Animation:** Step transitions slide left/right; Step 3 shows `NxProviderHealthModal` inline
- **API:** `POST /api/v1/ai/providers`; `POST /api/v1/ai/providers/{id}/sync-models`

---

### L07 — NProgress-Style Top Loading Bar
- **Component:** `NxTopBar.vue` (new, separate from A04)
- **Hub:** Global
- **Priority:** P1
- **Description:** A dedicated `3px` progress bar at the very top of the viewport (above the status bar) for page/route transitions and initial data loads. Animates from `0%` to `30%` instantly on start, then incrementally crawls to `90%`, then jumps to `100%` and fades out on complete.
- **State:** `useSystem().pageLoading: Boolean`
- **Animation:** Eased incremental progress; color: Nexus Blue `#007AFF`

---

### L08 — Session Undo — Last Action Revert
- **Component:** Enhancement to `useNotificationStore`
- **Hub:** Global
- **Priority:** P3
- **Description:** For destructive actions (delete contact, delete memory, cancel workflow), the toast notification includes an "Undo" button that is active for `8 seconds`. Clicking Undo replays the inverse API call.
- **State:** `useNotificationStore().pendingUndo = { action, inverseAction, expiresAt }`
- **Animation:** Toast shows a shrinking progress bar indicating the 8-second undo window

---

### L09 — Customizable Hub Layout
- **Component:** Enhancement to each hub view
- **Hub:** All Hubs
- **Priority:** P4
- **Description:** Users can toggle which panels/widgets are shown in each hub. A "Customize" button in the hub header opens a panel of toggles. Layout preference persists per-hub in `localStorage`.
- **State:** `useSystem().hubLayouts[hubKey]` — array of visible panel keys

---

### L10 — AI Quick Summary Widget
- **Component:** `NxAiSummary.vue` (new)
- **Hub:** All Hubs (collapsible)
- **Priority:** P2
- **Description:** A collapsible glass card at the top of each hub that shows a TL;DR AI-generated summary of the current hub's data state. E.g., in Agents Hub: "3 agents running, 1 failed overnight — Claude failed on task #4521 due to rate limit." Refreshed on hub mount.
- **Props:** `hub: String`
- **Animation:** Text appears with a typing effect (`NxAiBubble` in mini mode); collapsed by default
- **API:** `POST /api/v1/ai/summarize` with `{ scope: 'agents' | 'memory' | 'contacts' | ... }`

---

## Appendix — New Components Index

| Component | Category | Priority | New File |
|---|---|---|---|
| `NxStatusBar.vue` | A | P1 | ✅ New |
| `NxConnectionDot.vue` | A | P1 | ✅ New |
| `NxQueuePill.vue` | A | P2 | ✅ New |
| `NxJobRail.vue` | A | P1 | ✅ New |
| `NxAgentBadge.vue` | A | P2 | ✅ New |
| `NxRateLimitBanner.vue` | A | P2 | ✅ New |
| `NxTokenBudget.vue` | A | P2 | ✅ New |
| `NxMemoryPressure.vue` | A | P3 | ✅ New |
| `NxProviderDots.vue` | A | P2 | ✅ New |
| `NxNotificationBell.vue` | A | P1 | ✅ New |
| `NxLogViewerModal.vue` | B | P1 | ✅ New |
| `NxThoughtTraceDrawer.vue` | B/E | P1 | ✅ New |
| `NxQueueModal.vue` | B | P2 | ✅ New |
| `NxTaskDetailDrawer.vue` | B | P1 | ✅ New |
| `NxMemoryConsolidationModal.vue` | B | P2 | ✅ New |
| `NxWorkflowLogModal.vue` | B | P2 | ✅ New |
| `NxProviderHealthModal.vue` | B | P2 | ✅ New |
| `NxApiKeyModal.vue` | B | P1 | ✅ New |
| `NxTraceInspectorDrawer.vue` | B | P2 | ✅ New |
| `NxContactQuickView.vue` | B | P3 | ✅ New |
| `NxContactCard3D.vue` | C | P1 | ✅ New |
| `NxEmotionRadar.vue` | C | P1 | ✅ New |
| `NxRelationTimeline.vue` | C | P2 | ✅ New |
| `NxEngagementRing.vue` | C | P2 | ✅ New |
| `NxChannelStatus.vue` | C | P2 | ✅ New |
| `NxMemoryMiniGraph.vue` | C | P2 | ✅ New |
| `NxActivityHeatmap.vue` | C | P3 | ✅ New |
| `NxConflictDiff.vue` | C | P1 | ✅ New |
| `NxVersionHistory.vue` | C | P2 | ✅ New |
| `NxTagCloud.vue` | C | P3 | ✅ New |
| `NxPersonalityBars.vue` | C | P2 | ✅ New |
| `NxPresenceDot.vue` | C | P3 | ✅ New |
| `NxAiBubble.vue` | D | P1 | ✅ New |
| `NxVoiceOrb.vue` | D | P2 | ✅ New |
| `NxMessageReactions.vue` | D | P3 | ✅ New |
| `NxPinnedMessages.vue` | D | P3 | ✅ New |
| `NxContextBar.vue` | D | P1 | ✅ New |
| `NxConversationExport.vue` | D | P3 | ✅ New |
| `NxAiStatusRow.vue` | D | P1 | ✅ New |
| `NxAgentWorkloadChart.vue` | E | P2 | ✅ New |
| `NxAgentSparkline.vue` | E | P3 | ✅ New |
| `NxMultiAgentTimeline.vue` | E | P2 | ✅ New |
| `NxAgentCompare.vue` | E | P3 | ✅ New |
| `NxConsolidationGraph.vue` | F | P2 | ✅ New |
| `NxConfidenceBadge.vue` | F/C | P1 | ✅ New |
| `NxMemoryDiff.vue` | F | P2 | ✅ New |
| `NxSemanticCluster.vue` | F | P3 | ✅ New |
| `NxMemoryImportExport.vue` | F | P3 | ✅ New |
| `NxBranchVisualizer.vue` | G | P2 | ✅ New |
| `NxNavRail.vue` | H | P1 | ✅ New |
| `NxCommandBar.vue` | H | P1 | ✅ New |
| `NxThemeSwitcher.vue` | H | P2 | ✅ New |
| `NxFontScale.vue` | H | P3 | ✅ New |
| `NxUsageAnalytics.vue` | I | P2 | ✅ New |
| `NxLatencyChart.vue` | I | P2 | ✅ New |
| `NxTaskCompletionChart.vue` | I | P3 | ✅ New |
| `NxMemoryGrowthChart.vue` | I | P3 | ✅ New |
| `NxAgentHeatmap.vue` | I | P3 | ✅ New |
| `NxPullRefresh.vue` | J | P2 | ✅ New |
| `NxBottomSheet.vue` | J | P2 | ✅ New |
| `NxContextMenu.vue` | J | P3 | ✅ New |
| `NxFab.vue` | J | P2 | ✅ New |
| `NxLiveRegion.vue` | K | P2 | ✅ New |
| `NxOfflineBanner.vue` | K | P2 | ✅ New |
| `NxCelebration.vue` | K | P4 | ✅ New |
| `NxShortcutMap.vue` | L | P3 | ✅ New |
| `NxExportCenter.vue` | L | P3 | ✅ New |
| `NxIntentGrid.vue` | L | P1 | ✅ New |
| `NxAddProviderForm.vue` | L | P1 | ✅ New |
| `NxTopBar.vue` | L | P1 | ✅ New |
| `NxAiSummary.vue` | L | P2 | ✅ New |

---

*Total new components: 69 new Vue files + enhancements to 15 existing files = 100 feature items*
