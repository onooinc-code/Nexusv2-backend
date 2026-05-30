# Page Layout Designs

## Dashboard (DashboardView.vue)
- Top bar with branding, user menu, and connection status.
- Left rail (`NxNavRail.vue`) with hub links.
- Main area renders `DashboardCharts.vue` and summary cards.

## Contacts (ContactsView.vue)
- Grid list of `NxContactCard3D.vue` with 3‑pane detail on click.
- `ContactList.vue` with infinite scroll skeleton.

## Contact Detail (ContactDetail.vue)
- Hero card with 3D avatar.
- Tabs for notes, timeline, analytics.
- `NxRelationTimeline.vue` for interaction history.

## Workflows (WorkflowBuilder.vue)
- Drag‑drop canvas skeleton with phase‑1 nodes.
- Right sidebar for node configuration.

## Agents (AgentsView.vue)
- Team‑style grid with `NxAgentBadge.vue`.
- Queue panel via `NxQueuePill.vue`.

## Logs (LogsView.vue)
- Flat list with `LogStream.vue`.
- Modal viewer `NxLogViewerModal.vue`.

## Settings (SettingsView.vue)
- Form sections with `NxActionButton.vue` save.
- Theme switcher (`NxThemeSwitcher.vue`).

## AI Models (AIModelsView.vue)
- Provider cards (`NxAddProviderForm.vue`).
- Health modal (`NxProviderHealthModal.vue`).

## Conversations (ConversationsView.vue)
- Thread list with unread badge.
- Chat interface via `PeopleChat.vue`.

## Tasks (TasksView.vue)
- List with status pills.
- Detail in `TaskDetail.vue`.

## Memory (MemoryView.vue)
- Graph viewer `MemoryViewer.vue`.
- Mini graph `NxMemoryMiniGraph.vue`.

## General Patterns
- Breadcrumbs for hierarchy.
- Persistent loading skeleton across navigation.
- Toast notifications via `Toast.vue`.