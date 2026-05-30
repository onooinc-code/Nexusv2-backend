# UP-005_Task-10: Card.vue — Fix Slot API

## Task Overview
Fix slot API mismatch in Card.vue.

## Feature Specification
- **Feature ID:** F-VF-10
- **File:** `resources/js/Components/Card.vue` (modify)

## Requirements
1. Fix slot API mismatch: #body slot → default slot
2. Keep #header and #footer slots as-is
3. Update all consumers of Card.vue to use default slot instead of #body

## Implementation Details
- Rename <slot name="body" /> → <slot /> (default slot)
- Update all files using <Card><template #body>...</template></Card> → <Card>...</Card>

## Verification
- `npm run build` passes
- Default slot renders correctly
- #header and #footer slots work
