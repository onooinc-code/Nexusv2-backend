# UP-004_Task-01: NxNavRail.vue — Collapsible Navigation Rail

## Feature ID
F-NAV-01

## Objective
Create `NxNavRail.vue` — a collapsible navigation rail that replaces `Navigation.vue` with icon-only collapsed state (80px) and expanded state (240px).

## Specifications

### UI/UX Requirements
- Width: `80px` collapsed, `240px` expanded
- Transition: `width 250ms cubic-bezier(0.4, 0, 0.2, 1)`
- Active hub: left border `3px solid #007AFF`, background `rgba(0,122,255,0.05)`
- Glass: `background: rgba(22,27,34,0.7); backdrop-filter: blur(12px); border-right: 1px solid rgba(255,255,255,0.1)`
- RTL: `border-right` → `border-left`

### Hub Icons (Top Section)
- Nexus (MessageSquare)
- Agents (Bot)
- Memory (Brain)
- Contacts (Users)
- Workflows (Workflow)
- Settings (Settings)

### Bottom Section
- User profile avatar
- Settings gear

### State Management
- `collapsed` state persisted in `localStorage` key `nexus-nav-rail-collapsed`
- Mobile (`< 768px`): rail hidden, replaced by MobileFooter

## Technical Implementation

### File
`resources/js/Components/NxNavRail.vue`

### Props
None

### Emits
- `hub-change` — when hub is selected

### State
- `collapsed: Boolean` (ref)
- `activeHub: String` (computed from route)

### Functions
- `toggleCollapsed()` — toggle rail width
- `navigateToHub(hubName)` — router push

## Dependencies
- `vue-router`
- `lucide-vue-next`

## Verification
- [ ] Build passes (`npm run build`)
- [ ] Collapse/expand works with smooth transition
- [ ] State persists in localStorage
- [ ] Hub navigation works
- [ ] Mobile hidden state works