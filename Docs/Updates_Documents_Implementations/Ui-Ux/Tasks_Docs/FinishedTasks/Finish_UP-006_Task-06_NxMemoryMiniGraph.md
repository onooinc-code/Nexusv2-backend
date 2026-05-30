# UP-006_Task-06: NxMemoryMiniGraph — Contact Memory Graph

## Task Overview
Create NxMemoryMiniGraph.vue — compact ECharts force-directed graph.

## Feature Specification
- **Feature ID:** F-CP-06
- **File:** `resources/js/Components/NxMemoryMiniGraph.vue` (new)

## Requirements
1. Compact (300×200px) ECharts force-directed graph
2. Nodes: memory nodes related to this contact, colored by type (episodic=blue, semantic=purple, structured=emerald)
3. Edges: relationships between memories
4. Props: contactId: String, maxNodes: Number (default: 20)
5. Click node: opens NxTraceInspectorDrawer or Memory Hub filtered to that memory

## Implementation Details
- ECharts graph type with layout: 'force'
- Node size: 8px (episodic), 10px (semantic), 12px (structured)
- Edge: 1px solid rgba(255,255,255,0.2)
- Background: transparent

## Verification
- `npm run build` passes
- Nodes spawn from center with physics
- Node colors match type
- Click opens trace inspector
