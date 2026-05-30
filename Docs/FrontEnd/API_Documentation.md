# API Documentation

## REST Endpoints
All endpoints live under `routes/api.php` and return JSON.

### Authentication
- POST `/api/login` – Laravel Sanctum token auth.
- POST `/api/register` – User registration.

### Contacts
- GET  `/api/contacts` – List contacts (with avatar_url, last_seen_at).
- GET  `/api/contacts/{id}` – Contact detail.
- POST `/api/contacts` – Create contact.
- PUT  `/api/contacts/{id}` – Update contact.
- DELETE `/api/contacts/{id}` – Delete contact.

### Workflows
- GET  `/api/workflows` – List workflows.
- POST `/api/workflows` – Create workflow.
- PUT  `/api/workflows/{id}` – Update workflow.
- DELETE `/api/workflows/{id}` – Delete workflow.

### Tasks
- GET  `/api/tasks` – List tasks.
- POST `/api/tasks` – Create task.
- PUT  `/api/tasks/{id}` – Update task.
- DELETE `/api/tasks/{id}` – Delete task.

### Agents
- GET  `/api/agents` – List agents.
- POST `/api/agents` – Create agent.
- PUT  `/api/agents/{id}` – Update agent.

### Conversations
- GET  `/api/conversations` – List conversations.
- GET  `/api/conversations/{id}` – Conversation detail.
- POST `/api/conversations` – Create conversation.

### AI Models
- GET  `/api/ai/models` – List models.
- POST `/api/ai/request` – Intent‑based AI request.

### Logs
- GET  `/api/logs` – List logs.
- GET  `/api/logs/{id}` – Log detail.

## WebSocket (Reverb)
- Channel: `conversation.{id}` – Message broadcast.
- Channel: `job.{id}` – Job progress.
- Channel: `notification` – Toast notifications.

## Error Handling
- 4xx returns `{ error: string }`.
- 5xx shows generic `500` with `NxOfflineBanner.vue` fallback.

## Response Formats
All success responses wrap data in `{ data: [...] }` or `{ data: {...} }`.