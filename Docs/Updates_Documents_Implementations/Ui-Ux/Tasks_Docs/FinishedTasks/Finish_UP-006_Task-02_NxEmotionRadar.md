# UP-006_Task-02: NxEmotionRadar — Emotional Baseline Radar

## Task Overview
Create NxEmotionRadar.vue — ECharts radar chart for emotional baseline.

## Feature Specification
- **Feature ID:** F-CP-02
- **File:** `resources/js/Components/NxEmotionRadar.vue` (new)

## Requirements
1. ECharts radar chart mapping DB contacts.emotional_baseline JSON to 6 axes
2. Axes: Joy, Trust, Anticipation, Surprise, Sadness, Anger
3. Props: baseline: Object, history: Array (for animated transitions)
4. Animation: polygon fills from center outward using elasticOut at 800ms
5. Toggle between "Current" and "Historical Average"

## Implementation Details
- Size: 300×300px
- Fill color: #6366F1 (AI-Core purple) with 0.6 opacity
- Line color: #6366F1 with 2px width
- Background: transparent
- Axis labels: font-size: 11px; color: rgba(255,255,255,0.6)

## Verification
- `npm run build` passes
- 6 axes render with correct labels
- Polygon animates from center outward
- History toggle works
