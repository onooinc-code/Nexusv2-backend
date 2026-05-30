# State Management (Pinia Stores)

## useAuthStore
- `login(credentials)` – Sanctum token auth.
- `logout()` – Clear token.
- `user` – Current user object.
- `token` – Auth token.

## useContacts
- `fetchContacts()` – List contacts with eager loading.
- `fetchContact(id)` – Single contact detail.
- `createContact(data)` – Create contact.
- `updateContact(id, data)` – Update contact.
- `deleteContact(id)` – Delete contact.
- `contacts` – Array of contacts.
- `selectedContact` – Currently selected contact.

## useWorkflows
- `fetchWorkflows()` – List workflows.
- `createWorkflow(data)` – Create workflow.
- `updateWorkflow(id, data)` – Update workflow.
- `deleteWorkflow(id)` – Delete workflow.
- `workflows` – Array of workflows.
- `selectedWorkflow` – Active workflow.

## useTasks
- `fetchTasks()` – List tasks.
- `createTask(data)` – Create task.
- `updateTask(id, data)` – Update task status.
- `deleteTask(id)` – Delete task.
- `tasks` – Array of tasks.
- `selectedTask` – Active task.

## useChat
- `fetchConversations()` – List conversations.
- `fetchMessages(conversationId)` – Messages for conversation.
- `sendMessage(conversationId, content)` – Send message.
- `conversations` – Array of conversations.
- `messages` – Map of conversationId → messages.

## useWorkflows
- `fetchAgents()` – List agents.
- `createAgent(data)` – Create agent.
- `updateAgent(id, data)` – Update agent.
- `agents` – Array of agents.

## useNotificationStore
- `addNotification(type, message)` – Push toast.
- `notifications` – Array of active notifications.
- `dismiss(id)` – Dismiss notification.

## useSystem
- `isOnline` – Connection status.
- `isMobile` – Breakpoint detection.
- `toggleTheme()` – Theme toggle.

## useEchoStore
- `subscribe(channel, event, callback)` – Subscribe to Echo channel.
- `unsubscribe(channel)` – Unsubscribe.
- `echo` – Echo instance.
