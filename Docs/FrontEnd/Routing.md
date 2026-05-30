# Routing & Navigation

## Route Definitions (router/index.js)
```js
{
  path: '/',
  name: 'Dashboard',
  component: () => import('@/Pages/DashboardView.vue'),
},
{
  path: '/contacts',
  name: 'Contacts',
  component: () => import('@/Pages/ContactsView.vue'),
},
{
  path: '/contacts/:id',
  name: 'ContactDetail',
  component: () => import('@/Pages/ContactDetail.vue'),
},
{
  path: '/workflows',
  name: 'Workflows',
  component: () => import('@/Pages/WorkflowsView.vue'),
},
{
  path: '/workflows/create',
  name: 'WorkflowCreate',
  component: () => import('@/Pages/TaskCreating.vue'),
},
{
  path: '/tasks',
  name: 'Tasks',
  component: () => import('@/Pages/TasksView.vue'),
},
{
  path: '/tasks/:id',
  name: 'TaskDetail',
  component: () => import('@/Pages/TaskDetail.vue'),
},
{
  path: '/agents',
  name: 'Agents',
  component: () => import('@/Pages/AgentsView.vue'),
},
{
  path: '/ai-models',
  name: 'AIModels',
  component: () => import('@/Pages/AIModelsView.vue'),
},
{
  path: '/memory',
  name: 'Memory',
  component: () => import('@/Pages/MemoryView.vue'),
},
{
  path: '/logs',
  name: 'Logs',
  component: () => import('@/Pages/LogsView.vue'),
},
{
  path: '/conversations',
  name: 'Conversations',
  component: () => import('@/Pages/ConversationsView.vue'),
},
{
  path: '/chat/:id',
  name: 'Chat',
  component: () => import('@/Pages/PeopleChat.vue'),
},
{
  path: '/settings',
  name: 'Settings',
  component: () => import('@/Pages/SettingsView.vue'),
},
{
  path: '/template-library',
  name: 'TemplateLibrary',
  component: () => import('@/Pages/TemplateLibrary.vue'),
},
{
  path: '/analytics',
  name: 'ContactAnalytics',
  component: () => import('@/Pages/ContactAnalytics.vue'),
},
```

## Navigation Components
- `NxNavRail.vue` – Desktop left rail.
- `MobileHeader.vue` – Mobile top bar with hamburger.
- `Breadcrumbs.vue` – Hierarchical breadcrumb.
- `Navigation.vue` – Top bar wrapper.

## Protected Routes
All routes require auth token via Sanctum guard; unauthenticated users redirected to `/login`.