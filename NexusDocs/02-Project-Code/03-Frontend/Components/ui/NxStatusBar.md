# NxStatusBar

## Specification
- **File**: `components/layout/NxStatusBar.tsx`
- **Height**: 40px
- **Z-Index**: 50 (Top)

## Sub-components
### NxConnectionDot
- **States**: 
  - `connecting`: Amber pulse
  - `connected`: Emerald breathing
  - `disconnected`: Crimson static
  - `error`: Crimson jitter

### NxJobRail
- **Height**: 2px
- **Color**: Nexus Blue (#007AFF)
- **Behavior**: Spans full width, listens to `JobProgressUpdated`.

### NxNotificationBell
- **Behavior**: Unread count badge, opens drawer, shakes on new event.