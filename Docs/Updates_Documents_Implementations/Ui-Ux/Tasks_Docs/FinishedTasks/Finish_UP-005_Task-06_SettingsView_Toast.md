# UP-005_Task-06: SettingsView — Toast + Intent Grid

## Task Overview
Replace alert() with toast and add intent grid in SettingsView.vue.

## Feature Specification
- **Feature ID:** F-VF-06
- **File:** `resources/js/Pages/SettingsView.vue` (modify)

## Requirements
1. Replace all alert() calls with useNotificationStore().addToast()
2. Add NxIntentGrid (L05) — 2D intent routing matrix
3. Add NxAddProviderForm (L06) — multi-step provider add form
4. Add optimistic toggle for settings (instant update, revert on error)

## Implementation Details
- NxIntentGrid: rows = intents, columns = cost profiles (Fast/Quality/Budget), cells = provider/model dropdowns
- NxAddProviderForm: 4-step wizard (Basic Info → Auth → Test → Model Sync)
- Toast: glass pill, auto-dismiss after 5s, undo button for 8s

## Verification
- `npm run build` passes
- alert() calls replaced with toast
- Intent grid renders correctly
- Optimistic toggle works
