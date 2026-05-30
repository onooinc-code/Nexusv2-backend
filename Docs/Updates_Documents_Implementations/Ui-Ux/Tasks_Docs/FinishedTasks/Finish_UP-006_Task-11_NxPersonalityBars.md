# UP-006_Task-11: NxPersonalityBars — Trait Strength Bars

## Task Overview
Create NxPersonalityBars.vue — horizontal bars showing personality trait strengths.

## Feature Specification
- **Feature ID:** F-CP-11
- **File:** `resources/js/Components/NxPersonalityBars.vue` (new)

## Requirements
1. Horizontal bars showing personality trait strengths
2. Props: traits: Array<{ name, score, description }>
3. Animation: width fills from 0 to score using transition: width 800ms ease-out with 100ms stagger per bar
4. Hover: bar highlights with glow; tooltip shows description
5. Color: gradient from AI-Core purple to Action-Primary blue

## Implementation Details
- Bar container: height: 8px; background: rgba(255,255,255,0.1); border-radius: 4px; overflow: hidden
- Bar fill: height: 100%; background: linear-gradient(90deg, #6366F1, #007AFF); border-radius: 4px
- Hover glow: box-shadow: 0 0 8px rgba(99, 102, 241, 0.4)

## Verification
- `npm run build` passes
- Bars fill with stagger
- Hover shows tooltip
- Gradient colors correct
