# 03 - Naming Conventions

## Purpose
Define consistent naming rules for code, documentation, and system concepts across Nexus.

---

## 1. General Naming
- Use clear, descriptive names
- Avoid abbreviations unless widely accepted
- Use nouns for classes, verbs for methods
- Keep names consistent across backend and frontend

## 2. Laravel Naming
- Models: singular PascalCase (`Contact`, `AgentTask`)
- Controllers: `PascalCaseController` (`ContactsHubController`)
- Services: `PascalCaseService` (`MemoryHubService`)
- Repositories: `PascalCaseRepository` (`LogRepository`)
- Jobs: `PascalCaseJob` (`ExecuteWorkflowStepJob`)
- Events: `PascalCase` (`ContactUpdated`)
- Resources: `PascalCaseResource`
- Requests: `PascalCaseRequest`

## 3. Vue Naming
- Components: `PascalCase` (`HubCard.vue`)
- Composables: `use` prefix (`useMemoryStore.js`)
- Stores: descriptive names (`useContactsStore`)
- Pages: `PascalCase` (`DashboardPage.vue`)
- Props: camelCase (`initialValue`)

## 4. API Naming
- Use RESTful verbs and nouns
- Use plural resource names (`/contacts`, `/agents`)
- Use `snake_case` for JSON field names
- Use `camelCase` for query params and JS objects if needed

## 5. Documentation Naming
- Use clear, hyphen-separated file names
- Group related files in folders by domain
- Use consistent section headers and metadata blocks

## 6. System Concepts
- Use `Hub` for major domains
- Use `Module` or `Unit` for subdomains within a hub
- Use `Engine` for decision logic components
- Use `Router` for request/command routing
- Use `Pipeline` for ordered processing flows
- Use `Builder` for object assembly
