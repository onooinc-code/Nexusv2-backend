# UP-005_Task-03: MemoryView — Decay Opacity + Echo

## Task Overview
Add decay opacity and wire Echo events in MemoryView.vue.

## Status
- Completed: MemoryView opacity decay and Echo event handling implemented.

## Feature Specification
- **Feature ID:** F-VF-03
- **File:** `resources/js/Pages/MemoryView.vue` (modify)

## Requirements
1. Wire MemoriesExtracted, MemoryIndexed, MemoryVectorized Echo events
2. Map DB memories.decay_weight → opacity (1.0 → 0.3)
3. Add NxConfidenceBadge (F03) showing confidence score
4. Add NxConsolidationGraph (F02) force-directed graph
5. Add decay slider filter (F04)

## Implementation Details
- Memory card opacity: opacity: 1 - (decay_weight * 0.7) (min 0.3)
- NxConfidenceBadge: > 0.8 → emerald; 0.6–0.8 → amber; < 0.6 → crimson
- NxConsolidationGraph: ECharts force-directed, nodes = memories, edges = relationships

## Verification
- `npm run build` passes
- Older memories have lower opacity
- Decay slider filters list in real-time
- Confidence badges show correct colors
