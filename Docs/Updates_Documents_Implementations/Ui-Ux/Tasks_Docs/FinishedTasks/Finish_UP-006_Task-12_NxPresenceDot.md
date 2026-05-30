# UP-006_Task-12: NxPresenceDot — Last-Active Indicator

## Task Overview
Create NxPresenceDot.vue — color-coded dot showing when contact was last active.

## Feature Specification
- **Feature ID:** F-CP-12
- **File:** `resources/js/Components/NxPresenceDot.vue` (new)

## Requirements
1. Color-coded dot showing when contact was last active
2. Props: lastSeenAt: Date
3. Color: today → emerald pulse; this week → amber; this month → slate; older → grey static
4. Animation: emerald state has breathing pulse scale(1.0)→scale(1.4) at 2s
5. Tooltip: "Last active: 2 hours ago"

## Implementation Details
- Dot: width: 8px; height: 8px; border-radius: 50%
- Emerald pulse: @keyframes pulse-emerald { 0%, 100% { transform: scale(1); opacity: 1; } 50% { transform: scale(1.4); opacity: 0.7; } } at 2s

## Verification
- `npm run build` passes
- Color changes based on time range
- Pulse animation for "today"
- Tooltip shows relative time
