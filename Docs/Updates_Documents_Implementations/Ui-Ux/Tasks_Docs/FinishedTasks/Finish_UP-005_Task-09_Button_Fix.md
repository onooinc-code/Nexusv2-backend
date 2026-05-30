# UP-005_Task-09: Button.vue — Fix Optimistic Prop + Color

## Task Overview
Add optimistic prop and fix color in Button.vue.

## Feature Specification
- **Feature ID:** F-VF-09
- **File:** `resources/js/Components/Button.vue` (modify)

## Requirements
1. Add optimistic prop (Boolean) to Button.vue
2. Fix color from green (#4ade80) to Nexus Blue (#007AFF)
3. Add optimisticState v-model support (pending | success | error)
4. Add loading slot for custom loading indicator

## Implementation Details
- Primary: background: #007AFF; color: white
- Optimistic success: background: #10B981 (brief flash)
- Optimistic error: background: #EF4444 with shake animation
- Touch target: min-height: 44px; min-width: 44px

## Verification
- `npm run build` passes
- Button color is Nexus Blue
- Optimistic prop works
- Touch target is ≥ 44px
