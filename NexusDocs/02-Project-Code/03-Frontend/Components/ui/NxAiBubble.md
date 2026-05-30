# NxAiBubble

## Specification
- **File**: `components/modules/chat/NxAiBubble.tsx`
- **Styling**: Glassmorphism with character-streaming cursor.

## Features
- **Token Streaming**: Real-time typing effect triggered by `TokenStreamed` events.
- **Markdown Support**: Rendered via `markdown-it` or `react-markdown` with `highlight.js`.
- **Confidence Badge**: 
  - Emerald: >90% confidence.
  - Amber: 60-90% confidence.
  - Crimson: <60% confidence.
- **Actions**: "Regenerate" and "Copy Code" buttons visible on hover.