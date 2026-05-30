# 🚀 UPDATE BLUEPRINT: UP-007 — Advanced Features: Modals, Charts & Power User (Phase 7)

## 1. Meta & Pre-flight Analysis

### Checklist
- [x] Feature 1: `NxLogViewerModal`
- [x] Feature 2: `NxThoughtTraceDrawer`
- [x] Feature 3: `NxQueueModal`
- [x] Feature 4: `NxTaskDetailDrawer`
- [x] Feature 5: `NxUsageAnalytics`
- [x] Feature 6: `NxIntentGrid`
- [x] Feature 7: `NxAddProviderForm`
- [x] Feature 8: `NxTopBar`
- [x] Feature 9: `NxAiSummary`
- [x] Feature 10: `Multi-Select Bulk Action Mode`

- **Features & Details:**
  - Build 10 Modal/Drawer components (B01–B10): `NxLogViewerModal`, `NxThoughtTraceDrawer`, `NxQueueModal`, `NxTaskDetailDrawer`, `NxMemoryConsolidationModal`, `NxWorkflowLogModal`, `NxProviderHealthModal`, `NxApiKeyModal`, `NxTraceInspectorDrawer`, `NxContactQuickView`
  - Build 5 Data Visualization components (I01–I05): `NxUsageAnalytics`, `NxLatencyChart`, `NxTaskCompletionChart`, `NxMemoryGrowthChart`, `NxAgentHeatmap`
  - Build 10 Power User components (L01–L10): Multi-select, `NxShortcutMap`, Split-screen, `NxExportCenter`, `NxIntentGrid`, `NxAddProviderForm`, `NxTopBar`, Undo, Hub customization, `NxAiSummary`
  - All overlays use Teleport to `<body>`, 200ms fade-in, 300ms slide-in drawers

- **Project Context & Versions:**
  - Vue 3 Composition API
  - ECharts via `vue-echarts` for data viz
  - Pinia stores for state
  - Echo for real-time updates
  - Design tokens from UP-001

- **Regression Check:**
  - All modals/drawers are new — no existing functionality broken
  - `NxIntentGrid` and `NxAddProviderForm` integrate with Settings Hub — verify existing settings page still works
  - `NxTopBar` adds new DOM element at viewport top — verify no layout shift

---

## 2. Feature Specifications (Per Feature)

### Feature 1: NxLogViewerModal.vue — Full Log Stream (F-MOD-01)

- **Feature Name & ID:** NxLogViewerModal — Full Log Stream Viewer — F-MOD-01
- **Specs & Requirements:**
  - Full-screen glass modal with real-time log stream
  - Left sidebar: filter checkboxes (level: debug/info/warning/error; category)
  - Main area: virtual-scrolled list of log entries in JetBrains Mono
  - Props: `initialFilter: Object`
  - Animation: fade-in backdrop + scale-up panel from 0.95 to 1.0 in 200ms
  - Features: search with regex, "Pause stream" toggle, "Export as JSON", auto-scroll (pausable)
  - Echo: `private-logs` channel listens for `LogCreated`

- **UI/UX Specs:**
  - Modal: `position: fixed; inset: 0; background: rgba(0,0,0,0.7); backdrop-filter: blur(8px); z-index: 200`
  - Panel: `width: 90vw; max-width: 1200px; height: 85vh; background: rgba(22,27,34,0.95); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px`
  - Log entry: `font-family: 'JetBrains Mono'; font-size: 12px; padding: 4px 12px; border-bottom: 1px solid rgba(255,255,255,0.05)`
  - Level pills: debug=slate, info=blue, warning=amber, error=crimson

- **Logic Workflow:**
  - `onMounted`: fetch initial logs, subscribe to Echo
  - `LogCreated` event: append to logs array, auto-scroll if not paused
  - Search: filter logs by regex on level/category/message
  - Pause: toggle `paused` state, stop auto-scroll

- **Technical Workflow:**
  - File: `resources/js/Components/NxLogViewerModal.vue` (new)
  - Props: `initialFilter: Object`
  - Emits: `close`
  - State: `logs: Array`, `paused: Boolean`, `searchQuery: String`, `filters: Object`
  - API: `GET /api/v1/logs?level=&category=&page=`
  - Echo: `window.Echo.private('logs').listen('LogCreated', appendLog)`

- **Backend Readiness:** `GET /api/v1/logs`; `LogCreated` Echo event on `private-logs`
- **Required Libraries:** `pinia`, `laravel-echo`, `axios`
- **Class/Component Names:** `NxLogViewerModal.vue`
- **Functions to Modify/Create:**
  - `fetchLogs()` — paginated load
  - `appendLog(log)` — add to array
  - `togglePause()` — pause/resume auto-scroll
  - `exportLogs()` — download JSON

---

### Feature 2: NxThoughtTraceDrawer.vue — Agent Reasoning Inspector (F-MOD-02)

- **Feature Name & ID:** NxThoughtTraceDrawer — Agent Reasoning Inspector — F-MOD-02
- **Specs & Requirements:**
  - Slide-in drawer (480px wide) showing real-time reasoning loop
  - Glass terminal aesthetic using JetBrains Mono
  - New reasoning steps append from bottom with fade-in
  - Props: `agentId: String`, `taskId: String`
  - Animation: each new line slides in from `translateX(8px)` to `translateX(0)` with `opacity 0→1` in `150ms`
  - Step states: thinking (purple pulse) → tool-call (blue) → observation (slate) → response (emerald)

- **UI/UX Specs:**
  - Drawer: `position: fixed; top: 0; right: 0; bottom: 0; width: 480px; background: rgba(22,27,34,0.95); backdrop-filter: blur(20px); border-left: 1px solid rgba(255,255,255,0.1); transform: translateX(100%); transition: transform 300ms cubic-bezier(0.4, 0, 0.2, 1)`
  - Open: `transform: translateX(0)`
  - Step line: `font-family: 'JetBrains Mono'; font-size: 12px; padding: 6px 16px; border-left: 2px solid [step-color]`

- **Logic Workflow:**
  - `onMounted`: subscribe to `private-agents.{agentId}` channel
  - `AgentStepCompleted` event: append step to `steps` array
  - Auto-scroll to bottom on new step

- **Technical Workflow:**
  - File: `resources/js/Components/NxThoughtTraceDrawer.vue` (new)
  - Props: `agentId: String`, `taskId: String`
  - Emits: `close`
  - State: `steps: Array`, `open: Boolean`
  - Echo: `window.Echo.private('agents.' + agentId).listen('AgentStepCompleted', appendStep)`

- **Backend Readiness:** `AgentStepCompleted` Echo event on `private-agents.{agentId}`
- **Required Libraries:** `laravel-echo`
- **Class/Component Names:** `NxThoughtTraceDrawer.vue`
- **Functions to Modify/Create:**
  - `open(agentId, taskId)` — subscribe, fetch history
  - `appendStep(step)` — add to array, scroll to bottom
  - `close()` — unsubscribe, clear steps

---

### Feature 3: NxQueueModal.vue — Job Queue Manager (F-MOD-03)

- **Feature Name & ID:** NxQueueModal — Job Queue Manager — F-MOD-03
- **Specs & Requirements:**
  - Centered glass modal showing queued and running jobs in sortable table
  - Columns: Job Name, Status, Queue, Attempts, Progress, Actions
  - Actions: Pause, Retry, Cancel (with optimistic UI)
  - Animation: row highlight pulses amber when status changes
  - API: `GET /api/v1/tasks?status=queued,running`; `DELETE /api/v1/tasks/{id}`; `POST /api/v1/tasks/{id}/retry`

- **UI/UX Specs:**
  - Modal: `width: 800px; max-width: 90vw; max-height: 80vh; background: rgba(22,27,34,0.95); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px`
  - Table: `width: 100%; border-collapse: collapse`
  - Row: `border-bottom: 1px solid rgba(255,255,255,0.05); transition: background-color 200ms`
  - Amber pulse: `@keyframes row-pulse { 0%, 100% { background-color: transparent; } 50% { background-color: rgba(245, 158, 11, 0.1); } }`

- **Logic Workflow:**
  - `onMounted`: fetch jobs
  - `WorkflowStepCompleted` Echo: update row status, trigger pulse animation
  - Cancel/Retry: optimistic UI → API call → revert on error

- **Technical Workflow:**
  - File: `resources/js/Components/NxQueueModal.vue` (new)
  - Props: None
  - Emits: `close`
  - State: `jobs: Array`, `sortBy: String`, `sortOrder: String`
  - API: `GET /api/v1/tasks`, `DELETE /api/v1/tasks/{id}`, `POST /api/v1/tasks/{id}/retry`

- **Backend Readiness:** `GET /api/v1/tasks`; `DELETE /api/v1/tasks/{id}`; `POST /api/v1/tasks/{id}/retry`
- **Required Libraries:** `axios`, `pinia`
- **Class/Component Names:** `NxQueueModal.vue`
- **Functions to Modify/Create:**
  - `fetchJobs()` — load queued/running tasks
  - `cancelJob(id)` — optimistic cancel
  - `retryJob(id)` — optimistic retry

---

### Feature 4: NxTaskDetailDrawer.vue — Task Detail Slide-In (F-MOD-04)

- **Feature Name & ID:** NxTaskDetailDrawer — Task Detail Drawer — F-MOD-04
- **Specs & Requirements:**
  - Right-side drawer (560px) triggered by clicking any task row
  - Shows: trace_id (JetBrains Mono, copy button), status timeline, step-by-step log accordion, agent assignment, raw JSON payload
  - Props: `taskId: String`
  - Features: Copy trace_id, JSON with syntax highlighting, step accordion with `NxLiveLoader`, "Retry Task" with `NxActionButton optimistic=true`

- **UI/UX Specs:**
  - Drawer: `width: 560px; background: rgba(22,27,34,0.95); backdrop-filter: blur(20px); border-left: 1px solid rgba(255,255,255,0.1)`
  - Trace_id: `font-family: 'JetBrains Mono'; font-size: 12px; color: rgba(255,255,255,0.6); user-select: all`
  - JSON: `font-family: 'JetBrains Mono'; font-size: 11px; background: rgba(0,0,0,0.3); padding: 12px; border-radius: 8px; overflow-x: auto`

- **Logic Workflow:**
  - `onMounted`: fetch task detail, subscribe to task channel
  - `TaskCheckpoint` Echo: append to step logs
  - Copy trace_id: `navigator.clipboard.writeText(traceId)`

- **Technical Workflow:**
  - File: `resources/js/Components/NxTaskDetailDrawer.vue` (new)
  - Props: `taskId: String`
  - Emits: `close`, `retry`
  - State: `task: Object`, `logs: Array`, `expandedSteps: Set`
  - API: `GET /api/v1/tasks/{id}`, `GET /api/v1/tasks/{id}/logs`

- **Backend Readiness:** `GET /api/v1/tasks/{id}`; `GET /api/v1/tasks/{id}/logs`
- **Required Libraries:** `axios`, `laravel-echo`
- **Class/Component Names:** `NxTaskDetailDrawer.vue`, `NxLiveLoader.vue`, `NxActionButton.vue`
- **Functions to Modify/Create:**
  - `fetchTaskDetail()` — load task
  - `copyTraceId()` — clipboard copy
  - `retryTask()` — optimistic retry

---

### Feature 5: NxUsageAnalytics.vue — Usage Dashboard (F-VIZ-01)

- **Feature Name & ID:** NxUsageAnalytics — Usage Analytics Dashboard — F-VIZ-01
- **Specs & Requirements:**
  - Full dashboard panel: token usage over time (line), API calls per provider (bar), cost estimate (area), top intents (pie)
  - Date range selector: Today / 7d / 30d / Custom
  - Library: ECharts (`vue-echarts`)
  - API: `GET /api/v1/stats/usage?range=7d`

- **UI/UX Specs:**
  - Grid: `display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px`
  - Chart cards: `NxGlassCard` with `elevation={2}`
  - Line chart: token usage, x-axis = time, y-axis = tokens
  - Bar chart: API calls per provider, horizontal bars
  - Area chart: cost estimate, stacked by provider
  - Pie chart: top intents, donut style

- **Logic Workflow:**
  - `onMounted`: fetch usage data for default range
  - Range change: refetch with new `range` param
  - Poll every `60s` for live updates

- **Technical Workflow:**
  - File: `resources/js/Components/NxUsageAnalytics.vue` (new)
  - Props: `range: String (default: '7d')`
  - Emits: `range-change`
  - State: `usageData: Object`, `loading: Boolean`
  - API: `GET /api/v1/stats/usage?range={range}`

- **Backend Readiness:** `GET /api/v1/stats/usage?range=`
- **Required Libraries:** `vue-echarts`, `echarts`, `axios`
- **Class/Component Names:** `NxUsageAnalytics.vue`
- **Functions to Modify/Create:**
  - `fetchUsageData(range)` — load stats
  - `handleRangeChange(range)` — refetch

---

### Feature 6: NxIntentGrid.vue — Intent Routing Matrix (F-PU-01)

- **Feature Name & ID:** NxIntentGrid — Intent Routing Neural Grid — F-PU-01
- **Specs & Requirements:**
  - 2D intent routing matrix: rows = intents, columns = cost profiles (Fast/Quality/Budget)
  - Each cell: dropdown of available provider/model pairs
  - Changing cell: `PUT /api/v1/ai/intents/routing` → flash cell emerald on success
  - Mobile: transforms to vertically stacked accordion
  - Animation: cell dropdown opens with scale-in; saving flashes cell emerald

- **UI/UX Specs:**
  - Desktop: `display: grid; grid-template-columns: 200px repeat(3, 1fr); gap: 1px; background: rgba(255,255,255,0.1)`
  - Cell: `background: rgba(22,27,34,0.7); padding: 8px; min-height: 44px`
  - Mobile: `display: flex; flex-direction: column`; each intent becomes accordion header

- **Logic Workflow:**
  - `onMounted`: fetch intents, providers, models
  - Cell change: optimistic update → API call → flash emerald on success → revert on error

- **Technical Workflow:**
  - File: `resources/js/Components/NxIntentGrid.vue` (new)
  - Props: `intents: Array`, `providers: Array`, `models: Array`
  - Emits: `change`
  - State: `matrix: Object` (intent × profile → model)
  - API: `GET /api/v1/ai/intents`, `GET /api/v1/ai/providers`, `PUT /api/v1/ai/intents/routing`

- **Backend Readiness:** `GET /api/v1/ai/intents`; `GET /api/v1/ai/providers`; `PUT /api/v1/ai/intents/routing`
- **Required Libraries:** `axios`, `pinia`
- **Class/Component Names:** `NxIntentGrid.vue`
- **Functions to Modify/Create:**
  - `fetchIntentRouting()` — load matrix
  - `updateCell(intent, profile, model)` — optimistic update

---

### Feature 7: NxAddProviderForm.vue — Multi-Step Provider Add (F-PU-02)

- **Feature Name & ID:** NxAddProviderForm — Multi-Step Provider Add — F-PU-02
- **Specs & Requirements:**
  - 4-step wizard: (1) Basic Info (name, base URL), (2) Auth (Bearer/custom header/API key), (3) Test Connection (live ping), (4) Model Sync (show fetched models)
  - Animation: step transitions slide left/right; Step 3 shows `NxProviderHealthModal` inline
  - API: `POST /api/v1/ai/providers`; `POST /api/v1/ai/providers/{id}/sync-models`

- **UI/UX Specs:**
  - Step indicator: `display: flex; gap: 8px; margin-bottom: 24px`
  - Step circle: `width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center`
  - Active step: `background: #007AFF; color: white`
  - Completed step: `background: #10B981; color: white`
  - Transition: `transform: translateX(20px) opacity(0)` → `translateX(0) opacity(1)` in `300ms`

- **Logic Workflow:**
  - Step 1 → 2: validate basic info
  - Step 2 → 3: save provider, show test connection
  - Step 3 → 4: on test success, sync models
  - Step 4: complete, emit `provider-created`

- **Technical Workflow:**
  - File: `resources/js/Components/NxAddProviderForm.vue` (new)
  - Props: None
  - Emits: `complete`, `cancel`
  - State: `currentStep: Number (1-4)`, `formData: Object`, `testResult: Object`
  - API: `POST /api/v1/ai/providers`, `POST /api/v1/ai/providers/{id}/test`, `POST /api/v1/ai/providers/{id}/sync-models`

- **Backend Readiness:** `POST /api/v1/ai/providers`; test connection endpoint; sync models endpoint
- **Required Libraries:** `axios`
- **Class/Component Names:** `NxAddProviderForm.vue`, `NxProviderHealthModal.vue`
- **Functions to Modify/Create:**
  - `nextStep()` — validate and advance
  - `prevStep()` — go back
  - `testConnection()` — ping provider
  - `syncModels()` — fetch and save models

---

### Feature 8: NxTopBar.vue — NProgress-Style Top Bar (F-PU-03)

- **Feature Name & ID:** NxTopBar — Page Loading Progress Bar — F-PU-03
- **Specs & Requirements:**
  - `3px` progress bar at very top of viewport (above status bar)
  - Animates: `0% → 30%` instantly on start, then incremental crawl to `90%`, then jump to `100%` and fade out
  - State: `useSystem().pageLoading`
  - Color: Nexus Blue `#007AFF`

- **UI/UX Specs:**
  - `position: fixed; top: 0; left: 0; right: 0; height: 3px; z-index: 1000`
  - `background: linear-gradient(90deg, #007AFF, #007AFF var(--progress), transparent var(--progress))`
  - Transition: `width 300ms ease`

- **Logic Workflow:**
  - `pageLoading = true`: bar appears, animates to 30% instantly
  - Incremental: `setInterval` crawls to 90% over time
  - `pageLoading = false`: jump to 100%, fade out over 400ms

- **Technical Workflow:**
  - File: `resources/js/Components/NxTopBar.vue` (new)
  - Props: None
  - Emits: None
  - State: reads `useSystem().pageLoading`
  - Computed: `progress` from loading state

- **Backend Readiness:** N/A
- **Required Libraries:** `pinia`
- **Class/Component Names:** `NxTopBar.vue`
- **Functions to Modify/Create:**
  - `useSystem()` — add `pageLoading` ref, `setPageLoading()` action

---

### Feature 9: NxAiSummary.vue — AI Quick Summary Widget (F-PU-04)

- **Feature Name & ID:** NxAiSummary — AI Quick Summary Widget — F-PU-04
- **Specs & Requirements:**
  - Collapsible glass card at top of each hub showing AI-generated TL;DR
  - Props: `hub: String` (agents, memory, contacts, workflows, etc.)
  - Animation: text appears with typing effect; collapsed by default
  - API: `POST /api/v1/ai/summarize` with `{ scope: hub }`

- **UI/UX Specs:**
  - Card: `NxGlassCard` with `elevation={1}`
  - Header: `display: flex; justify-content: space-between; align-items: center`
  - Summary text: `font-size: 14px; line-height: 1.6`
  - Typing effect: characters appear one-by-one with `50ms` delay

- **Logic Workflow:**
  - `onMounted`: fetch summary for hub
  - Expand: trigger typing animation
  - Collapse: hide text, show "Show summary" button

- **Technical Workflow:**
  - File: `resources/js/Components/NxAiSummary.vue` (new)
  - Props: `hub: String`
  - Emits: None
  - State: `summary: String`, `expanded: Boolean`, `loading: Boolean`
  - API: `POST /api/v1/ai/summarize` with `{ scope: hub }`

- **Backend Readiness:** `POST /api/v1/ai/summarize`
- **Required Libraries:** `axios`
- **Class/Component Names:** `NxAiSummary.vue`
- **Functions to Modify/Create:** None

---

### Feature 10: Multi-Select Bulk Action Mode (F-PU-05)

- **Feature Name & ID:** Multi-Select Bulk Action Mode — F-PU-05
- **Specs & Requirements:**
  - Long-press any list item enters "multi-select mode"
  - Checkbox appears on all items; selected count in header
  - Floating action bar at bottom with bulk actions
  - Bulk actions by hub: Contacts → Bulk tag, export, delete; Memory → Bulk delete, export, tag; Tasks → Bulk retry, cancel
  - Animation: checkboxes slide in from `translateX(-8px)` with stagger; action bar slides up from `translateY(100%)`

- **UI/UX Specs:**
  - Checkbox: `width: 20px; height: 20px; border: 2px solid rgba(255,255,255,0.3); border-radius: 4px`
  - Selected: `background: #007AFF; border-color: #007AFF`
  - Action bar: `position: fixed; bottom: 80px; left: 50%; transform: translateX(-50%) translateY(100%); background: rgba(22,27,34,0.95); backdrop-filter: blur(12px); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; padding: 12px 24px`

- **Logic Workflow:**
  - Long-press (500ms): enter multi-select mode, show checkboxes
  - Item click: toggle selection, update count
  - Bulk action: perform on all selected items, exit mode

- **Technical Workflow:**
  - Enhancement to all list views (`ContactsView.vue`, `MemoryView.vue`, `TaskMonitor.vue`)
  - State: `multiSelectMode: Boolean`, `selectedItems: Set` in each view or global store
  - Computed: `selectedCount`, `allSelected`

- **Backend Readiness:** Bulk API endpoints: `POST /api/v1/contacts/bulk-delete`, `POST /api/v1/memories/bulk-tag`, etc.
- **Required Libraries:** None
- **Class/Component Names:** Enhanced list views
- **Functions to Modify/Create:**
  - `enterMultiSelectMode()` — show checkboxes
  - `toggleSelect(item)` — toggle selection
  - `executeBulkAction(action)` — perform bulk operation

---

## 3. Testing Strategy

### Automated Testing

- **Unit Tests (Vitest):**
  - `NxLogViewerModal.spec.ts`: Test log rendering; test filter; test pause/resume; test export
  - `NxThoughtTraceDrawer.spec.ts`: Test step appending; test auto-scroll; test step state colors
  - `NxQueueModal.spec.ts`: Test job list rendering; test cancel/retry optimistic UI
  - `NxTaskDetailDrawer.spec.ts`: Test task detail load; test copy trace_id; test retry
  - `NxUsageAnalytics.spec.ts`: Test chart data mapping; test range change refetch
  - `NxIntentGrid.spec.ts`: Test matrix rendering; test cell change optimistic update; test mobile accordion
  - `NxAddProviderForm.spec.ts`: Test step navigation; test form validation; test provider creation
  - `NxTopBar.spec.ts`: Test progress animation; test page loading state
  - `NxAiSummary.spec.ts`: Test summary fetch; test typing animation; test collapse/expand
  - `MultiSelect.spec.ts`: Test mode entry; test selection; test bulk action

### Manual Testing Steps

1. **Log Viewer:**
   - Open modal → verify logs load with level filters
   - Type regex in search → verify logs filter
   - Toggle "Pause stream" → verify auto-scroll stops
   - Click "Export as JSON" → verify download

2. **Thought Trace:**
   - Open drawer for running agent → verify steps append in real-time
   - Verify step colors: purple (thinking) → blue (tool-call) → slate (observation) → emerald (response)

3. **Queue Modal:**
   - Open modal → verify queued/running jobs listed
   - Click Cancel → verify optimistic row removal
   - Simulate API error → verify row reappears with error toast

4. **Task Detail:**
   - Click task row → verify drawer opens with trace_id
   - Click copy button → verify trace_id copied to clipboard
   - Expand step accordion → verify `NxLiveLoader` shows logs

5. **Usage Analytics:**
   - Switch date range → verify charts update
   - Verify all 4 chart types render (line, bar, area, pie)

6. **Intent Grid:**
   - Change cell dropdown → verify optimistic update
   - Verify cell flashes emerald on success
   - Resize to mobile → verify accordion layout

7. **Add Provider:**
   - Complete 4-step wizard → verify provider created
   - Verify Step 3 shows live ping animation
   - Verify Step 4 shows fetched models

8. **Top Bar:**
   - Navigate between pages → verify progress bar animates
   - Verify bar appears at very top above status bar

9. **AI Summary:**
   - Expand summary → verify typing animation
   - Collapse → verify "Show summary" button appears

10. **Multi-Select:**
    - Long-press list item → verify checkboxes appear
    - Select multiple items → verify count updates
    - Tap bulk action → verify action executes on all selected
