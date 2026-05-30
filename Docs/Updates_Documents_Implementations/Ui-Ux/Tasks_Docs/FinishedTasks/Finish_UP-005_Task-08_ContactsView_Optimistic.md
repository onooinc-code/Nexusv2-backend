# UP-005_Task-08: ContactsView — Optimistic Add + 3D Card

## Task Overview
Implement optimistic add and 3D card in ContactsView.vue.

## Feature Specification
- **Feature ID:** F-VF-08
- **File:** `resources/js/Pages/ContactsView.vue` (modify)

## Requirements
1. Implement optimistic addContact() via useContacts().addContact()
2. Replace simple contact card with NxContactCard3D (C01) flip card
3. Add NxEmotionRadar (C02) ECharts radar
4. Add NxRelationTimeline (C03) vertical timeline
5. Add NxConflictDiff (C08) for conflict resolution

## Implementation Details
- NxContactCard3D: CSS 3D perspective, flip on click, avatar ring gradient rotation
- NxEmotionRadar: 6-axis radar (Joy, Trust, Anticipation, Surprise, Sadness, Anger)
- NxRelationTimeline: vertical timeline with SVG stroke-dashoffset draw animation
- NxConflictDiff: card glows crimson, split-pane diff on expand

## Verification
- `npm run build` passes
- Optimistic add works with instant card appearance
- 3D card flips on click
- Emotion radar renders correctly
