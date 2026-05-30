# UP-006_Task-04: NxEngagementRing — Engagement Score Ring

## Task Overview
Create NxEngagementRing.vue — SVG ring meter showing engagement score.

## Feature Specification
- **Feature ID:** F-CP-04
- **File:** `resources/js/Components/NxEngagementRing.vue` (new)

## Requirements
1. SVG ring meter (120×120px) showing engagement score (0–100)
2. Props: score: Number, trend: 'up' | 'down' | 'stable'
3. Animation: ring fills from 0 to score using stroke-dashoffset over 1200ms ease-out
4. Center text: score counts up during fill using requestAnimationFrame
5. Color: 0–40 → Crimson; 40–70 → Amber; 70–100 → Emerald

## Implementation Details
- SVG <circle> with r=54, cx=60, cy=60, stroke-width=8
- stroke-dasharray: 339.29 (2πr); stroke-dashoffset computed from score
- Background circle: rgba(255,255,255,0.1); foreground: color per range
- Center text: font-size: 32px; font-weight: 700; font-family: 'Inter'

## Verification
- `npm run build` passes
- Ring fills from 0 to score over 1200ms
- Center text counts up during fill
- Color changes based on score range
