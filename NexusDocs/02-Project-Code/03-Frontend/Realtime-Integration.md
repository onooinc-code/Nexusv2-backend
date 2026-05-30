# Real-time Integration (Laravel Echo)

## Overview
Nexus uses **Laravel Echo** combined with **Pusher** (or Laravel Reverb) to handle real-time updates in the Next.js frontend. This integration is crucial for the "System HUD" aesthetic, providing live feedback on agent thinking, job progress, and system notifications.

## Configuration
- **Library**: `laravel-echo`, `pusher-js`
- **Hook**: `useEcho.ts`

### Connection Setup
The connection is initialized in a global provider (`SystemProvider`) to ensure a single WebSocket connection across the application.

```typescript
// Example Echo Configuration
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

const echo = new Echo({
    broadcaster: 'reverb',
    key: process.env.NEXT_PUBLIC_REVERB_APP_KEY,
    wsHost: process.env.NEXT_PUBLIC_REVERB_HOST,
    wsPort: process.env.NEXT_PUBLIC_REVERB_PORT,
    forceTLS: false,
    enabledTransports: ['ws', 'wss'],
});
```

## Core Events

### 1. TokenStreamed
- **Channel**: `private-chat.{chatId}`
- **Payload**: `{ token: string, sequence: number }`
- **Usage**: Triggers the character-by-character typing effect in `NxAiBubble`.

### 2. JobProgressUpdated
- **Channel**: `private-queue`
- **Payload**: `{ jobId: string, progress: number, status: string }`
- **Usage**: Updates the `NxJobRail` width and status in `NxQueueModal`.

### 3. NotificationReceived
- **Channel**: `private-user.{userId}`
- **Payload**: `{ id: string, message: string, type: string }`
- **Usage**: Increments the `unreadCount` in `useSystemStore` and triggers the `NxNotificationBell` shake animation.

### 4. AgentThoughtUpdated
- **Channel**: `private-agent.{agentId}`
- **Payload**: `{ step: string, content: string, type: 'thinking' | 'tool-call' | 'observation' }`
- **Usage**: Populates the `NxThoughtTraceDrawer` in real-time.

## Error Handling
- **Reconnection**: Echo handles automatic reconnection. The `NxConnectionDot` listens to `connecting` and `disconnected` events to update the UI status.
- **Authentication**: Private channels require a valid Sanctum/JWT token passed in the Echo headers.