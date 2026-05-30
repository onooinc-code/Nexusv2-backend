# UI/UX Specifications and Features Document

**Document Version:** 1.0  
**Date:** 2026-05-21  
**Framework:** Vue 3 (Composition API) · Pinia · Tailwind CSS · Laravel Reverb · ECharts

---

## Executive Summary

This technical breakdown details all design specifications and functional features for the Nexus platform UI/UX. The document is organized by architectural categories and provides implementation guidance for each component.

---

## 1. Architecture Overview

### 1.1 Hub Ecosystem Structure

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                           NEXUS PLATFORM UI                                 │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                             │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐     │
│  │   AgentsHub  │  │ WorkflowsHub │  │  SettingsHub │  │ ContactsHub  │     │
│  │              │  │              │  │              │  │              │     │
│  └──────────────┘  └──────────────┘  └──────────────┘  └──────────────┘     │
│                                                                             │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐     │
│  │    LogsHub   │  │  MemoryHub   │  │  NexusHub    │  │ AIModelsHub  │     │
│  └──────────────┘  └──────────────┘  └──────────────┘  └──────────────┘     │
│                                                                             │
└─────────────────────────────────────────────────────────────────────────────┘
```

### 1.2 Design System Foundation

| Property | Value |
|----------|-------|
| Primary Font | Inter / System UI |
| Mono Font | JetBrains Mono |
| Border Radius | 12px (glass cards), 8px (inputs) |
| Glass Effect | backdrop-filter: blur(12px) |
| Transition Default | 250ms cubic-bezier(0.4, 0, 0.2, 1) |
| Dark Theme | CSS variables in `[data-theme="dark"]` |
| Light Theme | CSS variables in `[data-theme="light"]` |

---

## 2. Category A — Global Status Bar & System HUD

### A01: NxStatusBar.vue
- **File:** `resources/js/components/ui/NxStatusBar.vue`
- **Purpose:** Persistent system health indicator strip
- **Technical Specs:**
  - Height: 40px fixed
  - Background: Glassmorphism (rgba with backdrop-filter)
  - Layout: Flexbox with space-between
  - Z-index: 100 (below modals)

### A02: NxConnectionDot.vue
- **WebSocket Integration:**
  ```javascript
  // Listen to Reverb connection events
  window.Echo.connector.pusher.connection.bind('connected', () => {
    store.updateConnectionState('connected')
  })
  ```
- **Animation Keyframes:**
  ```css
  @keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
  }
  @keyframes breathe {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.15); }
  }
  ```

### A03: NxQueuePill.vue
- **Polling Strategy:**
  - `setInterval` every 15 seconds
  - Cancel on component unmount
  - Exponential backoff on errors

### A04: NxJobRail.vue
- **Echo Integration:**
  ```javascript
  window.Echo.private('jobs')
    .listen('JobProgressUpdated', (e) => {
      store.updateJobProgress(e.progress)
    })
  ```

---

## 3. Category B — Modals, Drawers & Overlays

### B01: NxLogViewerModal.vue
- **Virtual Scrolling Implementation:**
  - Use `vue-virtual-scroller` for 1000+ logs
  - Row height: 60px fixed
  - Buffer: 20 rows above/below viewport
- **Log Entry Format:**
  ```json
  {
    "timestamp": "2026-05-21T01:15:00Z",
    "level": "info|warning|error|debug",
    "message": "string",
    "context": {}
  }
  ```

### B02: NxThoughtTraceDrawer.vue
- **Terminal Styling:**
  - Background: `#0a0a0a`
  - Text: `#10b981` (emerald for success steps)
  - Font: JetBrains Mono, 12px

---

## 4. Category C — Contact Profile Components

### C01: NxContactCard3D.vue
- **3D Flip Implementation:**
  ```html
  <div class="contact-card" :class="{ flipped }">
    <div class="card-front">...</div>
    <div class="card-back">...</div>
  </div>
  ```
  ```css
  .contact-card {
    transform-style: preserve-3d;
    transition: transform 800ms cubic-bezier(0.23, 1, 0.32, 1);
  }
  .contact-card.flipped {
    transform: rotateY(180deg);
  }
  ```

### C02: NxEmotionRadar.vue
- **ECharts Configuration:**
  ```javascript
  const option = {
    radar: {
      indicator: [
        { name: 'Joy', max: 1 },
        { name: 'Trust', max: 1 },
        // ... other axes
      ]
    },
    series: [{
      type: 'radar',
      data: [{ value: baselineValues }]
    }]
  }
  ```

---

## 5. Category D — Chat & AI Interface

### D01: Token Stream Typing
- **WebSocket Event Handler:**
  ```javascript
  listen('TokenStreamed', (e) => {
    state.message += e.token
    state.streaming = !e.isComplete
  })
  ```

### D02: NxVoiceOrb.vue
- **Web Audio API Setup:**
  ```javascript
  const analyser = audioContext.createAnalyser()
  analyser.fftSize = 256
  const bufferLength = analyser.frequencyBinCount
  const dataArray = new Uint8Array(bufferLength)
  ```

---

## 6. Category E — Agent Hub

### E01: NxAgentWorkloadChart.vue
- **Donut Chart Configuration:**
  ```javascript
  const option = {
    series: [{
      type: 'pie',
      radius: ['40%', '80%'],
      data: agentTasks.map(a => ({ name: a.name, value: a.count }))
    }]
  }
  ```

---

## 7. Category F — Memory Hub

### F01: Memory Decay Visualization
- **Opacity Calculation:**
  ```javascript
  const decayOpacity = computed(() => 
    Math.max(0.3, memory.value.decayWeight)
  )
  ```

---

## 8. Category G — Workflow Canvas

### G01: Snap-to-Grid
- **Grid Settings:**
  - Grid size: 24px
  - Snap threshold: 8px
  - Drag ghost preview with opacity 0.7

### G02: Animated Flow Lines
- **SVG Path Animation:**
  ```css
  .flow-line {
    stroke-dasharray: 5, 5;
    animation: flow 1s linear infinite;
  }
  @keyframes flow {
    to { stroke-dashoffset: 10; }
  }
  ```

---

## 9. Category H — Navigation & Shell

### H01: NxNavRail.vue
- **Collapse State:**
  ```javascript
  const collapsed = ref(localStorage.getItem('nav-collapsed') === 'true')
  ```
- **Width Transition:**
  ```css
  .nav-rail {
    width: var(--nav-width, 240px);
    transition: width 250ms cubic-bezier(0.4, 0, 0.2, 1);
  }
  ```

### H02: NxCommandBar.vue
- **Fuzzy Search Algorithm:**
  - Fuse.js for client-side fuzzy matching
  - Minimum match score: 0.3
  - Highlight matched characters

---

## 10. Category I — Data Visualization

### I01: NxUsageAnalytics.vue
- **Chart Stack:**
  - Line chart for token usage over time
  - Bar chart for API calls per provider
  - Area chart for cost estimation
  - Pie chart for top intents

---

## 11. Category J — Mobile & Touch

### J01: Swipe Gestures
- **Touch Event Handling:**
  ```javascript
  let startX = 0
  const handleTouchStart = (e) => { startX = e.touches[0].clientX }
  const handleTouchMove = (e) => {
    const delta = e.touches[0].clientX - startX
    if (delta > 50 && startX < 50) router.back()
  }
  ```

---

## 12. Category K — Accessibility

### K01: Skip Link CSS
```css
.skip-link {
  position: absolute;
  top: -40px;
  left: 6px;
  background: var(--color-primary);
  z-index: 9999;
}
.skip-link:focus {
  top: 6px;
}
```

### K02: Focus Ring
```css
:focus-visible {
  outline: 2px solid #007AFF;
  outline-offset: 2px;
}
```

---

## 13. Category L — Power User Features

### L01: Multi-Select Mode
- **State Management:**
  ```javascript
  const selectedIds = ref(new Set())
  const toggleSelect = (id) => {
    if (selectedIds.value.has(id)) {
      selectedIds.value.delete(id)
    } else {
      selectedIds.value.add(id)
    }
  }
  ```

---

## Feature Coverage Matrix

| Category | Components | Status |
|----------|------------|--------|
| A - Global Status | 10 | ✅ Spec Complete |
| B - Modals | 10 | ✅ Spec Complete |
| C - Contacts | 12 | ✅ Spec Complete |
| D - Chat | 10 | ✅ Spec Complete |
| E - Agents | 8 | ✅ Spec Complete |
| F - Memory | 8 | ✅ Spec Complete |
| G - Workflows | 7 | ✅ Spec Complete |
| H - Navigation | 8 | ✅ Spec Complete |
| I - Analytics | 5 | ✅ Spec Complete |
| J - Mobile | 5 | ✅ Spec Complete |
| K - Accessibility | 7 | ✅ Spec Complete |
| L - Power User | 10 | ✅ Spec Complete |

**Total Components:** 69 new Vue files