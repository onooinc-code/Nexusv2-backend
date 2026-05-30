# 🎯 TASK: UP-007 - Task 09: NxAiSummary Component (F-PU-04)
- **Status:** ✅ COMPLETED
- **Dependencies:** None

## 1. Objective
Create `NxAiSummary.vue` — a collapsible AI summary widget that shows a typed TL;DR for the active hub.

## 2. Files to Create/Modify
- `resources/js/Components/NxAiSummary.vue` (new)
- `resources/js/Components/NxGlassCard.vue` (existing wrapper)

## 3. Implementation Steps
1. Build a collapsible glass card with summary text and expand/collapse toggle.
2. Fetch summary from `POST /api/v1/ai/summarize` with `{ scope: hub }`.
3. Animate summary text with a typing effect when expanded.
4. Show loading state while the summary is fetched.

## ✅ Final Verification
- [x] Summary widget renders for the active hub
- [x] Expand/collapse works
- [x] Typing animation plays on expand
- [x] API call returns summary text