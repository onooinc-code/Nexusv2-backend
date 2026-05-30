# Test Scenarios

## Unit Tests (Component Level)
- Test `NxActionButton.vue` click emits `click`.
- Test `NxGlassCard.vue` renders children and glass effect class.
- Test `NxAiPulse.vue` toggles state correctly.
- Test `LoadingSpinner.vue` shows spinner during loading.

## Pinia Store Tests
- `useAuthStore` login/logout flow.
- `useContacts` CRUD actions.
- `useWorkflows` workflow creation/validation.
- `useEchoStore` channel subscription/unsubscription.

## Feature Tests (API Level)
- Register/login returns token.
- Create contact returns avatar_url and last_seen_at.
- Workflow creation with valid steps.
- Task status transitions.
- Conversation message broadcast via WebSocket.
- AI request via `/api/ai/request` returns response.

## Integration Tests
- Navigation from Contacts to ContactDetail preserves state.
- Real‑time job progress updates via Echo.
- Toast notifications appear on job completion.
- Mobile header toggle on breakpoint change.

## E2E / UI Tests
- Login flow (register → login → dashboard).
- Contact creation → detail view → edit → delete.
- Workflow creation with drag‑drop (phase‑1).
- Message send/receive in conversation.
- Theme switch persists across reload.

## Accessibility Tests
- All interactive elements have `aria-label`.
- Focus ring visible on tab.
- Screen reader announces live regions.
- Color contrast meets WCAG AA.