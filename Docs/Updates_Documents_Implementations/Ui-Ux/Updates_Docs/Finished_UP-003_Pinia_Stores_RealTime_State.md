# 🚀 UPDATE BLUEPRINT: UP-003 — Pinia Stores & Real-Time State (Phase 3)

## 1. Meta & Pre-flight Analysis

- **Features & Details:**
  - Create 5 global Pinia stores: `useChat`, `useContacts`, `useWorkflows`, `useSystem`, `useNotificationStore`
  - Wire all 13 missing Echo event listeners across the app
  - Implement optimistic UI patterns for 5 mutation actions
  - All stores must support RTL and mobile responsiveness

- **Project Context & Versions:**
  - Vue 3 Composition API with Pinia
  - Laravel Echo + Reverb for WebSocket events
  - Axios for REST API calls
  - Stores created in `resources/js/stores/` directory

- **Regression Check:**
  - Stores are additive — no existing functionality broken
  - `useNotificationStore` replaces `window.$toast` hack in `Toast.vue`
  - All stores use `$patch` for batch updates to minimize re-renders
  - Echo subscriptions are cleaned up on `onUnmounted`

---

## 2. Feature Specifications (Per Feature)

### Feature 1: useChat Store (F-ST-01)

- **Feature Name & ID:** useChat — Chat State Management — F-ST-01
- **Specs & Requirements:**
  - State: `messages: Array`, `streaming: Boolean`, `draft: String`, `sessionId: String`, `contextTokens: Number`, `maxTokens: Number (6000)`
  - Actions: `sendMessage(content)`, `revertLastMessage()`, `streamToken(token)`, `finalizeMessage()`, `clearDraft()`, `setSession(id)`
  - Persistence: `sessionId` and `draft` persist to `sessionStorage`

- **UI/UX Specs:**
  - `streaming` flag controls `NxAiPulse` state in `NxAiStatusRow`
  - `contextTokens` drives `NxContextBar` (D06) and `NxTokenMeter` color threshold
  - RTL: message bubbles align right for user, left for agent (already handled by existing CSS)

- **Logic Workflow:**
  - `sendMessage()`: optimistic — push user message to `messages` immediately, emit API call, revert on error
  - `streamToken()`: append token to last message content, update `contextTokens`
  - `finalizeMessage()`: set `streaming = false`, emit `MessageCompleted` event

- **Technical Workflow:**
  - File: `resources/js/stores/useChat.js`
  - State: `ref([])` for messages, `ref('')` for draft, `ref(false)` for streaming
  - Computed: `currentSessionMessages`, `tokenPercentage`
  - Echo: `private-chat.{sessionId}` channel listens for `TokenStreamed`, `MessageCompleted`, `MessageReceived`

- **Backend Readiness:**
  - Echo events: `TokenStreamed`, `MessageCompleted`, `MessageReceived`, `MessageSent`
  - API: `POST /api/v1/chat/send`, `GET /api/v1/chat/sessions/{id}`

- **Required Libraries:** `pinia`, `laravel-echo`, `axios`
- **Class/Component Names:** `useChat.js`
- **Functions to Modify/Create:**
  - `sendMessage(content)` — optimistic push + API call
  - `revertLastMessage()` — pop last message, restore draft
  - `streamToken(token)` — append to last message
  - `finalizeMessage()` — set streaming false

---

### Feature 2: useContacts Store (F-ST-02)

- **Feature Name & ID:** useContacts — Contact State Management — F-ST-02
- **Specs & Requirements:**
  - State: `contacts: Array`, `selected: Object | null`, `loading: Boolean`, `searchQuery: String`
  - Actions: `fetchContacts()`, `selectContact(id)`, `addContact(data)`, `updateContact(id, data)`, `deleteContact(id)`, `searchContacts(query)`
  - Optimistic: `addContact()` shows card instantly with draft fallback

- **UI/UX Specs:**
  - `selected` drives `NxContactCard3D` (C01) flip state
  - `searchQuery` drives Hub Sidebar filter
  - RTL: contact list items align right; search input RTL-aware

- **Logic Workflow:**
  - `fetchContacts()`: `GET /api/v1/contacts` → populate `contacts`
  - `addContact(data)`: optimistic — push to `contacts` with `_optimistic: true` flag, API call, remove flag on success, revert on error
  - `selectContact(id)`: set `selected`, emit `contact-selected` event

- **Technical Workflow:**
  - File: `resources/js/stores/useContacts.js`
  - State: `ref([])`, `ref(null)`, `ref(false)`, `ref('')`
  - Computed: `filteredContacts`, `selectedContact`
  - Echo: `private-contacts` channel listens for `ContactCreated`, `ContactUpdated`

- **Backend Readiness:**
  - Echo events: `ContactCreated`, `ContactUpdated`
  - API: `GET /api/v1/contacts`, `POST /api/v1/contacts`, `PUT /api/v1/contacts/{id}`, `DELETE /api/v1/contacts/{id}`

- **Required Libraries:** `pinia`, `laravel-echo`, `axios`
- **Class/Component Names:** `useContacts.js`
- **Functions to Modify/Create:**
  - `fetchContacts()` — GET all contacts
  - `addContact(data)` — optimistic add
  - `selectContact(id)` — set selected

---

### Feature 3: useWorkflows Store (F-ST-03)

- **Feature Name & ID:** useWorkflows — Workflow State Management — F-ST-03
- **Specs & Requirements:**
  - State: `workflows: Array`, `current: Object | null`, `selectedStep: Object | null`, `executionProgress: Number`
  - Actions: `fetchWorkflows()`, `selectWorkflow(id)`, `selectStep(stepId)`, `updateStepStatus(stepId, status)`, `setExecutionProgress(progress)`
  - Optimistic: `selectWorkflow()` updates UI instantly

- **UI/UX Specs:**
  - `current` drives `WorkflowBuilder.vue` canvas
  - `selectedStep` drives step detail panel
  - `executionProgress` drives `NxJobRail` (A04) and execution overlay (G05)

- **Logic Workflow:**
  - `fetchWorkflows()`: `GET /api/v1/workflows` → populate
  - `updateStepStatus()`: optimistic — update local step status, API call, revert on error
  - `setExecutionProgress()`: update `executionProgress` (0–100)

- **Technical Workflow:**
  - File: `resources/js/stores/useWorkflows.js`
  - State: `ref([])`, `ref(null)`, `ref(null)`, `ref(0)`
  - Computed: `currentWorkflowSteps`, `isExecuting`
  - Echo: `private-workflows.{workflowId}` listens for `WorkflowStepCompleted`, `WorkflowStarted`, `WorkflowCompleted`

- **Backend Readiness:**
  - Echo events: `WorkflowStepCompleted`, `WorkflowStarted`, `WorkflowCompleted`
  - API: `GET /api/v1/workflows`, `POST /api/v1/workflows`, `PUT /api/v1/workflows/{id}/steps/{stepId}`

- **Required Libraries:** `pinia`, `laravel-echo`, `axios`
- **Class/Component Names:** `useWorkflows.js`
- **Functions to Modify/Create:**
  - `fetchWorkflows()` — GET all workflows
  - `selectWorkflow(id)` — set current
  - `updateStepStatus(stepId, status)` — optimistic step update

---

### Feature 4: useSystem Store (F-ST-04)

- **Feature Name & ID:** useSystem — Global System State — F-ST-04
- **Specs & Requirements:**
  - State: `connectionState: String`, `jobProgress: Number`, `queueDepth: Number`, `activeAgentCount: Number`, `recentItems: Array`, `rateLimitInfo: Object`, `pageLoading: Boolean`, `hubLayouts: Object`
  - Actions: `setConnectionState(state)`, `updateJobProgress(progress)`, `updateQueueDepth(depth)`, `incrementAgentCount()`, `decrementAgentCount()`, `setRateLimit(info)`, `clearRateLimit()`, `addRecentItem(item)`, `setPageLoading(loading)`
  - Persistence: `recentItems` and `hubLayouts` persist to `localStorage`

- **UI/UX Specs:**
  - `connectionState` drives `NxConnectionDot` (A02)
  - `jobProgress` drives `NxJobRail` (A04)
  - `queueDepth` drives `NxQueuePill` (A03)
  - `activeAgentCount` drives `NxAgentBadge` (A05)
  - `rateLimitInfo` drives `NxRateLimitBanner` (A06)
  - `pageLoading` drives `NxTopBar` (L07)

- **Logic Workflow:**
  - `setConnectionState()`: update state, broadcast to all subscribers
  - `updateJobProgress()`: set `jobProgress` (0–100), auto-reset to 0 when complete
  - `addRecentItem()`: unshift to `recentItems`, slice to max 10, persist to localStorage

- **Technical Workflow:**
  - File: `resources/js/stores/useSystem.js`
  - State: multiple `ref()` and `reactive()` values
  - Computed: `isConnected`, `hasActiveJobs`, `showRateLimitBanner`
  - Echo: global listeners for `RateLimitHit`, `JobProgressUpdated`, `AgentExecuted`, `TokenStreamed`

- **Backend Readiness:**
  - Echo events: `RateLimitHit`, `JobProgressUpdated`, `AgentExecuted`, `TokenStreamed`
  - API: `GET /api/v1/tasks/stats`, `GET /api/v1/health`, `GET /api/v1/ai/providers/health`

- **Required Libraries:** `pinia`, `laravel-echo`, `axios`
- **Class/Component Names:** `useSystem.js`
- **Functions to Modify/Create:**
  - `setConnectionState(state)` — update connection state
  - `updateJobProgress(progress)` — set job progress
  - `updateQueueDepth(depth)` — set queue depth
  - `incrementAgentCount()` / `decrementAgentCount()` — adjust agent count
  - `setRateLimit(info)` / `clearRateLimit()` — rate limit banner state

---

### Feature 5: useNotificationStore (F-ST-05)

- **Feature Name & ID:** useNotificationStore — Toast & Notification State — F-ST-05
- **Specs & Requirements:**
  - State: `toasts: Array`, `unreadCount: Number`, `pendingUndo: Object | null`
  - Actions: `addToast(payload)`, `removeToast(id)`, `incrementUnread()`, `markAllRead()`, `setUndo(action)`, `clearUndo()`
  - Replaces `window.$toast` hack in `Toast.vue`

- **UI/UX Specs:**
  - `unreadCount` drives `NxNotificationBell` badge (A10)
  - `toasts` array drives `Toast.vue` rendering
  - `pendingUndo` drives undo button in toast with 8s countdown

- **Logic Workflow:**
  - `addToast()`: push to `toasts`, increment `unreadCount`, auto-remove after `duration` (default 5s)
  - `setUndo(action)`: set `pendingUndo` with `{ action, inverseAction, expiresAt: Date.now() + 8000 }`
  - `clearUndo()`: set `pendingUndo = null` after 8s or on undo click

- **Technical Workflow:**
  - File: `resources/js/stores/useNotificationStore.js`
  - State: `ref([])`, `ref(0)`, `ref(null)`
  - Computed: `hasUnread`, `activeToasts`
  - Echo: global listeners for `JobFailedEvent`, `WorkflowCompleted`, `ContactCreated`

- **Backend Readiness:**
  - Echo events: `JobFailedEvent`, `WorkflowCompleted`, `ContactCreated`
  - These events should push notifications via `addToast()` or `incrementUnread()`

- **Required Libraries:** `pinia`, `laravel-echo`
- **Class/Component Names:** `useNotificationStore.js`, `Toast.vue` (modified)
- **Functions to Modify/Create:**
  - `addToast(payload)` — add toast notification
  - `incrementUnread()` — increment unread count
  - `markAllRead()` — reset unread to 0
  - `setUndo(action)` — set pending undo action

---

### Feature 6: Echo Event Wiring — Global Listeners (F-ECHO-01)

- **Feature Name & ID:** Echo Event Wiring — Real-Time Subscriptions — F-ECHO-01
- **Specs & Requirements:**
  - Wire all 13 missing Echo events identified in `uiuv_v2.md` Section 4
  - Events: `TokenStreamed`, `AgentExecuted`, `WorkflowStepCompleted`, `MessageCompleted`, `MessageReceived`, `MessageSent`, `MemoriesExtracted`, `MemoryIndexed`, `MemoryVectorized`, `ContactCreated`, `JobFailedEvent`, `WorkflowStarted`, `WorkflowCompleted`

- **UI/UX Specs:**
  - Each event updates the relevant Pinia store state
  - UI updates reactively via Pinia state changes
  - No manual DOM manipulation required

- **Logic Workflow:**
  - `TokenStreamed` → `useChat().streamToken(e.token)`
  - `AgentExecuted` → `useSystem().incrementAgentCount()` / `decrementAgentCount()`
  - `WorkflowStepCompleted` → `useWorkflows().updateStepStatus(e.stepId, e.status)`
  - `MessageCompleted` → `useChat().finalizeMessage()`
  - `ContactCreated` → `useContacts().fetchContacts()` + `useNotificationStore().incrementUnread()`
  - `JobFailedEvent` → `useNotificationStore().addToast({ type: 'error', message: e.message })`

- **Technical Workflow:**
  - Each store's `onMounted` hook subscribes to relevant Echo channels
  - Each store's `onUnmounted` hook unsubscribes (Echo handles cleanup automatically on channel leave)
  - Global Echo listeners in `useSystem` for system-wide events

- **Backend Readiness:** All 13 Echo events must be broadcast from Laravel backend
- **Required Libraries:** `laravel-echo`, `pinia`
- **Class/Component Names:** All 5 store files
- **Functions to Modify/Create:**
  - Echo subscription setup in each store's `onMounted`
  - Event handler functions mapping events to store actions

---

### Feature 7: Optimistic UI Patterns (F-OPT-01)

- **Feature Name & ID:** Optimistic UI — Instant Feedback Pattern — F-OPT-01
- **Specs & Requirements:**
  - Implement optimistic UI for 5 mutation actions:
    1. Send message (`ChatInterface.vue`) — add user msg instantly
    2. Toggle setting (`SettingsView.vue`) — update instantly, revert on error
    3. Save workflow (`WorkflowBuilder.vue`) — update name/status instantly
    4. Publish workflow (`WorkflowBuilder.vue`) — flip status instantly
    5. Add contact (`ContactsView.vue`) — show card instantly with draft fallback

- **UI/UX Specs:**
  - Optimistic state shows `NxActionButton` in `optimisticState='success'` (green flash) or `optimisticState='error'` (red shake)
  - Rollback preserves user input in draft form
  - Toast notification on error with undo option

- **Logic Workflow:**
  - Pattern: `optimisticUpdate(() => apiCall(), rollbackAction)`
  - `optimisticUpdate` function: apply optimistic change → await API → on success commit → on error call rollback

- **Technical Workflow:**
  - Create `composables/useOptimistic.js` with `useOptimisticUpdate()` helper
  - Each mutation action uses the helper
  - `NxActionButton.vue` supports `optimistic` prop and `optimisticState` v-model

- **Backend Readiness:** API endpoints must return appropriate status codes (200 success, 4xx/5xx error)
- **Required Libraries:** `pinia`, `axios`
- **Class/Component Names:** `useOptimistic.js`, `NxActionButton.vue`
- **Functions to Modify/Create:**
  - `useOptimisticUpdate(optimisticFn, apiFn, rollbackFn)` — composable
  - `sendMessage()` in `useChat` — optimistic add
  - `addContact()` in `useContacts` — optimistic add
  - `saveWorkflow()` in `useWorkflows` — optimistic update
  - `publishWorkflow()` in `useWorkflows` — optimistic status flip

---

## 3. Testing Strategy

### Automated Testing

- **Unit Tests (Vitest):**
  - `useChat.spec.ts`: Test `sendMessage()` optimistic push; test `streamToken()` appends; test `revertLastMessage()` pops
  - `useContacts.spec.ts`: Test `addContact()` optimistic add; test `selectContact()` sets selected; test `searchContacts()` filters
  - `useWorkflows.spec.ts`: Test `updateStepStatus()` optimistic update; test `setExecutionProgress()`
  - `useSystem.spec.ts`: Test all state mutations; test `addRecentItem()` persists to localStorage
  - `useNotificationStore.spec.ts`: Test `addToast()` increments unread; test `setUndo()` sets pendingUndo; test auto-remove after duration
  - `useOptimistic.spec.ts`: Test optimistic update pattern; test rollback on API error

- **Echo Mocking:**
  - Mock `window.Echo` in tests
  - Simulate event broadcasts and verify store state updates

### Manual Testing Steps

1. **Store Inspection (Vue DevTools):**
   - Open Pinia tab → verify all 5 stores listed with correct state shape
   - Modify `useSystem().connectionState` → verify `NxConnectionDot` updates

2. **Echo Event Test:**
   - Trigger `TokenStreamed` from backend → verify `useChat().messages` updates in real-time
   - Trigger `AgentExecuted` → verify `useSystem().activeAgentCount` increments
   - Trigger `ContactCreated` → verify `useContacts().contacts` array updates

3. **Optimistic UI Test:**
   - Send a message → verify it appears instantly before API response
   - Simulate API error (disconnect network) → verify message reverts with error toast
   - Add contact → verify card appears instantly

4. **Persistence Test:**
   - Add recent item → refresh page → verify item persists from localStorage
   - Clear localStorage → verify recent items reset

5. **RTL Test:**
   - Set `dir="rtl"` → verify all store-driven UI mirrors correctly

EOF
