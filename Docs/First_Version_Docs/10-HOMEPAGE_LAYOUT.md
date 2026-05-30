# Homepage Layout

## Purpose
Define the Homepage layout that hosts the primary conversation tabs (`HedraSouly` and `PeopleConnect`) plus quick access to hubs, scheduler, notifications and common actions.

## High-level structure
- Top navigation: global search, user menu, quick-create, environment selector.
- Left rail: hub shortcuts (Agents, Scheduler, Analytics, Integrations), collapsed state supported.
- Center area: primary work surface — Tabs + Conversation area (default to HedraSouly).
- Right rail: contextual panel for session details, quick tools, memory snippets and active workflows.
- Bottom bar: global composer shortcuts, status (queue length, worker health), quick toggles (dark/light).

## Conversation area (center)
- Tab bar: two primary tabs labeled `HedraSouly` and `PeopleConnect` with unread badges and pinned sessions.
- Session pane: left side of center area shows SessionList when viewport >= 1024px; for smaller screens it collapses into a drawer.
- ChatWindow: main message area with infinite scroll, new message indicator, and jump-to-latest button.

## Scheduler quick widget
- Compact cron-builder widget accessible from Homepage; allows quick schedule creation for messages or workflows.
- Shows next-run preview, creator, and a `Run now` button for manual triggers (requires permission).

## Notifications & Alerts
- Global notification center in top-right; persistent alerts (retryable failures, rate-limits) appear as toasts and log entries.

## Responsive behavior
- Mobile: tab-first experience, session list accessible via bottom sheet; composer collapses to single-line with expand affordance.
- Tablet: two-column layout where right rail becomes a collapsible drawer.

## Performance & Loading
- Lazy-load heavy panels (RightPanel, Analytics) on demand.
- Use skeleton loaders for message streaming and for remote memory lookups.

## Accessibility
- Logical heading structure, skip-links to main conversation, ARIA roles for tabs and message list.

## Implementation tasks
- Build `Homepage` layout component with slots for left-rail, center, right-rail and footer.
- Implement responsive rules and drawer behaviors.
- Add `SchedulerWidget` component that calls SchedulerHub API and shows local optimistic UI for pending jobs.
- Add e2e tests for mobile & desktop tab workflows.
