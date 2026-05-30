# UP-006_Task-07: NxActivityHeatmap — Interaction Heatmap

## Task Overview
Create NxActivityHeatmap.vue — GitHub-style contribution heatmap.

## Feature Specification
- **Feature ID:** F-CP-07
- **File:** `resources/js/Components/NxActivityHeatmap.vue` (new)

## Requirements
1. GitHub contribution-style heatmap showing interaction frequency over past 52 weeks
2. Props: contactId: String, data: Array<{ date, count }>
3. Color: 0 → Surface-Mid; 1–3 → light blue; 4–7 → Nexus Blue; 8+ → bright blue
4. Animation: cells fade in column by column from left to right over 600ms

## Implementation Details
- Grid: display: grid; grid-template-columns: repeat(53, 1fr); gap: 2px
- Cell: width: 10px; height: 10px; border-radius: 2px
- Tooltip on hover: shows date and interaction count

## Verification
- `npm run build` passes
- 52-week grid renders (53 columns)
- Color intensity matches interaction count
- Cells fade in with stagger
