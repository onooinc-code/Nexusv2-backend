# 💎 Nexus UI/UX - Final Master Specification Report

**Document Status:** FINAL / SOURCE OF TRUTH
**Role:** Principal Frontend Architect & HCI Specialist
**Architecture:** Vue 3 (Composition API) + Pinia + Tailwind CSS + Laravel Reverb

---

## 1. 🎨 The Atomic Design System (Foundational Resources)

### Design Language: Glassmorphism 2.0
The visual language reflects the "Digital Mirror" philosophy—sophisticated, fast, and layered. We avoid flat, opaque surfaces in favor of depth and context.
*   **Blur:** `backdrop-filter: blur(12px)`
*   **Border:** `1px solid rgba(255, 255, 255, 0.1)` (Creates the "frosted edge" light-catch effect).
*   **Background Opacity:** `background-color: rgba(22, 27, 34, 0.7)`

### Color Palette (Semantic & Functional)
Tailwind CSS theme extensions mapped directly to database/system states:
*   `Surface-High`: **#0B0E14** (Deep Space) - Global body background.
*   `Surface-Mid`: **#161B22** (Midnight Glass) - Primary card and modal backgrounds.
*   `Action-Primary`: **#007AFF** (Nexus Blue) - Primary CTA, active navigational states, user chat bubbles.
*   `AI-Core`: **#6366F1** (Hédra Purple) - AI agent activity, system insights, thinking pulses.
*   `Status-Success`: **#10B981** (Emerald) - Task `completed`, agent `idle`, websocket connected, confidence >0.8.
*   `Status-Warning`: **#F59E0B** (Amber) - Task `queued`, rate limits approaching, confidence 0.6-0.8.
*   `Status-Error`: **#EF4444** (Crimson) - Task `failed`, agent `dead`/`failed`, network disconnects.

### Typography Hierarchy
*   **System UI & Prose:** `Inter` (Sans-serif). Tracking set to `-0.02em` for H1/H2. Line-height `1.6` for readability.
*   **AI Data, Metadata & Logs:** `JetBrains Mono`. Used strictly for `trace_id`, JSON payloads, confidence scores, and code blocks. Enable `font-variant-numeric: tabular-nums`.

---

## 2. 🧱 Global Shared Components (Atomic Layer)

### `NxAiPulse.vue` (The State Orb)
*   **Purpose:** Visual heartbeat of the AI.
*   **Props:** `state` (Enum: `idle` | `thinking` | `speaking` | `error`).
*   **Animations:**
    *   `idle`: Slow breathing scale (`1.0` to `1.05`), `opacity: 0.4` to `0.7` (4s loop).
    *   `thinking`: Rapid conic-gradient rotation (1s linear loop).
    *   `speaking`: Sound-reactive/randomized scale based on streaming token chunks.
    *   `error`: Crimson jitter/shake (`translateX(-2px to 2px)` over 100ms).

### `NxGlassCard.vue`
*   **Purpose:** The standard container for profiles, memories, and settings.
*   **Props:** `elevation` (1, 2, 3 mapped to shadow spread), `hoverable` (Boolean - applies `translate-y-[-2px]` on hover).
*   **Slots:** `#header` (sticky top), `#body` (scrollable), `#footer` (actions).

### `NxTokenMeter.vue`
*   **Purpose:** Real-time Context Window visualization mapping to `meta.tokens_used`.
*   **Props:** `currentTokens` (Int), `maxTokens` (Int - default 6000).
*   **Visuals:** Horizontal SVG progress bar.
    *   `< 70%`: Nexus Blue.
    *   `70% - 90%`: Amber (Warns user of approaching limit).
    *   `> 90%`: Crimson (Triggers "Trim Context" suggestion UI).

### `NxLiveLoader.vue`
*   **Purpose:** Replacing generic spinners for long-running background jobs (`async` mode tasks).
*   **Props:** `taskId` (UUID), `status` (String).
*   **Visuals:** A pulsing glass pill. When expanded, displays a tiny terminal-style log feed (`task_checkpoints` streamed via Reverb).

### `NxActionButton.vue`
*   **Purpose:** Standardized interaction button.
*   **Props:** `variant` (primary, secondary, danger, ghost), `loading` (Boolean), `disabled` (Boolean), `optimistic` (Boolean).
*   **Feedback:** If `optimistic=true`, instantly enters a pseudo-success state while awaiting backend ACK, rolling back to `error` if the promise rejects.

---

## 3. 🗺️ Global Navigation & Layout Architecture

### Desktop (3-Pane Architecture, `> 1024px`)
1.  **Navigation Rail (Left):** Collapsible (`80px` min, `240px` max). Contains top-level Hub icons. Bottom anchors user profile and global settings.
2.  **Hub Sidebar (Middle-Left, `320px`):** The Entity List (e.g., active sessions, contact list, agent list). Includes sticky search and sort filters.
3.  **Workspace (Right, Flexible):** Main interaction area. Topped with a Glassmorphism header featuring Breadcrumbs (`Hub / Entity`) and Action Icons.

### Mobile (Stack-and-Slide, `< 768px`)
*   **Navigation:** Desktop Rail vanishes. Replaced by a `64px` high, heavily blurred **Bottom Tab Bar** (Home, Memory, Contacts, Tasks, Search).
*   **Flow:** Clicking an entity in a list slides the Detail Workspace over the screen from the right (100vw). A persistent `< Back` chevron appears in the top header.
*   **The Action Orb:** Floating Hédra orb above the tab bar for one-tap voice dictation.

### Universal Command Bar (Cmd+K)
*   **Trigger:** `Cmd+K` (Mac) or `Ctrl+K` (Win), or tapping the magnifying glass.
*   **UX:** A frosted glass overlay centers on the screen. Fuzzy-searches across `contacts.canonical_name`, `memories.snippet`, `agents.name`, and system routes. Results are keyboard-navigable.

---

## 4. 🖥️ Detailed View Specifications (The Hubs)

### 4.1 Nexus Hub (The Dashboard)
*   **HedraSouly Tab:**
    *   *UX:* Central, private 1-on-1 AI chat. `NxAiBubble` renders Markdown and code. Uses `useScroll.js` for sticky-bottom auto-scrolling during `MessageStreamed` websocket events.
    *   *Quick Actions:* A horizontal scrollable list of chips above the composer (e.g., "Summarize day", "Consolidate memories").
*   **PeopleConnect Tab:**
    *   *UX:* Dual-pane layout. Contact threads on the left; multi-channel chat (WhatsApp/SMS) on the right.
    *   *Features:* Channel indicators (WhatsApp icon vs SMS icon). DB `messages.status` mapped to checkmarks (1 tick = sent, 2 ticks = delivered/read). Composer includes a DateTime picker to trigger `SchedulerHub` delayed sends.

### 4.2 Agents Hub (Specialized Views)
*   **Agent Registry:**
    *   *UX:* Grid of `NxGlassCard`s mapping to `agents` table. Displays `name`, persona summary, `version`, and an `NxStatusPill` (`idle`, `running`, `failed`).
*   **Thought-Trace Workspace:**
    *   *UX:* Split pane. Left side shows configuration. Right side is a "Glass Terminal" utilizing JetBrains Mono. Maps to `AgentRuntimeLog`. As `agent.step.completed` events arrive, new lines append sequentially, providing transparency into the AI's reasoning loop.

### 4.3 Memory Hub (The Knowledge Base)
*   **The Memory Timeline:**
    *   *UX:* Vertical timeline feed mapping to `memories` table where `type = episodic`.
    *   *Data:* Fades text opacity based on `decay_weight`.
*   **Fact Explorer:**
    *   *UX:* Categorized grid for `Semantic` and `Structured` memories.
    *   *Data mapping:* DB `confidence` column maps to `NxConfidenceBadge` (Color-coded).
*   **Consolidation Map:**
    *   *UX:* Force-directed graph view (via D3.js or ECharts). Visualizes how scattered facts (`source_event_id`) merge into semantic insights (`memory_consolidations`). Mobile transforms this into a drill-down accordion list.

### 4.4 Contacts Hub (Intelligence Profiles)
*   **360-Profile View:**
    *   *Data Mapping:* DB `emotional_baseline` JSON renders as an ECharts sparkline. DB `preferences` array renders as tag chips.
    *   *Versioning:* DB `superseded_at` triggers an `NxVersionHistory` component—old beliefs are struck through with a "Diff" viewer available on click.
    *   *Conflict Resolution:* DB `conflict_with_id` makes the specific data card glow Red, presenting [Keep This] or [Keep Other] buttons.
*   **Rule Editor:**
    *   *UX:* Interface mapping to `contact_rules` table. Draggable list to set "Hierarchical Information Priority" (Fixed rules vs. learned memories).

### 4.5 Workflows Hub (Orchestration)
*   **Workflow Builder:**
    *   *UX:* Desktop uses a drag-and-drop node canvas. Mobile falls back to a linear "Step Sequencer" list.
*   **Task Monitor:**
    *   *UX:* High-density DataGrid of `tasks` table.
    *   *Data Mapping:* Status enum (`queued`, `running`, `completed`, `failed`) dictates row highlight color. `trace_id` is clickable, launching a sliding drawer to view raw JSON payloads from `LogsHub`.

### 4.6 Settings & Logs Hub
*   **Provider Manager:**
    *   *UX:* Dynamic forms for `AiModelsHub`. API keys use password-type `NxInput`. Includes an asynchronous "Test Connection" button that pings the provider health endpoint.
*   **Intent Routing Matrix:**
    *   *UX (Desktop):* A 2D "Neural Grid". Rows = Intents (e.g., "Summarization"), Columns = Cost Profiles (Fast, Quality, Budget). Cells contain dropdowns of available LLMs.
    *   *UX (Mobile):* Transforms into a vertically grouped accordion list for touch-friendly configuration.

---

## 5. 🌊 Motion & Interaction Design

*   **Transitions:**
    *   *Page-Slide:* Route changes use a `300ms cubic-bezier(0.4, 0, 0.2, 1)` sliding fade (entering components slide up 12px).
    *   *Modal-Fade:* Overlays and dialogs use a rapid `200ms` fade-in.
    *   *List Reordering:* Uses FLIP animations (via `@vueuse/motion`) so tasks or consolidated memories glide to their new positions smoothly.
*   **Optimistic UI:**
    *   Sending a message or toggling a boolean setting updates the DOM instantly. If the backend fails (e.g., 500 error), the UI reverts with an `NxToast` error, preserving the user's intended input in draft form.
*   **Haptic Feedback (Mobile-Only):**
    *   Utilizes `navigator.vibrate()`.
    *   *Success:* Light double-tap (`[15, 50, 15]`) on task completion.
    *   *Error:* Heavy buzz (`[50, 100, 50]`) for rate limits or failed provider calls.

---

## 6. 🚀 Resource & Tech Stack Checklist

The following stack must be instantiated for Phase 6 execution:

*   **Frontend Framework:** Vue 3 (Strictly Composition API with `<script setup>`).
*   **State Management:** Pinia (Modular stores: `useChat`, `useContacts`, `useWorkflows`, `useSystem`).
*   **Build Tool:** Vite (for HMR and optimized asset bundling).
*   **Styling & UI Primitives:**
    *   Tailwind CSS (Extended with exact Hex codes from Section 1).
    *   Headless UI (Vue) for accessible modals, dropdowns, and comboboxes.
*   **Real-Time & WebSockets:**
    *   Laravel Echo + `pusher-js` configured strictly for **Laravel Reverb**.
*   **Iconography:** Lucide-Vue-Next (Configured globally to 2px stroke width).
*   **Data Visualization:** ECharts (via `vue-echarts`) for emotional baselines, usage analytics, and consolidation graphs.
*   **AI Prose Rendering:** `markdown-it` combined with `highlight.js` for rendering LLM outputs, tables, and code blocks safely.
