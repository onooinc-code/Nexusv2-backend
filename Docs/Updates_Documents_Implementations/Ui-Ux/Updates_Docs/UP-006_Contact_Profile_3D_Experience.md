# 🚀 UPDATE BLUEPRINT: UP-006 — Contact Profile 3D Experience (Phase 6)

## 1. Meta & Pre-flight Analysis

- **Features & Details:**
  - Build 12 Contact Profile components (C01–C12) transforming contacts from data table to living intelligence interface
  - Components: `NxContactCard3D`, `NxEmotionRadar`, `NxRelationTimeline`, `NxEngagementRing`, `NxChannelStatus`, `NxMemoryMiniGraph`, `NxActivityHeatmap`, `NxConflictDiff`, `NxVersionHistory`, `NxTagCloud`, `NxPersonalityBars`, `NxPresenceDot`
  - Integrate into `ContactsView.vue` replacing simple card layout
  - All components must support RTL and 44×44px touch targets on mobile

- **Project Context & Versions:**
  - Vue 3 Composition API
  - ECharts via `vue-echarts` for radar, force graph, heatmap
  - Pinia `useContacts` store for contact data
  - Design tokens from UP-001, Nx components from UP-002

- **Regression Check:**
  - `ContactsView.vue` layout completely redesigned — ensure all existing contact CRUD operations still work
  - `ContactDetail.vue` may need updates to accommodate new profile components
  - API endpoints for timeline, activity heatmap, memory graph must exist or be stubbed

---

## 2. Feature Specifications (Per Feature)

### Feature 1: NxContactCard3D.vue — Virtual 3D Flip Card (F-CP-01)

- **Feature Name & ID:** NxContactCard3D — 3D Flip Card — F-CP-01
- **Specs & Requirements:**
  - CSS 3D perspective flip card
  - Front: avatar with gradient ring, canonical name, presence dot (C12), channel status (C05), key stats
  - Back: AI-generated relationship summary, emotional baseline snapshot, top 3 personality traits, quick actions
  - Props: `contact: Object`, `flipped: Boolean`
  - Animation: `rotateY(180deg)` with `transition: 800ms cubic-bezier(0.23, 1, 0.32, 1)`
  - Avatar ring: gradient rotation `360deg` over `4s` when contact "active today"
  - Hover (desktop): `rotateX(3deg) rotateY(-3deg)` tilt following mouse

- **UI/UX Specs:**
  - `perspective: 1000px` on container
  - `transform-style: preserve-3d` on card inner
  - `backface-visibility: hidden` on front/back faces
  - Gradient ring: `conic-gradient(from 0deg, #007AFF, #6366F1, #007AFF)`
  - Touch target: entire card is clickable, `min-height: 120px`

- **Logic Workflow:**
  - Click on card header icon → toggle `flipped` state
  - Mobile: tap anywhere on card → auto-flip
  - Mouse move on desktop → calculate tilt from cursor position relative to card center

- **Technical Workflow:**
  - File: `resources/js/Components/NxContactCard3D.vue` (new)
  - Props: `contact: Object`, `flipped: Boolean`
  - Emits: `flip`, `action` (for quick action buttons)
  - Computed: `isActiveToday`, `gradientRingStyle`, `tiltStyle`
  - Template: `<div class="card-container" @mousemove="handleTilt"><div class="card-inner" :class="{ flipped }"><div class="card-front">...</div><div class="card-back">...</div></div></div>`

- **Backend Readiness:** `GET /api/v1/contacts/{id}` returns `emotional_baseline`, `preferences`, `personality_traits`, `relationship_summary`
- **Required Libraries:** None (pure Vue + CSS)
- **Class/Component Names:** `NxContactCard3D.vue`
- **Functions to Modify/Create:**
  - `handleTilt(e)` — calculate rotateX/rotateY from mouse position
  - `toggleFlip()` — emit flip event

---

### Feature 2: NxEmotionRadar.vue — Emotional Baseline Radar (F-CP-02)

- **Feature Name & ID:** NxEmotionRadar — Emotional Baseline Radar — F-CP-02
- **Specs & Requirements:**
  - ECharts radar chart mapping `DB contacts.emotional_baseline` JSON to 6 axes
  - Axes: Joy, Trust, Anticipation, Surprise, Sadness, Anger
  - Props: `baseline: Object`, `history: Array` (for animated transitions)
  - Animation: polygon fills from center outward using `elasticOut` at `800ms`
  - Toggle between "Current" and "Historical Average"

- **UI/UX Specs:**
  - Size: `300×300px`
  - Fill color: `#6366F1` (AI-Core purple) with `0.6` opacity
  - Line color: `#6366F1` with `2px` width
  - Background: transparent
  - Axis labels: `font-size: 11px; color: rgba(255,255,255,0.6)`

- **Logic Workflow:**
  - On mount: animate from center (all values 0) to actual values
  - On data update: morph between shapes with `animationEasing: 'elasticOut'`
  - Toggle: switch between `baseline` and `history` average

- **Technical Workflow:**
  - File: `resources/js/Components/NxEmotionRadar.vue` (new)
  - Props: `baseline: Object`, `history: Array`
  - Emits: `axis-hover`
  - ECharts option: `radar` type with 6 axes, `areaStyle` for fill, `lineStyle` for border

- **Backend Readiness:** `DB contacts.emotional_baseline` JSON field; `GET /api/v1/contacts/{id}/emotional-history` for history
- **Required Libraries:** `vue-echarts`, `echarts`
- **Class/Component Names:** `NxEmotionRadar.vue`
- **Functions to Modify/Create:** None

---

### Feature 3: NxRelationTimeline.vue — Animated Timeline (F-CP-03)

- **Feature Name & ID:** NxRelationTimeline — Relationship Timeline — F-CP-03
- **Specs & Requirements:**
  - Vertical scrollable timeline of key relationship events
  - Events: first contact, memory milestones, sentiment shifts, workflow interactions
  - Props: `contactId: String`, `events: Array`
  - Animation: event cards fly in from `translateX(-20px)` alternating left/right; connecting line draws with `stroke-dashoffset`
  - Milestone events (first contact, anniversary): gold glow pulse
  - Mobile: horizontal scroll timeline instead of vertical

- **UI/UX Specs:**
  - Line: `2px solid rgba(255,255,255,0.1)`; draws from top to bottom
  - Event dot: `12px` circle on the line; milestone = `16px` with gold glow
  - Event card: `background: rgba(22,27,34,0.7); backdrop-filter: blur(12px); border: 1px solid rgba(255,255,255,0.1); border-radius: 8px`
  - Mobile: `flex-direction: row; overflow-x: auto; scroll-snap-type: x mandatory`

- **Logic Workflow:**
  - `onMounted`: trigger line draw animation
  - `IntersectionObserver`: trigger card fly-in when scrolled into view
  - Milestone detection: `event.type === 'first_contact' || event.type === 'anniversary'`

- **Technical Workflow:**
  - File: `resources/js/Components/NxRelationTimeline.vue` (new)
  - Props: `contactId: String`, `events: Array`
  - Emits: `event-click`
  - Template: `<div class="timeline"><svg class="timeline-line" /><template v-for="event in events"><div class="event" :class="{ milestone: event.isMilestone }"><div class="event-dot" /><div class="event-card">...</div></div></template></div>`

- **Backend Readiness:** `GET /api/v1/contacts/{id}/timeline`
- **Required Libraries:** None
- **Class/Component Names:** `NxRelationTimeline.vue`
- **Functions to Modify/Create:**
  - `fetchTimeline(contactId)` — load events
  - `handleEventClick(event)` — emit event

---

### Feature 4: NxEngagementRing.vue — Engagement Score Ring (F-CP-04)

- **Feature Name & ID:** NxEngagementRing — Engagement Score Ring — F-CP-04
- **Specs & Requirements:**
  - SVG ring meter (120×120px) showing engagement score (0–100)
  - Props: `score: Number`, `trend: 'up' | 'down' | 'stable'`
  - Animation: ring fills from 0 to `score` using `stroke-dashoffset` over `1200ms ease-out`
  - Center text: score counts up during fill using `requestAnimationFrame`
  - Color: `0–40` → Crimson; `40–70` → Amber; `70–100` → Emerald

- **UI/UX Specs:**
  - SVG `<circle>` with `r=54`, `cx=60`, `cy=60`, `stroke-width=8`
  - `stroke-dasharray: 339.29` (2πr); `stroke-dashoffset` computed from score
  - Background circle: `rgba(255,255,255,0.1)`; foreground: color per range
  - Center text: `font-size: 32px; font-weight: 700; font-family: 'Inter'`

- **Logic Workflow:**
  - `onMounted`: animate `stroke-dashoffset` from `339.29` (empty) to computed value
  - Animate center text from 0 to `score` using `requestAnimationFrame`

- **Technical Workflow:**
  - File: `resources/js/Components/NxEngagementRing.vue` (new)
  - Props: `score: Number`, `trend: String`
  - Emits: None
  - Computed: `ringColor`, `dashOffset`, `dashArray`

- **Backend Readiness:** `DB contacts.engagement_score` computed field
- **Required Libraries:** None
- **Class/Component Names:** `NxEngagementRing.vue`
- **Functions to Modify/Create:** None

---

### Feature 5: NxChannelStatus.vue — Channel Badges (F-CP-05)

- **Feature Name & ID:** NxChannelStatus — Communication Channel Badges — F-CP-05
- **Specs & Requirements:**
  - Row of channel indicator badges: WhatsApp, SMS, Email
  - Each badge: channel icon + colored status dot
  - Props: `channels: Array<{ type, status, lastMessageAt }>`
  - Click: opens PeopleConnect tab filtered to that channel
  - Animation: status dots use `NxConnectionDot`-style animations

- **UI/UX Specs:**
  - Badge: `display: flex; align-items: center; gap: 6px; padding: 4px 10px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 9999px`
  - Channel colors: WhatsApp → `#25D366`; SMS → `#007AFF`; Email → `#64748B`
  - Status dot: `width: 6px; height: 6px; border-radius: 50%`

- **Logic Workflow:**
  - Status mapping: `online` → emerald pulse; `offline` → grey static; `pending` → amber pulse
  - Click: `emit('channel-select', channel.type)` → parent navigates to PeopleConnect

- **Technical Workflow:**
  - File: `resources/js/Components/NxChannelStatus.vue` (new)
  - Props: `channels: Array`
  - Emits: `channel-select`
  - Icons: Lucide `MessageCircle` (WhatsApp), `Phone` (SMS), `Mail` (Email)

- **Backend Readiness:** `DB contacts.channels` JSON field
- **Required Libraries:** `lucide-vue-next`
- **Class/Component Names:** `NxChannelStatus.vue`
- **Functions to Modify/Create:** None

---

### Feature 6: NxMemoryMiniGraph.vue — Contact Memory Graph (F-CP-06)

- **Feature Name & ID:** NxMemoryMiniGraph — Contact Memory Force Graph — F-CP-06
- **Specs & Requirements:**
  - Compact (300×200px) ECharts force-directed graph
  - Nodes: memory nodes related to this contact, colored by type (episodic=blue, semantic=purple, structured=emerald)
  - Edges: relationships between memories
  - Props: `contactId: String`, `maxNodes: Number (default: 20)`
  - Click node: opens `NxTraceInspectorDrawer` or Memory Hub filtered to that memory

- **UI/UX Specs:**
  - ECharts `graph` type with `layout: 'force'`
  - Node size: `8px` (episodic), `10px` (semantic), `12px` (structured)
  - Edge: `1px solid rgba(255,255,255,0.2)`
  - Background: transparent

- **Logic Workflow:**
  - `onMounted`: fetch graph data, render with force simulation
  - Node click: emit `node-select` with memory ID

- **Technical Workflow:**
  - File: `resources/js/Components/NxMemoryMiniGraph.vue` (new)
  - Props: `contactId: String`, `maxNodes: Number`
  - Emits: `node-select`
  - API: `GET /api/v1/contacts/{id}/memories/graph`

- **Backend Readiness:** `GET /api/v1/contacts/{id}/memories/graph`
- **Required Libraries:** `vue-echarts`, `echarts`, `axios`
- **Class/Component Names:** `NxMemoryMiniGraph.vue`
- **Functions to Modify/Create:**
  - `fetchGraphData(contactId)` — load nodes/edges

---

### Feature 7: NxActivityHeatmap.vue — Interaction Heatmap (F-CP-07)

- **Feature Name & ID:** NxActivityHeatmap — Interaction Frequency Heatmap — F-CP-07
- **Specs & Requirements:**
  - GitHub contribution-style heatmap showing interaction frequency over past 52 weeks
  - Props: `contactId: String`, `data: Array<{ date, count }>`
  - Color: `0` → Surface-Mid; `1–3` → light blue; `4–7` → Nexus Blue; `8+` → bright blue
  - Animation: cells fade in column by column from left to right over `600ms`

- **UI/UX Specs:**
  - Grid: `display: grid; grid-template-columns: repeat(53, 1fr); gap: 2px`
  - Cell: `width: 10px; height: 10px; border-radius: 2px`
  - Tooltip on hover: shows date and interaction count

- **Logic Workflow:**
  - `onMounted`: trigger staggered fade-in animation
  - Cell hover: show tooltip with date/count

- **Technical Workflow:**
  - File: `resources/js/Components/NxActivityHeatmap.vue` (new)
  - Props: `contactId: String`, `data: Array`
  - Emits: `cell-click`
  - Computed: `cellColor` from count

- **Backend Readiness:** `GET /api/v1/contacts/{id}/activity/heatmap`
- **Required Libraries:** `axios`
- **Class/Component Names:** `NxActivityHeatmap.vue`
- **Functions to Modify/Create:** None

---

### Feature 8: NxConflictDiff.vue — Conflict Resolution (F-CP-08)

- **Feature Name & ID:** NxConflictDiff — Conflict Resolution Diff — F-CP-08
- **Specs & Requirements:**
  - When `DB contacts.conflict_with_id` is set, card glows Crimson
  - Clicking expands into split-pane diff: left = current value, right = conflicting value
  - Buttons: `[Keep This]` and `[Keep Other]`
  - Props: `conflictId: String`, `field: String`, `currentValue: any`, `conflictValue: any`
  - Animation: card border pulses crimson; on expand split-pane slides open; on resolution chosen value slides to center

- **UI/UX Specs:**
  - Glow: `box-shadow: 0 0 0 2px #EF4444` at `1.5s` interval
  - Split pane: `display: flex; height: 0 → auto` transition
  - Resolution: chosen value `translateX(0) opacity(1)`; other `translateX(20px) opacity(0)`

- **Logic Workflow:**
  - `onMounted`: start pulse animation if `conflictId` exists
  - `resolve(choice)`: call `POST /api/v1/contacts/{id}/resolve-conflict` → animate resolution

- **Technical Workflow:**
  - File: `resources/js/Components/NxConflictDiff.vue` (new)
  - Props: `conflictId`, `field`, `currentValue`, `conflictValue`
  - Emits: `resolve`
  - API: `POST /api/v1/contacts/{id}/resolve-conflict`

- **Backend Readiness:** `POST /api/v1/contacts/{id}/resolve-conflict`
- **Required Libraries:** `axios`
- **Class/Component Names:** `NxConflictDiff.vue`
- **Functions to Modify/Create:**
  - `resolveConflict(choice)` — call API, animate

---

### Feature 9: NxVersionHistory.vue — Belief Version History (F-CP-09)

- **Feature Name & ID:** NxVersionHistory — Belief Version History — F-CP-09
- **Specs & Requirements:**
  - Collapsible accordion showing version history of any belief/fact
  - Superseded entries: `text-decoration: line-through; opacity: 0.5`
  - Click entry: show full "Diff" in popover
  - Props: `fieldKey: String`, `versions: Array<{ value, updatedAt, source, supersededAt }>`
  - Trigger: `DB contacts.superseded_at` field is set

- **UI/UX Specs:**
  - Accordion: `max-height: 0 → 500px` transition
  - Struck-through text: red underline draws left-to-right on mount
  - Diff popover: `+` green, `-` red highlighting

- **Logic Workflow:**
  - Accordion toggle: animate `max-height`
  - Version click: show diff popover with inline `+`/`-` highlighting

- **Technical Workflow:**
  - File: `resources/js/Components/NxVersionHistory.vue` (new)
  - Props: `fieldKey`, `versions`
  - Emits: `version-restore`
  - Computed: `sortedVersions` (newest first)

- **Backend Readiness:** `DB contacts.superseded_at`; version history from audit log
- **Required Libraries:** None
- **Class/Component Names:** `NxVersionHistory.vue`
- **Functions to Modify/Create:** None

---

### Feature 10: NxTagCloud.vue — Animated Tag Chips (F-CP-10)

- **Feature Name & ID:** NxTagCloud — Animated Tag Chips — F-CP-10
- **Specs & Requirements:**
  - Contact preference and personality tags as glass pill chips
  - On load: each chip flies in with staggered delay (50ms per chip)
  - Props: `tags: Array<{ label, category, color }>`, `editable: Boolean`
  - Editable: click `+` to add tag (autocomplete); click `×` to remove with shrink animation
  - Categories: personality (purple), preference (blue), topic (emerald), flag (amber)

- **UI/UX Specs:**
  - Chip: `display: inline-flex; align-items: center; gap: 4px; padding: 4px 10px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 9999px; font-size: 12px`
  - Animation: `scale(0) opacity(0)` → `scale(1) opacity(1)` with spring bounce, staggered by `index × 50ms`

- **Logic Workflow:**
  - `onMounted`: trigger staggered fly-in animation
  - Add tag: show autocomplete input, on select add to `tags` array
  - Remove tag: shrink animation → remove from array → API call

- **Technical Workflow:**
  - File: `resources/js/Components/NxTagCloud.vue` (new)
  - Props: `tags: Array`, `editable: Boolean`
  - Emits: `tag-add`, `tag-remove`
  - Computed: `chipStyle` from category color

- **Backend Readiness:** `DB contacts.tags` JSON field; `PUT /api/v1/contacts/{id}/tags`
- **Required Libraries:** `axios`
- **Class/Component Names:** `NxTagCloud.vue`
- **Functions to Modify/Create:** None

---

### Feature 11: NxPersonalityBars.vue — Trait Strength Bars (F-CP-11)

- **Feature Name & ID:** NxPersonalityBars — Trait Strength Bars — F-CP-11
- **Specs & Requirements:**
  - Horizontal bars showing personality trait strengths
  - Props: `traits: Array<{ name, score, description }>`
  - Animation: width fills from 0 to score using `transition: width 800ms ease-out` with `100ms` stagger per bar
  - Hover: bar highlights with glow; tooltip shows description
  - Color: gradient from AI-Core purple to Action-Primary blue

- **UI/UX Specs:**
  - Bar container: `height: 8px; background: rgba(255,255,255,0.1); border-radius: 4px; overflow: hidden`
  - Bar fill: `height: 100%; background: linear-gradient(90deg, #6366F1, #007AFF); border-radius: 4px`
  - Hover glow: `box-shadow: 0 0 8px rgba(99, 102, 241, 0.4)`

- **Logic Workflow:**
  - `onMounted`: trigger staggered width fill animation
  - Hover: show tooltip with trait description

- **Technical Workflow:**
  - File: `resources/js/Components/NxPersonalityBars.vue` (new)
  - Props: `traits: Array`
  - Emits: None
  - Computed: `barWidth` from `trait.score`

- **Backend Readiness:** `DB contacts.personality_traits` JSON field
- **Required Libraries:** None
- **Class/Component Names:** `NxPersonalityBars.vue`
- **Functions to Modify/Create:** None

---

### Feature 12: NxPresenceDot.vue — Last-Active Indicator (F-CP-12)

- **Feature Name & ID:** NxPresenceDot — Last-Active Presence — F-CP-12
- **Specs & Requirements:**
  - Color-coded dot showing when contact was last active
  - Props: `lastSeenAt: Date`
  - Color: `today` → emerald pulse; `this week` → amber; `this month` → slate; `older` → grey static
  - Animation: emerald state has breathing pulse `scale(1.0)→scale(1.4)` at `2s`
  - Tooltip: "Last active: 2 hours ago"

- **UI/UX Specs:**
  - Dot: `width: 8px; height: 8px; border-radius: 50%`
  - Emerald pulse: `@keyframes pulse-emerald { 0%, 100% { transform: scale(1); opacity: 1; } 50% { transform: scale(1.4); opacity: 0.7; } }` at `2s`

- **Logic Workflow:**
  - Computed: `timeAgo` from `lastSeenAt`; `color` from time range
  - Tooltip: formatted relative time

- **Technical Workflow:**
  - File: `resources/js/Components/NxPresenceDot.vue` (new)
  - Props: `lastSeenAt: Date`
  - Emits: None
  - Computed: `presenceColor`, `timeAgo`, `pulseClass`

- **Backend Readiness:** `DB contacts.last_seen_at` timestamp
- **Required Libraries:** None
- **Class/Component Names:** `NxPresenceDot.vue`
- **Functions to Modify/Create:** None

---

## 3. Testing Strategy

### Automated Testing

- **Unit Tests (Vitest):**
  - `NxContactCard3D.spec.ts`: Test flip animation; test tilt calculation; test gradient ring rotation
  - `NxEmotionRadar.spec.ts`: Test radar data mapping; test color threshold; test history toggle
  - `NxRelationTimeline.spec.ts`: Test event rendering; test milestone glow; test line draw animation
  - `NxEngagementRing.spec.ts`: Test ring fill calculation; test color threshold; test count-up animation
  - `NxChannelStatus.spec.ts`: Test channel badge rendering; test status dot color
  - `NxMemoryMiniGraph.spec.ts`: Test node color by type; test click emits node-select
  - `NxActivityHeatmap.spec.ts`: Test cell color from count; test 52-week grid
  - `NxConflictDiff.spec.ts`: Test crimson glow when conflict exists; test resolution animation
  - `NxVersionHistory.spec.ts`: Test accordion expand; test strikethrough on superseded
  - `NxTagCloud.spec.ts`: Test staggered fly-in; test add/remove tag animations
  - `NxPersonalityBars.spec.ts`: Test width fill animation; test stagger timing
  - `NxPresenceDot.spec.ts`: Test color from time range; test pulse animation for "today"

### Manual Testing Steps

1. **Contact Card 3D:**
   - Hover over card → verify tilt follows mouse
   - Click card → verify flip animation (800ms)
   - Verify avatar ring gradient rotates when contact active today

2. **Emotion Radar:**
   - Verify 6 axes render with correct labels
   - Toggle "Historical Average" → verify polygon morphs
   - Hover axis → verify tooltip shows raw score

3. **Relation Timeline:**
   - Scroll timeline → verify cards fly in with stagger
   - Verify connecting line draws from top to bottom
   - Verify first contact event has gold glow

4. **Engagement Ring:**
   - Verify ring fills from 0 to score over 1200ms
   - Verify center text counts up during fill
   - Test score 30 (Crimson), 60 (Amber), 90 (Emerald)

5. **Channel Status:**
   - Verify WhatsApp/SMS/Email badges render with correct icons
   - Click badge → verify PeopleConnect tab opens filtered

6. **Memory Mini Graph:**
   - Verify nodes spawn from center with physics
   - Hover node → verify memory snippet tooltip
   - Click node → verify trace inspector opens

7. **Activity Heatmap:**
   - Verify 52-week grid renders (53 columns)
   - Verify color intensity matches interaction count
   - Hover cell → verify date/count tooltip

8. **Conflict Diff:**
   - For contact with `conflict_with_id` → verify crimson glow pulse
   - Click card → verify split-pane expands
   - Click "Keep This" → verify resolution animation

9. **RTL Test:**
   - Set `dir="rtl"` → verify all components mirror correctly
   - Verify timeline scroll direction flips

10. **Mobile Test (375px):**
    - Verify timeline switches to horizontal scroll
    - Verify all touch targets `≥ 44×44px`
    - Verify card flip works on tap
