# 🎯 TASK: UP-007 - Task 05: NxUsageAnalytics Component (F-VIZ-01)
- **Status:** ✅ COMPLETED
- **Dependencies:** None

## 1. Objective
Create `NxUsageAnalytics.vue` — a usage dashboard with line, bar, area, and pie charts for token usage, provider calls, cost estimate, and top intents.

## 2. Files to Create/Modify
- `resources/js/Components/NxUsageAnalytics.vue` (new)
- `resources/js/Components/NxGlassCard.vue` (existing card wrapper)

## 3. Implementation Steps
1. Create a dashboard grid with four chart cards.
2. Use `vue-echarts` to render line, bar, area, and pie charts.
3. Fetch data from `GET /api/v1/stats/usage?range={range}` and refresh every 60s.
4. Add a date range selector with Today / 7d / 30d / Custom.

## ✅ Final Verification
- [x] All four charts render correctly
- [x] Range selector updates chart data
- [x] Data refreshes every 60 seconds
- [x] Glass card styling and layout are responsive