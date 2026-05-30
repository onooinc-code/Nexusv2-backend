# UP-006_Task-08: NxConflictDiff — Conflict Resolution

## Task Overview
Create NxConflictDiff.vue — conflict resolution diff component.

## Feature Specification
- **Feature ID:** F-CP-08
- **File:** `resources/js/Components/NxConflictDiff.vue` (new)

## Requirements
1. When DB contacts.conflict_with_id is set, card glows Crimson
2. Clicking expands into split-pane diff: left = current value, right = conflicting value
3. Buttons: [Keep This] and [Keep Other]
4. Props: conflictId: String, field: String, currentValue: any, conflictValue: any
5. Animation: card border pulses crimson; on expand split-pane slides open; on resolution chosen value slides to center

## Implementation Details
- Glow: box-shadow: 0 0 0 2px #EF4444 at 1.5s interval
- Split pane: display: flex; height: 0 → auto transition
- Resolution: chosen value translateX(0) opacity(1); other translateX(20px) opacity(0)

## Verification
- `npm run build` passes
- Card glows crimson when conflict exists
- Split-pane expands on click
- Resolution animation works
