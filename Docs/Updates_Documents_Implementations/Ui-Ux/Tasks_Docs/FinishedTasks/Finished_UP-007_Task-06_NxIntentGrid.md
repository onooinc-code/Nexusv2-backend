# 🎯 TASK: UP-007 - Task 06: NxIntentGrid Component (F-PU-01)
- **Status:** ✅ COMPLETED
- **Dependencies:** None

## 1. Objective
Create `NxIntentGrid.vue` — a 2D intent routing matrix for provider/model selection with responsive mobile accordion support.

## 2. Files to Create/Modify
- `resources/js/Components/NxIntentGrid.vue` (new)
- `resources/js/Components/NxGlassCard.vue` (existing wrapper)

## 3. Implementation Steps
1. Build a matrix layout with intents as rows and cost profiles as columns.
2. Populate each cell with a provider/model dropdown.
3. Fetch intents and provider/model data from the AI endpoints.
4. Implement optimistic updates with `PUT /api/v1/ai/intents/routing` and flash success state.
5. Add a mobile accordion variant for smaller screens.

## ✅ Final Verification
- [x] Matrix loads with intents and profiles
- [x] Cell dropdown updates persist via API
- [x] Success flash animation works
- [x] Mobile accordion layout is functional