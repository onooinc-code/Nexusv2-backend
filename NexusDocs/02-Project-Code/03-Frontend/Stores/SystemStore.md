# System Store (Zustand)

## Specification
- **File**: `store/useSystemStore.ts`
- **Purpose**: Centralized state for the Global HUD and system-wide status.

## State Schema
- **Connection**:
  - `status`: `'connecting' | 'connected' | 'disconnected' | 'error'`
  - `latency`: Number (ms)
- **Queue**:
  - `depth`: Number of pending jobs.
  - `hasFailures`: Boolean.
- **Agents**:
  - `activeCount`: Number of agents currently "thinking" or "running".
- **Notifications**:
  - `unreadCount`: Number.
  - `recent`: Array of last 5 notification objects.

## Actions
- `setStatus(status)`: Update connection state.
- `updateQueue(stats)`: Update depth and failure flags.
- `incrementNotifications()`: Triggered by Echo events.