# Nexus UI – Next.js Specification

**Document Version:** 1.0  
**Date:** 2026-05-21  
**Platform:** Next.js 14+ (App Router)  
**Technology Stack:** React 18+, TypeScript, Tailwind CSS, Headless UI, ECharts, Lucide React

---

## Executive Summary

This document provides a comprehensive feature list and granular requirements checklist for the Nexus UI migration to Next.js. The specification consolidates all UI/UX requirements from source documentation into an actionable Next.js implementation plan.

---

## 1. Global Status Bar & System HUD

### 1.1 NxStatusBar Component
- **Priority:** P1
- **File:** `components/ui/NxStatusBar.tsx`
- **Requirements:**
  - [ ] 40px tall frosted-glass horizontal bar
  - [ ] Three-zone layout (left, center, right)
  - [ ] Slides down from translateY(-100%) to translateY(0) in 200ms ease-out
  - [ ] Mobile: Collapses to icon-only strip at < 768px
  - [ ] Mobile: Tap opens slide-up panel with full status detail
  - [ ] Integrates with useSystem() Pinia store equivalent (Zustand/Jotai)

### 1.2 NxConnectionDot Component
- **Priority:** P1
- **File:** `components/ui/NxConnectionDot.tsx`
- **Requirements:**
  - [ ] 10px circle indicator
  - [ ] States: connecting, connected, disconnected, error
  - [ ] Connecting: amber, slow pulse opacity 1.5s
  - [ ] Connected: emerald #10B981, breathing scale 1.0→1.15 at 3s
  - [ ] Disconnected: crimson #EF4444, static
  - [ ] Error: crimson rapid jitter translateX(-1px,1px) at 100ms
  - [ ] WebSocket integration via Laravel Reverb
  - [ ] Hover tooltip with connection status

### 1.3 NxQueuePill Component
- **Priority:** P2
- **File:** `components/ui/NxQueuePill.tsx`
- **Requirements:**
  - [ ] Clickable glass pill showing queue depth
  - [ ] Opens NxQueueModal on click
  - [ ] Bounce animation (scale 1.1) when count increases
  - [ ] Color logic: grey (0), blue (>0), crimson (has failures)
  - [ ] Polls GET /api/v1/tasks/stats every 15s

### 1.4 NxJobRail Component
- **Priority:** P1
- **File:** `components/ui/NxJobRail.tsx`
- **Requirements:**
  - [ ] 2px tall progress bar spanning full width
  - [ ] Nexus Blue #007AFF with glowing right edge
  - [ ] Smooth width transition 300ms ease
  - [ ] Fades to 100% then out over 400ms on completion
  - [ ] Listens to JobProgressUpdated event

### 1.5 NxAgentBadge Component
- **Priority:** P2
- **File:** `components/ui/NxAgentBadge.tsx`
- **Requirements:**
  - [ ] Shows count of running/thinking agents
  - [ ] NxAiPulse orb integration
  - [ ] Click navigates to Agents Hub filtered by status

### 1.6 NxRateLimitBanner Component
- **Priority:** P2
- **File:** `components/ui/NxRateLimitBanner.tsx`
- **Requirements:**
  - [ ] Dismissible amber banner below status bar
  - [ ] Shows provider name, reset countdown, "Switch Provider" CTA
  - [ ] Slides in from translateY(-100%) in 250ms
  - [ ] Shakes gently every 5s when visible

### 1.7 NxTokenBudget Component
- **Priority:** P2
- **File:** `components/ui/NxTokenBudget.tsx`
- **Requirements:**
  - [ ] 24×24px SVG ring showing daily token budget
  - [ ] Color thresholds: blue (<70%), amber (70-90%), crimson (>90%) with pulse
  - [ ] Opens UsageAnalyticsModal on click
  - [ ] Fetches GET /api/v1/stats/tokens/today

### 1.8 NxMemoryPressure Component
- **Priority:** P3
- **File:** `components/ui/NxMemoryPressure.tsx`
- **Requirements:**
  - [ ] Shows Redis memory usage percentage
  - [ ] Only visible when usage > 60%
  - [ ] Color: amber (60-80%), crimson (>80%)
  - [ ] Fetches GET /api/v1/health

### 1.9 NxProviderDots Component
- **Priority:** P2
- **File:** `components/ui/NxProviderDots.tsx`
- **Requirements:**
  - [ ] Row of colored dots per AI provider
  - [ ] Colors: emerald (online), amber (degraded), crimson (offline)
  - [ ] Tooltip shows latency and status
  - [ ] Polls GET /api/v1/ai/providers/health every 60s

### 1.10 NxNotificationBell Component
- **Priority:** P1
- **File:** `components/ui/NxNotificationBell.tsx`
- **Requirements:**
  - [ ] Bell icon with unread count badge
  - [ ] Opens NxNotificationDrawer on click
  - [ ] Shake animation on new notification
  - [ ] Badge pops in with spring scale

---

## 2. Modals, Drawers & Overlays

### 2.1 NxLogViewerModal Component
- **Priority:** P1
- **File:** `components/modals/NxLogViewerModal.tsx`
- **Requirements:**
  - [ ] Full-screen glass modal with real-time log stream
  - [ ] Left sidebar with filter checkboxes (debug/info/warning/error)
  - [ ] JetBrains Mono font for log entries
  - [ ] Color-coded level pills
  - [ ] Search input with regex support
  - [ ] "Pause stream" and "Export as JSON" buttons
  - [ ] Auto-scroll to bottom (pausable)
  - [ ] Echo real-time log streaming

### 2.2 NxThoughtTraceDrawer Component
- **Priority:** P1
- **File:** `components/drawers/NxThoughtTraceDrawer.tsx`
- **Requirements:**
  - [ ] 480px wide slide-in drawer
  - [ ] Glass terminal aesthetic with JetBrains Mono
  - [ ] Shows agent reasoning loop in real-time
  - [ ] Step states: thinking, tool-call, observation, response
  - [ ] Fade-in animation for new steps

### 2.3 NxQueueModal Component
- **Priority:** P2
- **File:** `components/modals/NxQueueModal.tsx`
- **Requirements:**
  - [ ] Centered glass modal with job queue table
  - [ ] Columns: Job Name, Status, Queue, Attempts, Progress, Actions
  - [ ] Actions: Pause, Retry, Cancel with optimistic UI
  - [ ] Row highlight on status change

### 2.4 NxTaskDetailDrawer Component
- **Priority:** P1
- **File:** `components/drawers/NxTaskDetailDrawer.tsx`
- **Requirements:**
  - [ ] 560px wide right drawer
  - [ ] Shows trace_id (copy button), status timeline
  - [ ] Step-by-step log accordion
  - [ ] Raw JSON payload viewer with syntax highlighting

### 2.5 NxMemoryConsolidationModal Component
- **Priority:** P2
- **File:** `components/modals/NxMemoryConsolidationModal.tsx`
- **Requirements:**
  - [ ] Before/after split view for memory consolidation
  - [ ] Left panel: source memories, right panel: consolidated output
  - [ ] Edit consolidated text before confirming

### 2.6 NxWorkflowLogModal Component
- **Priority:** P2
- **File:** `components/modals/NxWorkflowLogModal.tsx`
- **Requirements:**
  - [ ] Shows workflow execution log with timeline view
  - [ ] Step markers on left, detail on right
  - [ ] Export as JSON and re-run from failed step options

### 2.7 NxProviderHealthModal Component
- **Priority:** P2
- **File:** `components/modals/NxProviderHealthModal.tsx`
- **Requirements:**
  - [ ] Async "Test Connection" modal with progress ring
  - [ ] Shows latency, model count, authentication status
  - [ ] Progress ring animation while pinging

### 2.8 NxApiKeyModal Component
- **Priority:** P1
- **File:** `components/modals/NxApiKeyModal.tsx`
- **Requirements:**
  - [ ] API key manager with masked display
  - [ ] Reveal toggle with 30s auto-hide
  - [ ] Copy-to-clipboard functionality
  - [ ] "Rotate Key" and usage stats

### 2.9 NxTraceInspectorDrawer Component
- **Priority:** P2
- **File:** `components/drawers/NxTraceInspectorDrawer.tsx`
- **Requirements:**
  - [ ] Shows raw JSON payload chain for trace
  - [ ] Collapsible JSON tree viewer
  - [ ] Timeline of events in trace

### 2.10 NxContactQuickView Component
- **Priority:** P3
- **File:** `components/ui/NxContactQuickView.tsx`
- **Requirements:**
  - [ ] Hover popover on contact name
  - [ ] Shows avatar, relationship, emotional baseline, last interaction
  - [ ] 400ms hover delay, 150ms fade-in scale animation

---

## 3. Contact Profile Components

### 3.1 NxContactCard3D Component
- **Priority:** P1
- **File:** `components/contacts/NxContactCard3D.tsx`
- **Requirements:**
  - [ ] CSS 3D perspective flip card
  - [ ] Front: avatar with gradient ring, name, stats
  - [ ] Back: AI relationship summary, emotional baseline, quick actions
  - [ ] Flip animation: 800ms cubic-bezier transition
  - [ ] Mouse tilt effect on hover

### 3.2 NxEmotionRadar Component
- **Priority:** P1
- **File:** `components/contacts/NxEmotionRadar.tsx`
- **Requirements:**
  - [ ] ECharts radar chart for emotional baseline
  - [ ] 6 axes: Joy, Trust, Anticipation, Surprise, Sadness, Anger
  - [ ] Elastic-out animation on mount

### 3.3 NxRelationTimeline Component
- **Priority:** P2
- **File:** `components/contacts/NxRelationTimeline.tsx`
- **Requirements:**
  - [ ] Vertical scrollable timeline of relationship events
  - [ ] Animated fly-in for each event card
  - [ ] Gold glow pulse for milestones

### 3.4 NxEngagementRing Component
- **Priority:** P2
- **File:** `components/contacts/NxEngagementRing.tsx`
- **Requirements:**
  - [ ] 120×120px SVG ring showing engagement score
  - [ ] Color thresholds matching confidence badge
  - [ ] Ring fill animation 1200ms ease-out

### 3.5 NxChannelStatus Component
- **Priority:** P2
- **File:** `components/contacts/NxChannelStatus.tsx`
- **Requirements:**
  - [ ] Channel badges with status dots
  - [ ] Icons: WhatsApp (green), SMS (blue), Email (slate)

### 3.6 NxMemoryMiniGraph Component
- **Priority:** P2
- **File:** `components/contacts/NxMemoryMiniGraph.tsx`
- **Requirements:**
  - [ ] Force-directed graph of related memories
  - [ ] Nodes colored by memory type
  - [ ] Physics simulation on load

### 3.7 NxActivityHeatmap Component
- **Priority:** P3
- **File:** `components/contacts/NxActivityHeatmap.tsx`
- **Requirements:**
  - [ ] GitHub-style heatmap for 52 weeks
  - [ ] Color intensity by interaction count
  - [ ] Column-by-column fade-in animation

### 3.8 NxConflictDiff Component
- **Priority:** P1
- **File:** `components/contacts/NxConflictDiff.tsx`
- **Requirements:**
  - [ ] Split-pane diff view for conflicting data
  - [ ] Pulsing crimson border on conflict
  - [ ] "Keep This" / "Keep Other" buttons

### 3.9 NxVersionHistory Component
- **Priority:** P2
- **File:** `components/contacts/NxVersionHistory.tsx`
- **Requirements:**
  - [ ] Accordion showing belief version history
  - [ ] Strikethrough for superseded entries
  - [ ] Inline diff popover

### 3.10 NxTagCloud Component
- **Priority:** P3
- **File:** `components/contacts/NxTagCloud.tsx`
- **Requirements:**
  - [ ] Animated glass pill chips
  - [ ] Staggered fade-in animation
  - [ ] Editable mode with autocomplete

---

## 4. Chat & AI Interface Components

### 4.1 Token Stream Typing Effect
- **Priority:** P1
- **File:** `components/chat/NxAiBubble.tsx`
- **Requirements:**
  - [ ] Character-by-character token streaming
  - [ ] Blinking cursor while streaming
  - [ ] Remove cursor on completion
  - [ ] Echo TokenStreamed events

### 4.2 NxVoiceOrb Component
- **Priority:** P2
- **File:** `components/chat/NxVoiceOrb.tsx`
- **Requirements:**
  - [ ] Floating voice dictation orb
  - [ ] Web Audio API analyser for waveform visualization
  - [ ] 20 frequency bars animation
  - [ ] Breathing idle animation, expand on activate

### 4.3 NxAiBubble Component
- **Priority:** P1
- **File:** `components/chat/NxAiBubble.tsx`
- **Requirements:**
  - [ ] Markdown rendering with highlight.js
  - [ ] Code blocks with copy button
  - [ ] Confidence badge (emerald/amber/crimson)
  - ] "Regenerate" button on hover

### 4.4 NxContextBar Component
- **Priority:** P1
- **File:** `components/chat/NxContextBar.tsx`
- **Requirements:**
  - [ ] NxTokenMeter in chat header
  - [ ] Shows context window usage
  - [ ] "Trim Context" button at >90%

---

## 5. Navigation & Shell Components

### 5.1 NxNavRail Component
- **Priority:** P1
- **File:** `components/navigation/NxNavRail.tsx`
- **Requirements:**
  - [ ] Collapsible: 80px (icon-only) / 240px (expanded)
  - [ ] State persists in localStorage
  - [ ] 250ms width transition
  - [ ] Lucide icons with 2px stroke

### 5.2 NxCommandBar Component
- **Priority:** P1
- **File:** `components/navigation/NxCommandBar.tsx`
- **Requirements:**
  - [ ] Cmd+K / Ctrl+K fuzzy search overlay
  - [ ] Searches contacts, memories, agents, workflows, routes
  - [ ] Keyboard navigation with ↑↓ Enter Escape

### 5.3 NxThemeSwitcher Component
- **Priority:** P2
- **File:** `components/navigation/NxThemeSwitcher.tsx`
- **Requirements:**
  - [ ] Dark / Light / System Auto toggle
  - [ ] Persists in localStorage
  - [ ] 300ms color cross-fade transition

---

## 6. Data Visualization Components

### 6.1 NxUsageAnalytics Component
- **Priority:** P2
- **File:** `components/analytics/NxUsageAnalytics.tsx`
- **Requirements:**
  - [ ] Token usage, API calls, cost, top intents charts
  - [ ] Date range selector: Today / 7d / 30d / Custom

### 6.2 other analytics components
- [ ] NxLatencyChart - Provider latency comparison
- [ ] NxTaskCompletionChart - Task completion rate line
- [ ] NxMemoryGrowthChart - Memory growth timeline
- [ ] NxAgentHeatmap - GitHub-style activity heatmap

---

## 7. Mobile & Touch Components

### 7.1 Swipe Gesture Navigation
- **Priority:** P2
- **Requirements:**
  - [ ] Swipe right from left edge for back navigation
  - [ ] Panel follows finger with transform
  - [ ] Velocity threshold for complete/back snap

### 7.2 NxBottomSheet Component
- **Priority:** P2
- **File:** `components/mobile/NxBottomSheet.tsx`
- **Requirements:**
  - [ ] Slides up from bottom with drag handle
  - [ ] Snap points support (40%, 90%)
  - [ ] Semi-transparent backdrop

### 7.3 NxFab Component
- **Priority:** P2
- **File:** `components/mobile/NxFab.tsx`
- **Requirements:**
  - [ ] Circular FAB above bottom tab bar
  - [ ] Expands to show secondary actions
  - [ ] Hub-specific primary actions

---

## 8. Accessibility Components

### 8.1 Skip-to-Content Link
- **Priority:** P2
- **Requirements:**
  - [ ] Hidden link becomes visible on focus
  - [ ] Positioned before all navigation

### 8.2 NxLiveRegion Component
- **Priority:** P2
- **File:** `components/a11y/NxLiveRegion.tsx`
- **Requirements:**
  - [ ] ARIA live region for screen reader announcements
  - [ ] Announces notifications, task completion, agent status

---

## Requirements Checklist Summary

### P1 Critical Path Features (29 items)
- [ ] NxStatusBar, NxConnectionDot, NxJobRail, NxNotificationBell
- [ ] NxLogViewerModal, NxThoughtTraceDrawer, NxTaskDetailDrawer
- [ ] NxContactCard3D, NxEmotionRadar, NxConflictDiff
- [ ] NxAiBubble, NxVoiceOrb, NxContextBar, NxNavRail, NxCommandBar

### P2 High Value Features (31 items)
- [ ] NxQueuePill, NxAgentBadge, NxRateLimitBanner, NxTokenBudget
- [ ] NxQueueModal, NxMemoryConsolidationModal, NxProviderHealthModal
- [ ] NxEngagementRing, NxChannelStatus, NxRelationTimeline
- [ ] NxMemoryMiniGraph, NxMemoryPressure, NxProviderDots
- [ ] NxApiKeyModal, NxTraceInspectorDrawer
- [ ] NxTagCloud, NxPersonalityBars, NxMultiAgentTimeline
- [ ] NxBranchVisualizer, NxThemeSwitcher, NxUsageAnalytics
- [ ] NxPullRefresh, NxBottomSheet, NxFab, NxFontScale

### P3 Medium Value Features (19 items)
- [ ] NxActivityHeatmap, NxVersionHistory, NxPresenceDot
- [ ] NxMessageReactions, NxPinnedMessages, NxAgentSparkline
- [ ] NxAgentCompare, NxConsolidationGraph, NxMemoryDiff
- [ ] NxSemanticCluster, NxMemoryImportExport, NxTagCloud
- [ ] NxAgentWorkloadChart, NxAgentHeatmap, NxPermissionManager
- [ ] NxFontScale, NxShortcutMap, NxOfflineBanner

### P4 Nice-to-Have Features (4 items)
- [ ] NxCelebration, NxAiSummary, Customizable Hub Layout
- [ ] Session Undo, High Contrast Mode