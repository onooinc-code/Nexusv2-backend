# NxThoughtTraceDrawer

## Specification
- **File**: `components/drawers/NxThoughtTraceDrawer.tsx`
- **Width**: 480px (Slide-in from right)
- **Aesthetic**: Glass terminal with JetBrains Mono font.

## Features
- **Real-time Reasoning**: Displays the agent's internal "thought process" loop.
- **Step Visualizer**:
  - `Thinking`: Amber pulse icon.
  - `Tool-Call`: Blue icon with tool name and parameters.
  - `Observation`: Green icon with truncated output.
  - `Response`: Emerald checkmark.
- **Auto-scroll**: Follows the latest thought step as it arrives via Echo.
- **Code Highlighting**: JSON payloads in tool calls are syntax-highlighted.