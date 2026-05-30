# Project Structure

```
/var/www/os/ns/
├── app/
│   ├── Http/
│   │   ├── Controllers/       # Thin API controllers
│   │   ├── Middleware/
│   │   └── Requests/
│   ├── Services/               # Business logic
│   ├── Models/                 # Eloquent models
│   ├── Events/                 # Domain events
│   ├── Listeners/              # Event handlers
│   ├── Jobs/                   # Async jobs
│   └── Repositories/           # Data access
├── config/
├── database/
│   └── migrations/
├── resources/
│   └── js/
│       ├── app.js
│       ├── router/index.js
│       ├── services/apiClient.js
│       ├── stores/
│       │   ├── useAuthStore.js
│       │   ├── useContacts.js
│       │   ├── useWorkflows.js
│       │   ├── useChat.js
│       │   ├── useNotificationStore.js
│       │   ├── useSystem.js
│       │   └── useEchoStore.js
│       ├── composables/
│       │   ├── useHaptic.js
│       │   ├── useLiveChat.js
│       │   ├── useOfflineQueue.js
│       │   └── useEcho.js
│       ├── Components/
│       │   ├── Nx*.vue
│       │   ├── Card.vue
│       │   ├── ContactList.vue
│       │   ├── ConversationList.vue
│       │   ├── DashboardCharts.vue
│       │   ├── LoadingSpinner.vue
│       │   ├── SkeletonLoader.vue
│       │   ├── Toast.vue
│       │   └── ...
│       └── Pages/
│           ├── Auth/
│           │   ├── LoginPage.vue
│           │   └── RegisterPage.vue
│           ├── DashboardView.vue
│           ├── ContactsView.vue
│           ├── ContactDetail.vue
│           ├── ContactAnalytics.vue
│           ├── ConversationsView.vue
│           ├── PeopleChat.vue
│           ├── WorkflowsView.vue
│           ├── WorkflowBuilder.vue
│           ├── TasksView.vue
│           ├── TaskCreating.vue
│           ├── TaskDetail.vue
│           ├── AgentsView.vue
│           ├── AIModelsView.vue
│           ├── MemoryView.vue
│           ├── LogsView.vue
│           ├── TemplateLibrary.vue
│           └── SettingsView.vue
├── Docs/
│   ├── FrontEnd/
│   │   ├── UI_UX_Specs.md
│   │   ├── Mobile_UI_Specs.md
│   │   ├── Page_Layouts.md
│   │   ├── API_Documentation.md
│   │   ├── Test_Scenarios.md
│   │   ├── Architecture_Overview.md
│   │   └── Project_Structure.md
│   ├── First_Version_Docs/
│   ├── Updates_Documents_Implementations/
│   └── ...
├── package.json
├── vite.config.js
├── tailwind.config.js
└── README.md
```

## Key Conventions
- PascalCase for component names.
- kebab-case for file names.
- kebab-case for route paths.
- Store names match domain (useContacts, useWorkflows).
- Services live under `app/Services/{HubName}/`.
- Events named `{Action}{Subject}`.