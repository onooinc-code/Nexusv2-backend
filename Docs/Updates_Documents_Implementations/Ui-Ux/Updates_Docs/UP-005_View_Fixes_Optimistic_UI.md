# 🚀 UPDATE BLUEPRINT: UP-005 — View Fixes & Optimistic UI (Phase 5)

## 1. Meta & Pre-flight Analysis

- **Features & Details:**
  - Fix all 18 Real-Time & State violations from Section 4 of `uiuv_v2.md`
  - Fix all 9 Mobile Compliance violations from Section 5
  - Wire all 13 missing Echo event listeners in view components
  - Implement optimistic UI for 5 mutation actions
  - Fix `Button.vue` (add `optimistic` prop, fix color to Nexus Blue)
  - Fix `Card.vue` slot API mismatch (`#body` vs default)
  - Fix `Toast.vue` to use Pinia store instead of `window.$toast` hack
  - Fix `WorkflowBuilder.vue` mobile breakpoints and touch targets
  - Fix `DashboardView.vue` grid overflow (`minmax(400px,1fr)` → `minmax(min(400px,100%),1fr)`)
  - Progress: 7/10 UP-005 tasks completed (`ChatInterface.vue`, `AgentsView.vue`, `MemoryView.vue`, `WorkflowBuilder.vue`, `TaskMonitor.vue`, `SettingsView.vue`, `DashboardView.vue`)

- **Project Context & Versions:**
  - Vue 3 Composition API
  - Pinia stores available (UP-003)
  - Echo initialized (UP-001)
  - Nx components available (UP-002)

- **Regression Check:**
  - All view modifications are additive or bug-fix — no feature removal
  - `Button.vue` color change from green to Nexus Blue affects all buttons app-wide
  - `Card.vue` slot API change may break existing usage — must update all `Card.vue` consumers
  - `Toast.vue` Pinia migration removes `window.$toast` dependency

---

## 2. Feature Specifications (Per Feature)

### Feature 1: ChatInterface.vue — Token Streaming & Echo Wiring (F-VF-01)

- **Feature Name & ID:** ChatInterface — Token Stream + Echo — F-VF-01
- **Specs & Requirements:**
  - Wire `TokenStreamed` Echo event → `useChat().streamToken(e.token)`
  - Wire `MessageCompleted` → `useChat().finalizeMessage()`
  - Wire `MessageReceived` → `useChat().receiveMessage(e.message)`
  - Wire `MessageSent` → `useChat().confirmSent(e.messageId)`
  - Add `NxAiStatusRow` (D09) above AI response showing processing step
  - Add `NxContextBar` (D06) in chat header showing token usage
  - Add `NxAiBubble` (D03) to replace `.message.agent` div for markdown rendering
  - Add `NxVoiceOrb` (D02) for voice dictation
  - Add Quick Actions horizontal scroll (D07)

- **UI/UX Specs:**
  - Token streaming: character-by-character append with blinking cursor `|` at end
  - Cursor: `@keyframes blink { 0%, 100% { opacity: 1; } 50% { opacity: 0; } }` at `500ms`
  - `NxAiStatusRow`: shows "Understanding intent → Searching memories → Generating response → Streaming"
  - `NxContextBar`: `NxTokenMeter` embedded in header, shows `> 90%` → "Trim Context" button

- **Logic Workflow:**
  - `useChat().sendMessage()`: optimistic push user message → API call → stream tokens → finalize
  - `streamToken()`: append to last message content, update `contextTokens`
  - `finalizeMessage()`: set `streaming = false`, remove cursor

- **Technical Workflow:**
  - File: `resources/js/Pages/ChatInterface.vue` (modify)
  - Add Echo subscription in `onMounted`: `window.Echo.private('chat.' + sessionId).listen('TokenStreamed', ...)`
  - Replace agent message div with `<NxAiBubble :content="msg.content" :confidence="msg.confidence" :streaming="msg.streaming" />`
  - Add `<NxAiStatusRow :step="currentStep" />` above AI response
  - Add `<NxContextBar />` in header

- **Backend Readiness:** Echo events: `TokenStreamed`, `MessageCompleted`, `MessageReceived`, `MessageSent`
- **Required Libraries:** `pinia`, `laravel-echo`, `markdown-it`, `highlight.js`
- **Class/Component Names:** `ChatInterface.vue`, `NxAiBubble.vue`, `NxAiStatusRow.vue`, `NxContextBar.vue`, `NxVoiceOrb.vue`
- **Functions to Modify/Create:**
  - `setupEchoListeners()` — subscribe to chat channel events
  - `handleTokenStreamed(e)` — call `useChat().streamToken(e.token)`
  - `handleMessageCompleted()` — call `useChat().finalizeMessage()`

---

### Feature 2: AgentsView.vue — Orb Cards + Echo (F-VF-02)

- **Feature Name & ID:** AgentsView — Orb Cards + Echo — F-VF-02
- **Specs & Requirements:**
  - Embed `NxAiPulse` orb in each agent card, state mapped to `agent.status`
  - Wire `AgentExecuted` Echo → `useSystem().incrementAgentCount()` / `decrementAgentCount()`
  - Add `NxAgentWorkloadChart` (E02) donut chart
  - Add `NxAgentSparkline` (E03) inline performance chart
  - Add `NxThoughtTraceDrawer` (B02/E04) slide-in for agent reasoning

- **UI/UX Specs:**
  - Agent card: `NxGlassCard` with `NxAiPulse` orb in header
  - Status mapping: `idle` → idle pulse; `running` → thinking rotation; `failed` → error jitter
  - `NxAgentWorkloadChart`: ECharts donut, center shows total active tasks

- **Logic Workflow:**
  - `AgentExecuted` event: update agent status in `useWorkflows()` or local state
  - `NxAiPulse` state reactive to `agent.status`

- **Technical Workflow:**
  - File: `resources/js/Pages/AgentsView.vue` (modify)
  - Add Echo: `window.Echo.private('agents').listen('AgentExecuted', updateAgentStatus)`
  - Replace agent status indicator with `<NxAiPulse :state="agent.status" />`
  - Add `<NxAgentWorkloadChart :agents="agents" />` in sidebar
  - Add click handler to open `<NxThoughtTraceDrawer :agentId="agent.id" />`

- **Backend Readiness:** Echo event: `AgentExecuted`
- **Required Libraries:** `pinia`, `laravel-echo`, `vue-echarts`, `echarts`
- **Class/Component Names:** `AgentsView.vue`, `NxAiPulse.vue`, `NxAgentWorkloadChart.vue`, `NxThoughtTraceDrawer.vue`
- **Functions to Modify/Create:**
  - `setupEchoListeners()` — subscribe to agents channel
  - `updateAgentStatus(e)` — update local agent state
  - `openThoughtTrace(agentId)` — open drawer

---

### Feature 3: MemoryView.vue — Decay Opacity + Echo (F-VF-03)

- **Feature Name & ID:** MemoryView — Decay Opacity + Echo — F-VF-03
- **Specs & Requirements:**
  - Wire `MemoriesExtracted`, `MemoryIndexed`, `MemoryVectorized` Echo events
  - Map `DB memories.decay_weight` → `opacity` (1.0 → 0.3)
  - Add `NxConfidenceBadge` (F03) showing confidence score
  - Add `NxConsolidationGraph` (F02) force-directed graph
  - Add decay slider filter (F04)

- **UI/UX Specs:**
  - Memory card opacity: `opacity: 1 - (decay_weight * 0.7)` (min 0.3)
  - `NxConfidenceBadge`: `> 0.8` → emerald; `0.6–0.8` → amber; `< 0.6` → crimson
  - `NxConsolidationGraph`: ECharts force-directed, nodes = memories, edges = relationships

- **Logic Workflow:**
  - `MemoriesExtracted` → add new memory to list
  - `MemoryIndexed` → update memory `indexed_at` timestamp
  - Decay slider: client-side filter by `minDecay` (0.0–1.0)

- **Technical Workflow:**
  - File: `resources/js/Pages/MemoryView.vue` (modify)
  - Add Echo listeners for memory events
  - Add `:style="{ opacity: 1 - (memory.decay_weight * 0.7) }"` to memory cards
  - Add `<NxConfidenceBadge :score="memory.confidence" />` in card header
  - Add `<NxConsolidationGraph />` in detail panel
  - Add decay range slider in list header

- **Backend Readiness:** Echo events: `MemoriesExtracted`, `MemoryIndexed`, `MemoryVectorized`
- **Required Libraries:** `pinia`, `laravel-echo`, `vue-echarts`, `echarts`
- **Class/Component Names:** `MemoryView.vue`, `NxConfidenceBadge.vue`, `NxConsolidationGraph.vue`
- **Functions to Modify/Create:**
  - `setupEchoListeners()` — subscribe to memory channel
  - `handleMemoryExtracted(e)` — add memory
  - `handleDecayChange(value)` — filter list

---

### Feature 4: WorkflowBuilder.vue — Status Colors + Mobile (F-VF-04)

- **Feature Name & ID:** WorkflowBuilder — Status Colors + Mobile — F-VF-04
- **Specs & Requirements:**
  - Add step status color indicators (G03): `pending` → slate; `running` → Nexus Blue pulse; `completed` → emerald; `failed` → crimson jitter
  - Wire `WorkflowStepCompleted` Echo → `useWorkflows().updateStepStatus()`
  - Add snap-to-grid canvas (G01): 24px dot grid, steps snap on drag
  - Add animated SVG flow lines (G02): flowing dashes, active path glows
  - Add execution progress overlay (G05) when workflow running
  - Fix mobile breakpoints: 3-col layout → single column at `< 768px`
  - Fix button touch targets to `≥ 44×44px`

- **UI/UX Specs:**
  - Step node status border: `2px solid` per status color
  - `running` pulse: `@keyframes pulse-blue { 0%, 100% { box-shadow: 0 0 0 0 rgba(0,122,255,0); } 50% { box-shadow: 0 0 8px 2px rgba(0,122,255,0.4); } }`
  - Grid: CSS `radial-gradient(circle, rgba(255,255,255,0.05) 1px, transparent 1px)` at `24px` spacing
  - Flow line: `stroke-dasharray: 8 4; animation: flow 1s linear infinite`
  - Mobile: `@media (max-width: 767px) { .workflow-canvas { flex-direction: column; } }`

- **Logic Workflow:**
  - `WorkflowStepCompleted` → update step status in store → UI updates reactively
  - Snap-to-grid: on `dragend`, round `left/top` to nearest `24px` increment

- **Technical Workflow:**
  - File: `resources/js/Pages/WorkflowBuilder.vue` (modify)
  - Add Echo: `window.Echo.private('workflows.' + workflowId).listen('WorkflowStepCompleted', ...)`
  - Add step status computed: `statusColor` from `step.status`
  - Add grid background div with radial-gradient
  - Add SVG layer for flow lines with `stroke-dashoffset` animation
  - Add mobile breakpoint styles in `<style scoped>`

- **Backend Readiness:** Echo event: `WorkflowStepCompleted`
- **Required Libraries:** `pinia`, `laravel-echo`
- **Class/Component Names:** `WorkflowBuilder.vue`, `NxBranchVisualizer.vue`
- **Functions to Modify/Create:**
  - `setupEchoListeners()` — subscribe to workflow channel
  - `handleStepCompleted(e)` — update step status
  - `snapToGrid(value)` — round to 24px

---

### Feature 5: TaskMonitor.vue — Echo + Optimistic (F-VF-05)

- **Feature Name & ID:** TaskMonitor — Echo + Optimistic — F-VF-05
- **Specs & Requirements:**
  - Wire `WorkflowStepCompleted` → update task row status
  - Wire `JobProgressUpdated` → update progress bar
  - Add `NxTaskDetailDrawer` (B04) slide-in on row click
  - Add optimistic "Retry" button with `NxActionButton optimistic=true`
  - Add `NxLiveLoader` (F-UI-04) for running tasks

- **UI/UX Specs:**
  - Task row status: `queued` → amber; `running` → blue pulse; `completed` → emerald; `failed` → crimson
  - `NxTaskDetailDrawer`: 560px right drawer with trace_id, step logs, JSON payload
  - `NxLiveLoader`: pulsing pill with expandable terminal log

- **Logic Workflow:**
  - `WorkflowStepCompleted` → find task by `trace_id` → update status
  - Row click → open drawer with `NxTaskDetailDrawer :taskId="task.id"`

- **Technical Workflow:**
  - File: `resources/js/Pages/TaskMonitor.vue` (modify)
  - Add Echo listeners for task events
  - Add `NxTaskDetailDrawer` component
  - Add `NxLiveLoader` in running task rows
  - Add `NxActionButton` for retry with `optimistic=true`

- **Backend Readiness:** Echo events: `WorkflowStepCompleted`, `JobProgressUpdated`
- **Required Libraries:** `pinia`, `laravel-echo`
- **Class/Component Names:** `TaskMonitor.vue`, `NxTaskDetailDrawer.vue`, `NxLiveLoader.vue`, `NxActionButton.vue`
- **Functions to Modify/Create:**
  - `setupEchoListeners()` — subscribe to task channel
  - `openTaskDetail(taskId)` — open drawer
  - `retryTask(taskId)` — optimistic retry

---

### Feature 6: SettingsView.vue — Toast + Intent Grid (F-VF-06)

- **Feature Name & ID:** SettingsView — Toast + Intent Grid — F-VF-06
- **Specs & Requirements:**
  - Replace all `alert()` calls with `useNotificationStore().addToast()`
  - Add `NxIntentGrid` (L05) — 2D intent routing matrix
  - Add `NxAddProviderForm` (L06) — multi-step provider add form
  - Add optimistic toggle for settings (instant update, revert on error)

- **UI/UX Specs:**
  - `NxIntentGrid`: rows = intents, columns = cost profiles (Fast/Quality/Budget), cells = provider/model dropdowns
  - `NxAddProviderForm`: 4-step wizard (Basic Info → Auth → Test → Model Sync)
  - Toast: glass pill, auto-dismiss after 5s, undo button for 8s

- **Logic Workflow:**
  - Setting toggle: optimistic update → API call → revert on error with toast
  - `NxIntentGrid` cell change: `PUT /api/v1/ai/intents/routing` → flash cell emerald on success

- **Technical Workflow:**
  - File: `resources/js/Pages/SettingsView.vue` (modify)
  - Replace `alert('saved')` with `useNotificationStore().addToast({ type: 'success', message: 'Settings saved' })`
  - Add `<NxIntentGrid />` component
  - Add `<NxAddProviderForm />` component
  - Wrap setting toggles in `useOptimisticUpdate()`

- **Backend Readiness:** `PUT /api/v1/ai/intents/routing`; `POST /api/v1/ai/providers`
- **Required Libraries:** `pinia`, `axios`
- **Class/Component Names:** `SettingsView.vue`, `NxIntentGrid.vue`, `NxAddProviderForm.vue`
- **Functions to Modify/Create:**
  - `saveSetting(key, value)` — optimistic toggle
  - `handleIntentChange(intent, profile, model)` — update routing

---

### Feature 7: DashboardView.vue — Grid Fix + NxGlassCard (F-VF-07)

- **Feature Name & ID:** DashboardView — Grid Fix + Glass Cards — F-VF-07
- **Specs & Requirements:**
  - Fix grid overflow: `minmax(400px, 1fr)` → `minmax(min(400px, 100%), 1fr)`
  - Replace `.kpi-card` divs with `NxGlassCard` components
  - Add `NxUsageAnalytics` (I01) chart panel
  - Add `NxAiSummary` (L10) collapsible summary card

- **UI/UX Specs:**
  - Grid: `grid-template-columns: repeat(auto-fit, minmax(min(400px, 100%), 1fr))`
  - `NxGlassCard`: `elevation={2}` for KPI cards; `hoverable` for interactive cards
  - `NxUsageAnalytics`: ECharts line + bar + area + pie charts

- **Logic Workflow:**
  - KPI data from `useSystem()` store
  - `NxUsageAnalytics` polls `GET /api/v1/stats/usage` every `60s`

- **Technical Workflow:**
  - File: `resources/js/Pages/DashboardView.vue` (modify)
  - Fix grid template in `<style scoped>`
  - Replace `.kpi-card` divs with `<NxGlassCard>` components
  - Add `<NxUsageAnalytics />` in dashboard grid
  - Add `<NxAiSummary hub="dashboard" />` at top

- **Backend Readiness:** `GET /api/v1/stats/usage`
- **Required Libraries:** `pinia`, `axios`, `vue-echarts`, `echarts`
- **Class/Component Names:** `DashboardView.vue`, `NxGlassCard.vue`, `NxUsageAnalytics.vue`, `NxAiSummary.vue`
- **Functions to Modify/Create:**
  - `fetchDashboardStats()` — load KPI data
  - `refreshAnalytics()` — poll usage stats

---

### Feature 8: ContactsView.vue — Optimistic Add + 3D Card (F-VF-08)

- **Feature Name & ID:** ContactsView — Optimistic Add + 3D Card — F-VF-08
- **Specs & Requirements:**
  - Implement optimistic `addContact()` via `useContacts().addContact()`
  - Replace simple contact card with `NxContactCard3D` (C01) flip card
  - Add `NxEmotionRadar` (C02) ECharts radar
  - Add `NxRelationTimeline` (C03) vertical timeline
  - Add `NxConflictDiff` (C08) for conflict resolution

- **UI/UX Specs:**
  - `NxContactCard3D`: CSS 3D perspective, flip on click, avatar ring gradient rotation
  - `NxEmotionRadar`: 6-axis radar (Joy, Trust, Anticipation, Surprise, Sadness, Anger)
  - `NxRelationTimeline`: vertical timeline with SVG `stroke-dashoffset` draw animation
  - `NxConflictDiff`: card glows crimson, split-pane diff on expand

- **Logic Workflow:**
  - `addContact(data)`: optimistic push to `useContacts().contacts` → API call → remove `_optimistic` flag on success → revert on error
  - Contact card click: flip card or open detail drawer

- **Technical Workflow:**
  - File: `resources/js/Pages/ContactsView.vue` (modify)
  - Replace contact card div with `<NxContactCard3D :contact="contact" />`
  - Add `<NxEmotionRadar :baseline="contact.emotional_baseline" />` in detail panel
  - Add `<NxRelationTimeline :contactId="contact.id" />` in detail panel
  - Add optimistic "Add Contact" form using `NxActionButton optimistic=true`

- **Backend Readiness:** `POST /api/v1/contacts`; `GET /api/v1/contacts/{id}/timeline`
- **Required Libraries:** `pinia`, `axios`, `vue-echarts`, `echarts`
- **Class/Component Names:** `ContactsView.vue`, `NxContactCard3D.vue`, `NxEmotionRadar.vue`, `NxRelationTimeline.vue`, `NxConflictDiff.vue`
- **Functions to Modify/Create:**
  - `handleAddContact(data)` — optimistic add
  - `handleContactClick(contact)` — flip card or open detail

---

### Feature 9: Button.vue — Fix Optimistic Prop + Color (F-VF-09)

- **Feature Name & ID:** Button.vue — Fix Optimistic + Color — F-VF-09
- **Specs & Requirements:**
  - Add `optimistic` prop (Boolean) to `Button.vue`
  - Fix color from green (`#4ade80`) to Nexus Blue (`#007AFF`)
  - Add `optimisticState` v-model support (`pending` | `success` | `error`)
  - Add `loading` slot for custom loading indicator

- **UI/UX Specs:**
  - Primary: `background: #007AFF; color: white`
  - Optimistic success: `background: #10B981` (brief flash)
  - Optimistic error: `background: #EF4444` with shake animation
  - Touch target: `min-height: 44px; min-width: 44px`

- **Logic Workflow:**
  - `optimistic=true`: on click, emit `click` with `{ optimistic: true }`
  - Parent sets `optimisticState` via `v-model:optimisticState`
  - Button shows visual feedback based on state

- **Technical Workflow:**
  - File: `resources/js/Components/Button.vue` (modify)
  - Add props: `optimistic: Boolean`, `optimisticState: String`
  - Add emits: `click`, `update:optimisticState`
  - Fix color classes from `bg-green-500` → `bg-[#007AFF]`

- **Backend Readiness:** N/A
- **Required Libraries:** N/A
- **Class/Component Names:** `Button.vue`
- **Functions to Modify/Create:**
  - `handleClick()` — emit with optimistic flag
  - `buttonClass` computed — include optimistic state styles

---

### Feature 10: Card.vue — Fix Slot API (F-VF-10)

- **Feature Name & ID:** Card.vue — Fix Slot API — F-VF-10
- **Specs & Requirements:**
  - Fix slot API mismatch: `#body` slot → default slot
  - Keep `#header` and `#footer` slots as-is
  - Update all consumers of `Card.vue` to use default slot instead of `#body`

- **UI/UX Specs:** N/A (API fix only)
- **Logic Workflow:** N/A
- **Technical Workflow:**
  - File: `resources/js/Components/Card.vue` (modify)
  - Rename `<slot name="body" />` → `<slot />` (default slot)
  - Update all files using `<Card><template #body>...</template></Card>` → `<Card>...</Card>`

- **Backend Readiness:** N/A
- **Required Libraries:** N/A
- **Class/Component Names:** `Card.vue`
- **Functions to Modify/Create:** None

---

### Feature 11: Toast.vue — Pinia Migration (F-VF-11)

- **Feature Name & ID:** Toast.vue — Pinia Migration — F-VF-11
- **Specs & Requirements:**
  - Replace `window.$toast` hack with `useNotificationStore()`
  - Remove global `$toast` registration from `app.js`
  - Toast component reads from `useNotificationStore().toasts` array
  - Add undo button for destructive actions (8s countdown)

- **UI/UX Specs:**
  - Toast: glass pill, `position: fixed; bottom: 24px; right: 24px` (LTR) / `left: 24px` (RTL)
  - Undo button: shows shrinking progress bar for 8s window
  - Auto-dismiss after 5s with fade-out animation

- **Logic Workflow:**
  - `useNotificationStore().addToast()` adds to `toasts` array
  - Toast component watches `toasts` and renders each
  - Auto-remove after `duration` via `setTimeout`

- **Technical Workflow:**
  - File: `resources/js/Components/Toast.vue` (modify)
  - Import `useNotificationStore` from `../stores/useNotificationStore`
  - Replace `window.$toast` calls with `useNotificationStore().addToast()`
  - Add undo button with countdown timer

- **Backend Readiness:** N/A
- **Required Libraries:** `pinia`
- **Class/Component Names:** `Toast.vue`, `useNotificationStore.js`
- **Functions to Modify/Create:**
  - Remove `$toast` global registration from `app.js`
  - Update all `$toast()` calls to `useNotificationStore().addToast()`

---

## 3. Testing Strategy

### Automated Testing

- **Unit Tests (Vitest):**
  - `ChatInterface.spec.ts`: Test `TokenStreamed` event appends tokens; test `NxAiBubble` renders markdown; test `NxContextBar` token threshold
  - `AgentsView.spec.ts`: Test `NxAiPulse` state mapping; test `AgentExecuted` event updates count
  - `MemoryView.spec.ts`: Test decay opacity calculation; test `NxConfidenceBadge` color threshold
  - `WorkflowBuilder.spec.ts`: Test step status colors; test snap-to-grid; test mobile breakpoint layout
  - `TaskMonitor.spec.ts`: Test `WorkflowStepCompleted` updates row; test `NxTaskDetailDrawer` opens on click
  - `SettingsView.spec.ts`: Test `alert()` replaced with toast; test `NxIntentGrid` cell change
  - `DashboardView.spec.ts`: Test grid doesn't overflow at 375px; test `NxGlassCard` renders
  - `ContactsView.spec.ts`: Test optimistic `addContact()`; test `NxContactCard3D` flip
  - `Button.spec.ts`: Test `optimistic` prop; test color is `#007AFF`; test touch target `≥ 44px`
  - `Card.spec.ts`: Test default slot renders; test `#header` and `#footer` slots
  - `Toast.spec.ts`: Test `addToast()` renders; test auto-dismiss; test undo button

### Manual Testing Steps

1. **Chat Token Streaming:**
   - Send message → verify user message appears instantly
   - Verify AI response streams character-by-character with blinking cursor
   - Verify `NxAiStatusRow` shows processing steps
   - Verify `NxContextBar` shows token usage

2. **Agent Orb Cards:**
   - Navigate to Agents Hub → verify each agent card has `NxAiPulse` orb
   - Trigger `AgentExecuted` from backend → verify orb state changes

3. **Memory Decay:**
   - Navigate to Memory Hub → verify older memories have lower opacity
   - Adjust decay slider → verify list filters in real-time

4. **Workflow Builder Mobile:**
   - Resize to 375px → verify layout switches to single column
   - Verify all buttons are `≥ 44×44px`
   - Drag step → verify snap-to-grid (24px increments)

5. **Optimistic UI:**
   - Send message → disconnect network → verify message reverts with error toast
   - Add contact → verify card appears instantly
   - Toggle setting → verify instant update, revert on simulated error

6. **Toast Migration:**
   - Trigger notification → verify toast appears via Pinia store
   - Verify `window.$toast` is undefined (removed)

7. **Dashboard Grid:**
   - Resize to 375px → verify grid doesn't overflow horizontally
   - Verify KPI cards use `NxGlassCard` with elevation

8. **RTL Test:**
   - Set `dir="rtl"` → verify all views mirror correctly
   - Verify toast position flips to `left: 24px`
