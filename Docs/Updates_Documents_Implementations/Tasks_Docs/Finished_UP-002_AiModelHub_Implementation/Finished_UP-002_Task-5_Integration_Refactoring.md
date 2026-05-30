# 🎯 TASK: UP-002 - Task 5: Integration & Refactoring
- **Status:** 🔴 PENDING
- **Dependencies:** Tasks 1, 2, 3, and 4 must be finished first

## 1. Objective
Refactor existing AI provider services to implement a common interface, update workflows/agents to use intent-based requests, and implement real-time token usage and cost calculation.

## 2. Files to Create/Modify
- `app/Services/AiModelsHub/AiProviderInterface.php`: Create interface for AI providers
- `app/Services/AiModelsHub/OpenAIProvider.php`: Refactor existing OpenAI provider
- `app/Services/AiModelsHub/GroqProvider.php`: Refactor existing Groq provider
- `app/Services/AiModelsHub/AnthropicProvider.php`: Refactor existing Anthropic provider
- `app/Services/AiModelsHub/GoogleGeminiProvider.php`: Refactor existing Google Gemini provider
- `app/Hubs/AIModelsHub.php`: Update to use new intent-based system
- Various workflow and agent files: Update to use intent-based requests
- `app/Services/UsageCalculator.php`: Create service for real-time token usage and cost calculation

## 3. Implementation Steps
1. Create AiProviderInterface with standard methods (generateText, embeddings, etc.)
2. Refactor each existing provider service to implement this interface
3. Modify providers to receive configuration from DynamicProviderRegistry instead of hardcoded values
4. Update AIModelsHub hub to use IntentRoutingEngine and DynamicProviderRegistry
5. Update workflows, agents, MemoryHub, and LogsHub to use intent-based requests via AIModelsHub
6. Implement UsageCalculator service for real-time token usage and cost tracking
7. Add notification alerts for model deprecation and fallback events (using Laravel events/notifications)

## ✅ Final Verification
- [ ] Code is complete (No placeholders).
- [ ] Checked against existing project versions.
- [ ] Does not break dependent features.