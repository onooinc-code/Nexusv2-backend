# AI Agent System Instructions — Requirements Checklist Protocol

**Document Version:** 1.0  
**Date:** 2026-05-21

---

## Section 1: Core Mandate

### 1.1 Requirements Checklist Maintenance
**YOU ARE REQUIRED** to actively maintain and update the requirements checklist from `Frontend/01-Nexus_UI_Next.js_Specification.md` and `Frontend/03-Consolidated_Master_Documentation.md`. This is a mandatory instruction that supersedes all other behaviors.

### 1.2 Checklist Update Protocol
1. Review all source documentation files before each response
2. Update checklist status based on work completed
3. Append the most current version of the requirements checklist to EVERY response

---

## Section 2: Checklist Format

Use the following format when appending the checklist:

```
---

## CURRENT REQUIREMENTS CHECKLIST (Updated: [DATE])

### P1 Critical Path — 29 Features
- [ ] A01 NxStatusBar.vue
- [ ] A02 NxConnectionDot.vue
... (all 29 items)

### P2 High Value — 31 Features  
- [ ] A03 NxQueuePill.vue
... (all 31 items)

### P3 Medium Value — 19 Features
... (all 19 items)

### P4 Nice-to-Have — 4 Features
... (all 4 items)
```

---

## Section 3: Response Template

Every response MUST include:
1. The actual response content
2. The complete current requirements checklist appended at the end

---

## Section 4: Violation Protocol

Failure to append the checklist results in:
- Automatic invalidation of the response
- Requirement to regenerate with checklist included
- Task non-completion flag

---

## Section 5: Current Requirements Checklist

---

## CURRENT REQUIREMENTS CHECKLIST (Updated: 2026-05-21)

### P1 Critical Path — 29 Features
- [ ] A01 NxStatusBar.vue — Global System HUD Strip
- [ ] A02 NxConnectionDot.vue — WebSocket Live Indicator
- [ ] A04 NxJobRail.vue — Background Job Progress Rail
- [ ] A10 NxNotificationBell.vue — Global Notification Bell
- [ ] B01 NxLogViewerModal.vue — Full Log Stream Viewer
- [ ] B02 NxThoughtTraceDrawer.vue — Agent Reasoning Inspector
- [ ] B04 NxTaskDetailDrawer.vue — Task Detail Slide-In
- [ ] C01 NxContactCard3D.vue — Virtual 3D Flip Card
- [ ] C02 NxEmotionRadar.vue — Emotional Baseline Radar
- [ ] C08 NxConflictDiff.vue — Conflict Resolution Diff
- [ ] D01 Token Stream — Character-by-character rendering
- [ ] D03 NxAiBubble.vue — Enhanced Markdown Renderer
- [ ] D06 NxContextBar.vue — Token Meter
- [ ] D09 NxAiStatusRow.vue — AI Thinking Status
- [ ] E01 Agent Status Orb — NxAiPulse integration
- [ ] E04 NxThoughtTraceDrawer.vue — Thought-Trace (B02)
- [ ] F01 Memory Decay Opacity — Timeline visualization
- [ ] F03 NxConfidenceBadge.vue — Memory Confidence
- [ ] G03 Step Status Colors — Status border indicators
- [ ] G05 Execution Progress Overlay — Running workflow indicator
- [ ] H01 NxNavRail.vue — Collapsible Navigation
- [ ] H02 NxCommandBar.vue — Universal Search
- [ ] K03 NxLiveRegion.vue — Screen reader announcements
- [ ] L05 NxIntentGrid.vue — Intent routing matrix
- [ ] L06 NxAddProviderForm.vue — Add provider flow
- [ ] L07 NxTopBar.vue — Top loading bar

### P2 High Value — 31 Features
- [ ] A03 NxQueuePill.vue — Queue Depth Counter
- [ ] A05 NxAgentBadge.vue — Active Agent Count
- [ ] A06 NxRateLimitBanner.vue — Rate Limit Warning
- [ ] A07 NxTokenBudget.vue — Daily Token Usage Ring
- [ ] A09 NxProviderDots.vue — Provider Health Indicators
- [ ] B03 NxQueueModal.vue — Job Queue Manager
- [ ] B07 NxProviderHealthModal.vue — Provider Test & Status
- [ ] B08 NxApiKeyModal.vue — API Key Manager
- [ ] C03 NxRelationTimeline.vue — Animated Timeline
- [ ] C04 NxEngagementRing.vue — Engagement Score Ring
- [ ] C05 NxChannelStatus.vue — Channel Badges
- [ ] C06 NxMemoryMiniGraph.vue — Contact Memory Graph
- [ ] D02 NxVoiceOrb.vue — Voice Dictation Orb
- [ ] D07 Quick Actions Scroll — Horizontal scroll chips
- [ ] D10 Channel Switcher — WhatsApp/SMS toggle
- [ ] E02 NxAgentWorkloadChart.vue — Workload Distribution
- [ ] E05 NxMultiAgentTimeline.vue — Coordination View
- [ ] F02 NxConsolidationGraph.vue — Force-Directed Graph
- [ ] F05 NxMemoryDiff.vue — Before/After Diff
- [ ] G01 Snap-to-Grid — Magnetic grid positioning
- [ ] G02 Animated Flow Lines — Direction indicators
- [ ] G07 Step Error Popover — Error details
- [ ] H03 Recent Items — Quick Panel
- [ ] H05 Hub State Persistence — Remember last state
- [ ] H06 Animated Breadcrumb — Navigation trail
- [ ] H07 NxThemeSwitcher.vue — Theme Toggle
- [ ] I01 NxUsageAnalytics.vue — Usage Dashboard
- [ ] I02 NxLatencyChart.vue — Provider Latency
- [ ] J01 Swipe Back Gesture — Right-swipe navigation
- [ ] J02 Pull-to-Refresh — Mobile refresh
- [ ] J03 NxBottomSheet.vue — Mobile menus
- [ ] J05 NxFab.vue — Floating Action Button
- [ ] K02 Custom Focus Ring — Visual focus indicator
- [ ] K05 Reduced Motion — Respects motion preference
- [ ] K06 NxOfflineBanner.vue — Offline indicator
- [ ] L01 Multi-Select Mode — Bulk actions
- [ ] L10 NxAiSummary.vue — Hub TL;DR

### P3 Medium Value — 19 Features
- [ ] A08 NxMemoryPressure.vue — Redis Memory Pill
- [ ] B05 NxMemoryConsolidationModal.vue — Consolidation Preview
- [ ] B06 NxWorkflowLogModal.vue — Workflow Execution History
- [ ] B09 NxTraceInspectorDrawer.vue — Trace ID Viewer
- [ ] B10 NxContactQuickView.vue — Contact Hover Card
- [ ] C07 NxActivityHeatmap.vue — Interaction Frequency
- [ ] C09 NxVersionHistory.vue — Belief Version History
- [ ] C10 NxTagCloud.vue — Animated Tag Chips
- [ ] C11 NxPersonalityBars.vue — Trait Strength Bars
- [ ] C12 NxPresenceDot.vue — Last-Active Indicator
- [ ] D04 NxMessageReactions.vue — Emoji Reactions
- [ ] D05 NxPinnedMessages.vue — Pinned Message Section
- [ ] D08 NxConversationExport.vue — Export Modal
- [ ] E03 NxAgentSparkline.vue — Performance Sparklines
- [ ] E06 Capability Tags — Hover Details
- [ ] E08 Version Switcher — Agent Version History
- [ ] F04 Memory Decay Slider — Filter by decay weight
- [ ] F06 NxSemanticCluster.vue — Topic Clusters
- [ ] F08 Memory Tagging — Smart Filter
- [ ] G04 NxBranchVisualizer.vue — Conditional Diamonds
- [ ] G06 Version History Panel — Workflow versions
- [ ] H04 Pinned Hubs — Pin Favorites
- [ ] H08 NxFontScale.vue — Font Scale Slider
- [ ] I03 NxTaskCompletionChart.vue — Task Completion Rate
- [ ] I04 NxMemoryGrowthChart.vue — Memory Growth
- [ ] I05 NxAgentHeatmap.vue — Agent Activity Heatmap
- [ ] J04 NxContextMenu.vue — Long-press menu

### P4 Nice-to-Have — 4 Features
- [ ] K07 NxCelebration.vue — Success animation
- [ ] L02 NxShortcutMap.vue — Keyboard shortcuts
- [ ] L03 Split-Screen View — Dual hub view
- [ ] L09 Customizable Layout — Panel visibility