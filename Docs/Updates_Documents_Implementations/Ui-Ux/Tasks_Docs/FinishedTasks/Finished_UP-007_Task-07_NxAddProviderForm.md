# 🎯 TASK: UP-007 - Task 07: NxAddProviderForm Component (F-PU-02)
- **Status:** ✅ COMPLETED
- **Dependencies:** None

## 1. Objective
Create `NxAddProviderForm.vue` — a 4-step provider onboarding wizard with test connection and model sync.

## 2. Files to Create/Modify
- `resources/js/Components/NxAddProviderForm.vue` (new)
- `resources/js/Components/NxProviderHealthModal.vue` (new)

## 3. Implementation Steps
1. Build a multi-step form with Basic Info, Auth, Test Connection, and Model Sync steps.
2. Add animated step transitions and a step progress indicator.
3. Save provider via `POST /api/v1/ai/providers` and test connection via `POST /api/v1/ai/providers/{id}/test`.
4. Sync models with `POST /api/v1/ai/providers/{id}/sync-models`.
5. Emit `complete` when the provider is added.

## ✅ Final Verification
- [x] Wizard advances through all four steps
- [x] Provider creation API calls succeed
- [x] Test connection step displays live feedback
- [x] Model sync step completes and shows models