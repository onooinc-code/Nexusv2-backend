# UP-005_Task-07: DashboardView — Grid Fix + NxGlassCard

## Task Overview
Fix grid overflow and use NxGlassCard in DashboardView.vue.

## Feature Specification
- **Feature ID:** F-VF-07
- **File:** `resources/js/Pages/DashboardView.vue` (modify)

## Requirements
1. Fix grid overflow: minmax(400px, 1fr) → minmax(min(400px, 100%), 1fr)
2. Replace .kpi-card divs with NxGlassCard components
3. Add NxUsageAnalytics (I01) chart panel
4. Add NxAiSummary (L10) collapsible summary card

## Implementation Details
- Grid: grid-template-columns: repeat(auto-fit, minmax(min(400px, 100%), 1fr))
- NxGlassCard: elevation={2} for KPI cards; hoverable for interactive cards
- NxUsageAnalytics: ECharts line + bar + area + pie charts

## Verification
- `npm run build` passes
- Grid doesn't overflow at 375px
- KPI cards use NxGlassCard with elevation
