# NxEmotionRadar

## Specification
- **File**: `components/modules/contacts/NxEmotionRadar.tsx`
- **Library**: Apache ECharts (via `vue-echarts` equivalent for React).

## Features
- **Six-Axis Radar**:
  - Joy, Trust, Anticipation, Surprise, Sadness, Anger.
- **Dynamic Data**: Updates based on the latest contact interaction analysis.
- **Styling**:
  - Semi-transparent area fill.
  - Glowing axis lines.
  - Custom tooltips showing exact percentage values.
- **Animation**: `elasticOut` transition when the chart mounts or data changes.