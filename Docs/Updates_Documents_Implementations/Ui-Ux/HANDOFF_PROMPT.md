# 🤖 AI Agent Handoff Prompt — Nexus UI/UX Implementation

## 1. Project Overview

You are continuing a comprehensive UI/UX compliance implementation for the **Nexus** application — a Vue 3 + Laravel 11 platform with real-time WebSocket features.

### Core Stack

- **Frontend:** Vue 3 (Composition API, `<script setup>`), Pinia, Tailwind CSS v3+, Vite
- **Backend:** Laravel 11 with Reverb WebSocket server
- **Icons:** Lucide Vue Next
- **Charts:** ECharts
- **Markdown:** markdown-it + highlight.js

### Design System: Glassmorphism 2.0

- Background: `rgba(22, 27, 34, 0.7)` with `backdrop-filter: blur(12px)`
- Border: `1px solid rgba(255, 255, 255, 0.1)`
- Color tokens: Nexus Blue `#007AFF`, Emerald `#10B981`, Crimson `#EF4444`, AI-Core Purple `#6366F1`
- Typography: Inter (body), JetBrains Mono (code), body line-height 1.6, H1/H2 tracking -0.02em

---

## 2. Documentation Structure

### Primary Specs (Read These First)

```
Docs/
├── uiuv_v2.md                    # Original 60 violations + 100 new features = 160 total backlog
├── Final Master Specification Report.md  # Design system authority (colors, glass, typography)
└── NEW_FEATURES_SPEC.md          # 100 new features organized by category
```

### Update Blueprints (10 Phases)

```
_AI_Workflow/Updates_Docs/
├── Finished_UP-001_Design_System_Foundation.md  ✅ COMPLETE
├── UP-002_Core_Nx_Components.md                 ✅ COMPLETE
├── UP-003_Pinia_Stores_RealTime_State.md         ✅ COMPLETE
├── UP-004_Layout_Navigation_Overhaul.md          ⬜ NEXT
├── UP-005_View_Fixes_Optimistic_UI.md            ⬜ PENDING
├── UP-006_Contact_Profile_3D_Experience.md       ⬜ PENDING
├── UP-007_Advanced_Features_Modals_Charts.md     ⬜ PENDING
├── UP-008_Mobile_Touch_Enhancements.md           ⬜ PENDING
├── UP-009_Accessibility_Polish.md                ⬜ PENDING
└── UP-010_Final_Polish_Deployment.md             ⬜ PENDING
```

### Task Documents

```
_AI_Workflow/Tasks_Docs/
├── TASK_INDEX.md                    # Master index with dependency graph
├── Finished_UP-001_Task-*.md        # 22 completed tasks
├── Finished_UP-002_Task-*.md        # 4 completed tasks
├── Finished_UP-003_Task-*.md        # 3 completed tasks
└── UP-004_Task-*.md                 # Not yet created (will be created by you)
```

---

## 3. What Has Been Completed

### Phase 1: Design System Foundation (UP-001) — 22/22 ✅

**Files Modified:**

- `package.json` — Added 8 dependencies (pinia, laravel-echo, lucide-vue-next, echarts, markdown-it, highlight.js)
- `resources/css/app.css` — Fixed 13 color tokens, glass backgrounds, typography
- `tailwind.config.js` — Extended theme with Inter/JetBrains Mono, color tokens, tracking-tight
- `resources/js/app.js` — Pinia initialization
- `resources/js/bootstrap.js` — Echo + Reverb initialization
- `resources/js/App.vue` — NxStatusBar mounted

**Components Created (11):**

- `NxStatusBar.vue` — 40px status bar with 3 zones
- `NxConnectionDot.vue` — WebSocket connection indicator
- `NxQueuePill.vue` — Queue depth counter
- `NxJobRail.vue` — Background job progress bar
- `NxAgentBadge.vue` — Active agent count with pulse
- `NxAiPulse.vue` — AI state orb (4 animations)
- `NxRateLimitBanner.vue` — Rate limit warning banner
- `NxTokenBudget.vue` — Daily token usage ring
- `NxMemoryPressure.vue` — Redis memory usage pill
- `NxProviderDots.vue` — Provider health indicators
- `NxNotificationBell.vue` — Global notification bell

**Stores Created (2):**

- `resources/js/stores/useSystem.js` — Global system state
- `resources/js/stores/useNotificationStore.js` — Notification/toast store

### Phase 2: Core Nx Components (UP-002) — 4/4 ✅

**Components Created:**

- `NxGlassCard.vue` — Glass container with elevation 1-3, hoverable, sticky header/footer
- `NxTokenMeter.vue` — SVG context window bar with Blue/Amber/Crimson thresholds (70%/90%)
- `NxLiveLoader.vue` — Pulsing pill with expandable terminal log feed + Echo subscription
- `NxActionButton.vue` — 4 variants, optimistic states, 44px touch target

### Phase 3: Pinia Stores (UP-003) — 3/3 ✅

**Stores Created:**

- `useChat.js` — Chat state with messages, streaming, draft, session management
- `useContacts.js` — Contacts with search, favorites, optimistic add/update/delete
- `useWorkflows.js` — Workflows with steps, execution progress, selection

---

## 4. Current State

### Build Status

- ✅ `npm run build` passes (866KB app chunk — code splitting recommended in future)
- ✅ Dev server runs at https://os.square-ltd.com/

### Task Execution Pattern

The established workflow is:

1. Read the task document from `_AI_Workflow/Tasks_Docs/`
2. Implement the feature exactly as specified
3. Run `npm run build` to verify
4. Rename task file from `UP-XXX_Task-YY_*.md` to `Finished_UP-XXX_Task-YY_*.md`
5. Update `TASK_INDEX.md` status
6. Provide git commit message
7. Ask for permission to continue

---

## 5. What Remains (Next Steps)

### Immediate Next: UP-004 — Layout & Navigation Overhaul

**Tasks to create and execute:**

1. `NxNavRail.vue` — Left navigation rail with icon-only collapsed state
2. `HubSidebar.vue` — Context-aware sidebar for hub views
3. `NxContextBar.vue` — Top context bar with breadcrumbs + NxTokenMeter
4. `NxWorkspacePane.vue` — Main content area with 3-pane layout
5. `NxMobileNav.vue` — Bottom tab bar for mobile
6. `NxRTLSupport.vue` — RTL layout utilities
7. `NxSkipLink.vue` — Accessibility skip link

### Subsequent Phases

- **UP-005:** View Fixes & Optimistic UI (ChatView, ContactsView, WorkflowsView fixes)
- **UP-006:** Contact Profile 3D Experience (3D avatar, memory spheres)
- **UP-007:** Advanced Features (Modals, Charts, Command Palette)
- **UP-008:** Mobile & Touch Enhancements
- **UP-009:** Accessibility Polish
- **UP-010:** Final Polish & Deployment

---

## 6. Key Architectural Decisions

### State Management

- All Pinia stores in `resources/js/stores/`
- Stores use Options API style (not setup stores) for consistency
- Optimistic updates pattern: update state → API call → revert on error

### Component Patterns

- All components use `<script setup>` syntax
- Props with validators where specified
- Scoped styles with glassmorphism base classes
- 44×44px minimum touch targets for interactive elements
- RTL support via `dir="rtl"` attribute on root elements

### Real-time (Echo + Reverb)

- `window.Echo` initialized globally in `bootstrap.js`
- Private channels: `window.Echo.private('tasks.{id}')`
- Events: `TaskCheckpoint`, `MemoryUpdated`, `AgentHeartbeat`

### Color Token Usage

| Token          | Hex       | Usage                                 |
| -------------- | --------- | ------------------------------------- |
| Nexus Blue     | `#007AFF` | Primary actions, links, active states |
| Emerald        | `#10B981` | Success, online, healthy              |
| Crimson        | `#EF4444` | Error, danger, critical               |
| AI-Core Purple | `#6366F1` | AI features, gradients                |

---

## 7. How to Continue

### Step 1: Read UP-004 Blueprint

```bash
# Read the blueprint
cat _AI_Workflow/Updates_Docs/UP-004_Layout_Navigation_Overhaul.md
```

### Step 2: Create Task Documents

Break UP-004 into individual task files in `_AI_Workflow/Tasks_Docs/` following the existing pattern:

- `UP-004_Task-01_NxNavRail.md`
- `UP-004_Task-02_HubSidebar.md`
- etc.

### Step 3: Execute Tasks Sequentially

Follow the established pattern:

1. Read task document
2. Implement feature
3. Build verify
4. Rename to `Finished_*`
5. Update TASK_INDEX.md
6. Provide commit message

### Step 4: Update TASK_INDEX.md

Keep the status table current. Mark completed phases as 🟢, in-progress as 🟡, pending as 🔴.

---

## 8. Important Notes

### Build Warnings

- Current app chunk is 866KB (gzipped 185KB). Consider code splitting in future phases.
- Use `npm run build` to verify — dev server may have hot reload issues with new files.

### File Naming

- Components: `NxPascalCase.vue` in `resources/js/Components/`
- Stores: `camelCase.js` in `resources/js/stores/`
- Task docs: `UP-XXX_Task-YY_Description.md`

### Git Workflow

- Commit after each phase completion (not every task)
- Use conventional commits: `feat(phase-X): description`
- Reference blueprint and task numbers in commit body

---

## 9. Quick Reference: Current File Structure

```
resources/js/
├── app.js                    # Pinia + Lucide init
├── bootstrap.js              # Echo + Reverb init
├── App.vue                   # Root component with NxStatusBar
├── Components/
│   ├── NxStatusBar.vue       ✅
│   ├── NxConnectionDot.vue   ✅
│   ├── NxQueuePill.vue       ✅
│   ├── NxJobRail.vue         ✅
│   ├── NxAgentBadge.vue      ✅
│   ├── NxAiPulse.vue         ✅
│   ├── NxRateLimitBanner.vue ✅
│   ├── NxTokenBudget.vue     ✅
│   ├── NxMemoryPressure.vue  ✅
│   ├── NxProviderDots.vue    ✅
│   ├── NxNotificationBell.vue ✅
│   ├── NxGlassCard.vue       ✅
│   ├── NxTokenMeter.vue      ✅
│   ├── NxLiveLoader.vue      ✅
│   └── NxActionButton.vue    ✅
└── stores/
    ├── useSystem.js          ✅
    ├── useNotificationStore.js ✅
    ├── useChat.js            ✅
    ├── useContacts.js        ✅
    └── useWorkflows.js       ✅
```

---

## 10. Your Starting Point

**Last completed task:** UP-003_Task-03_useWorkflows_Store.md (renamed to `Finished_UP-003_Task-03_useWorkflows_Store.md`)

**Next task to create:** UP-004_Task-01_NxNavRail.md

**Blueprint to read:** `_AI_Workflow/Updates_Docs/UP-004_Layout_Navigation_Overhaul.md`

**TASK_INDEX.md status:** UP-002 🟢 4/4 Complete, UP-003 🟢 3/3 Complete

---
