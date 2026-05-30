# Architecture Overview

## Technology Stack
- **Framework**: Vue 3 + Composition API (`<script setup>`).
- **Build**: Vite 6.0+.
- **Styling**: Tailwind CSS 3.4+.
- **State**: Pinia stores in `resources/js/stores/`.
- **Routing**: Vue Router lazy‑loaded routes.
- **Real‑time**: Laravel Echo + Reverb (WebSocket).
- **HTTP**: axios for REST calls.

## Clean Architecture Layers
```
Presentation (Vue Components)
    ↓
API Layer (Controllers, Requests)
    ↓
Service Layer (Services)
    ↓
Domain Layer (Models, Events)
    ↓
Data Layer (Repositories, Database)
```

## Frontend Directory Map
```
resources/js/
├── app.js                # App bootstrap
├── router/index.js       # Route definitions
├── services/apiClient.js # Axios instance
├── stores/               # Pinia stores
├── composables/          # Reusable logic
├── Components/           # Shared UI components
│   ├── Nx*.vue
│   ├── ContactList.vue
│   └── ...
└── Pages/                # Route components
    ├── DashboardView.vue
    ├── ContactsView.vue
    └── ...
```

## Data Flow
1. User action → Component emits event.
2. Event dispatched to Pinia store.
3. Store calls `apiClient` or Echo channel.
4. Service layer processes business logic.
5. UI updates reactively via store state.
6. WebSocket broadcasts trigger real‑time UI updates.

## Event Broadcasting
- Events implement `ShouldBroadcastNow`.
- Private channels only; sanitized payloads.
- Frontend subscribes via Echo in `useEcho.js`.

## Performance
- Lazy route loading.
- Skeleton placeholders during async fetch.
- Virtual scrolling for long lists (pending).
- Haptic feedback debounced.