# NxLogViewerModal

## Specification
- **File**: `components/modals/NxLogViewerModal.tsx`
- **Aesthetic**: Full-screen glass modal with JetBrains Mono font.

## Features
- **Real-time Stream**: Integrates with Laravel Echo to stream application logs.
- **Filtering**: Sidebar with checkboxes for `Debug`, `Info`, `Warning`, and `Error`.
- **Search**: Regex-supported search bar for log entries.
- **Controls**:
  - **Pause Stream**: Halts auto-scroll and new entry rendering.
  - **Export**: Download current log buffer as JSON.
- **Auto-scroll**: Automatically scrolls to the bottom unless paused or user manually scrolls up.