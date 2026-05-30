# UP-005_Task-02: AgentsView — Orb Cards + Echo

## Task Overview
Add NxAiPulse orb to agent cards and wire Echo events.

## Status
- Completed: AgentsView orb cards and Echo event wiring implemented.

## Feature Specification
- **Feature ID:** F-VF-02
- **File:** `resources/js/Pages/AgentsView.vue` (modify)

## Requirements
1. Embed NxAiPulse orb in each agent card, state mapped to agent.status
2. Wire AgentExecuted Echo → useSystem().incrementAgentCount() / decrementAgentCount()
3. Add NxAgentWorkloadChart (E02) donut chart
4. Add NxAgentSparkline (E03) inline performance chart
5. Add NxThoughtTraceDrawer (B02/E04) slide-in for agent reasoning

## Implementation Details
- Agent card: NxGlassCard with NxAiPulse orb in header
- Status mapping: idle → idle pulse; running → thinking rotation; failed → error jitter
- NxAgentWorkloadChart: ECharts donut, center shows total active tasks

## Verification
- `npm run build` passes
- Each agent card has NxAiPulse orb
- AgentExecuted event updates agent status
- Workload chart renders correctly
