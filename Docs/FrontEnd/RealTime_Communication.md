# Real‑Time Communication

## WebSocket (Reverb)
- Driver: `BROADCAST_DRIVER=reverb`.
- Frontend: Laravel Echo with private channels.
- Backend: Events implementing `ShouldBroadcastNow`.

## Channels
- `conversation.{id}` – Message sent/received.
- `job.{id}` – Job progress updates.
- `notification` – Global toast notifications.
- `presence` – User online status (via `NxPresenceDot.vue`).

## Frontend Integration (useEcho.js)
```js
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

export function useEcho() {
  const echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: import.meta.env.VITE_REVERB_PORT,
    forceTLS: false,
    disableStats: true,
  });
  return echo;
}
```

## Echo Store (useEchoStore.js)
- Tracks channel subscriptions.
- Emits events to Pinia for UI updates.
- Cleanup on unmount.

## Broadcast With (Backend)
```php
class MessageSent implements ShouldBroadcastNow {
    public function broadcastOn(): Channel {
        return new PrivateChannel("conversation.{$this->conversationId}");
    }
    public function broadcastWith(): array {
        return ['id' => $this->messageId, 'content' => $this->content];
    }
}
```

## UI Components Using Real‑Time
- `LiveChatStream.vue` – Token‑by‑token streaming.
- `NxLiveLoader.vue` – Job progress indicator.
- `GlobalJobMonitor.vue` – Queue dashboard.
- `NxPresenceDot.vue` – Online status.
- `NxNotificationBell.vue` – Toast notifications.

## Fallback
If WebSocket unavailable, polling via `useOfflineQueue.js`.