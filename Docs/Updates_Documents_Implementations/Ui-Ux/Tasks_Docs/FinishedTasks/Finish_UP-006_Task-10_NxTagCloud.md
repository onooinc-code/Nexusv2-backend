# UP-006_Task-10: NxTagCloud — Animated Tag Chips

## Task Overview
Create NxTagCloud.vue — contact preference and personality tags.

## Feature Specification
- **Feature ID:** F-CP-10
- **File:** `resources/js/Components/NxTagCloud.vue` (new)

## Requirements
1. Contact preference and personality tags as glass pill chips
2. On load: each chip flies in with staggered delay (50ms per chip)
3. Props: tags: Array<{ label, category, color }>, editable: Boolean
4. Editable: click + to add tag (autocomplete); click × to remove with shrink animation
5. Categories: personality (purple), preference (blue), topic (emerald), flag (amber)

## Implementation Details
- Chip: display: inline-flex; align-items: center; gap: 4px; padding: 4px 10px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 9999px; font-size: 12px
- Animation: scale(0) opacity(0) → scale(1) opacity(1) with spring bounce, staggered by index × 50ms

## Verification
- `npm run build` passes
- Tags render as glass pills
- Chips fly in with stagger
- Add/remove animations work
