# UP-006_Task-03: NxRelationTimeline — Animated Timeline

## Task Overview
Create NxRelationTimeline.vue — vertical timeline of relationship events.

## Feature Specification
- **Feature ID:** F-CP-03
- **File:** `resources/js/Components/NxRelationTimeline.vue` (new)

## Requirements
1. Vertical scrollable timeline of key relationship events
2. Events: first contact, memory milestones, sentiment shifts, workflow interactions
3. Props: contactId: String, events: Array
4. Animation: event cards fly in from translateX(-20px) alternating left/right; connecting line draws with stroke-dashoffset
5. Milestone events (first contact, anniversary): gold glow pulse
6. Mobile: horizontal scroll timeline instead of vertical

## Implementation Details
- Line: 2px solid rgba(255,255,255,0.1); draws from top to bottom
- Event dot: 12px circle on the line; milestone = 16px with gold glow
- Event card: background: rgba(22,27,34,0.7); backdrop-filter: blur(12px); border: 1px solid rgba(255,255,255,0.1); border-radius: 8px
- Mobile: flex-direction: row; overflow-x: auto; scroll-snap-type: x mandatory

## Verification
- `npm run build` passes
- Timeline renders with events
- Line draws from top to bottom
- Milestone events have gold glow
