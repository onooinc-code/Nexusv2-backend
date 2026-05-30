# UP-006_Task-09: NxVersionHistory — Belief Version History

## Task Overview
Create NxVersionHistory.vue — collapsible accordion for version history.

## Feature Specification
- **Feature ID:** F-CP-09
- **File:** `resources/js/Components/NxVersionHistory.vue` (new)

## Requirements
1. Collapsible accordion showing version history of any belief/fact
2. Superseded entries: text-decoration: line-through; opacity: 0.5
3. Click entry: show full "Diff" in popover
4. Props: fieldKey: String, versions: Array<{ value, updatedAt, source, supersededAt }>
5. Trigger: DB contacts.superseded_at field is set

## Implementation Details
- Accordion: max-height: 0 → 500px transition
- Struck-through text: red underline draws left-to-right on mount
- Diff popover: + green, - red highlighting

## Verification
- `npm run build` passes
- Accordion expands/collapses
- Superseded entries have strikethrough
- Diff popover shows on click
