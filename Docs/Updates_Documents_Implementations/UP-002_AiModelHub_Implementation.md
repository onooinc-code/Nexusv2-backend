# 🚀 UPDATE BLUEPRINT: UP-002 AiModelHub Implementation

## 1. Meta & Pre-flight Analysis
- **Features & Details:** Complete implementation of AI Models Hub with dynamic provider management, intent-based routing, and secure API key handling
- **Project Context & Versions:** Laravel PHP application with MySQL database, Redis caching
- **Regression Check:** This implementation replaces hardcoded AI provider services with dynamic database-driven configuration. Existing direct provider calls in workflows/agents will need to be updated to use the new intent-based API.

## 2. Feature Specifications (Per Feature)

### Feature Name: Database Schema Updates
- **Specs & Requirements:**
  - Create `ai_providers` table with UUID PK, name, base_url, models_fetch_endpoint, generate_endpoint, auth_header_format, payload_format, is_active
  - Update `ai_models` table: change id to UUID, add provider_id FK, add context_window, input_cost_per_m, output_cost_per_m, last_synced_at; remove provider, external_id, description, capabilities, metadata, status columns
  - Create `ai_api_keys` table with UUID PK, provider_id FK, key_hash (encrypted), and appropriate indexes
  - Create `intent_routing` table with UUID PK, intent_name (unique), default_provider_id FK, default_model_id FK, fallback_provider_id FK (nullable), fallback_model_id FK (nullable)
- **Backend Readiness:** 
  - Existing `ai_models` table needs modification
  - No `ai_providers`, `ai_api_keys`, or `intent_routing` tables exist
- **Required Libraries:** Laravel migration system, UUID package (ramsey/uuid)
- **Class/Component Names:** Migration classes
- **Functions to Modify/Create:** up() and down() methods in migration files

### Feature Name: API Endpoint Implementation
- **Specs & Requirements:**
  - Implement POST /api/v1/ai/providers for provider CRUD operations
  - Implement POST /api/v1/ai/providers/{id}/sync-models for model synchronization
  - Implement PUT /api/v1/ai/intents/routing for intent routing updates
  - Implement POST /api/v1/ai/request for unified AI request handling
- **Backend Readiness:**
  - Routes file exists but missing these specific endpoints
  - Controllers need to be created
- **Required Libraries:** Laravel routing, validation
- **Class/Component Names:** AiProviderController, AiRequestController
- **Functions to Modify/Create:** store, update, syncModels, routeIntent, handleRequest methods

### Feature Name: Core Services Development
- **Specs & Requirements:**
  - Create DynamicProviderRegistry service for provider CRUD and syncModels() execution
  - Create IntentRoutingEngine service for intent-based provider/model resolution
  - Create PayloadAdapterFactory service for formatting requests to provider-specific payloads
  - Implement Redis caching for intent routing and provider configurations with invalidation mechanism
- **Backend Readiness:**
  - No existing services match these specifications
  - Some provider services exist but are hardcoded
- **Required Libraries:** Laravel cache (Redis), HTTP client, encryption
- **Class/Component Names:** DynamicProviderRegistry, IntentRoutingEngine, PayloadAdapterFactory
- **Functions to Modify/Create:** 
  - DynamicProviderRegistry: getProvider, syncModels, registerProvider
  - IntentRoutingEngine: resolveIntent, getDefaultModel, getFallbackOptions
  - PayloadAdapterFactory: adaptPayload, adaptResponse

### Feature Name: Security & Resilience Features
- **Specs & Requirements:**
  - Implement AES-256 encryption for API keys at rest
  - Add SSRF protection for dynamic base URLs (block localhost/internal IPs)
  - Implement fallback chain with circuit breakers for 429/5xx/timeout scenarios
  - Add usage tracking and cost meter functionality
- **Backend Readiness:**
  - No encryption for API keys currently (stored in plaintext env/config)
  - No SSRF protection
  - No circuit breaker implementation
- **Required Libraries:** Laravel encryption, HTTP client with middleware, circuit breaker package
- **Class/Component Names:** EncryptedApiKeyStorage, SsrfProtectionMiddleware, CircuitBreaker
- **Functions to Modify/Create:** encrypt/decrypt methods, validateUrl, executeWithFallback

### Feature Name: Integration & Refactoring
- **Specs & Requirements:**
  - Refactor existing AI provider services to implement a common interface and be dynamically loadable
  - Update workflows, agents, MemoryHub, and LogsHub to use intent-based requests instead of direct provider calls
  - Implement real-time token usage and cost calculation
  - Add notification alerts for model deprecation and fallback events
- **Backend Readiness:**
  - Existing provider services (OpenAIProvider, GroqProvider, etc.) need refactoring
  - Workflows/agents currently call providers directly
- **Required Libraries:** Laravel service container, event system
- **Class/Component Names:** AiProviderInterface, refactored provider classes
- **Functions to Modify/Create:** __construct, generateText, embeddings methods in providers; update service calls in workflows/agents

### Feature Name: Testing & Validation
- **Specs & Requirements:**
  - Create unit tests for all new services and API endpoints
  - Test dynamic provider addition without code deployment
  - Verify intent-based routing works correctly
  - Validate fallback chain functionality
  - Confirm SSRF protection effectiveness
- **Backend Readiness:**
  - Some basic tests may exist but not for these specific features
- **Required Libraries:** PHPUnit, Laravel testing utilities
- **Class/Component Names:** Test classes for each service/controller
- **Functions to Modify/Create:** test methods for each functionality

## 3. Testing Strategy
- **Automated Testing:** PHPUnit feature and unit tests for all new endpoints, services, and security features
- **Manual Testing Steps:**
  1. Add a new provider via API and verify it's stored in database
  2. Sync models for a provider and verify models table is populated
  3. Set up intent routing and verify requests route correctly
  4. Test fallback chain when primary provider fails
  5. Verify API keys are encrypted in database
  6. Test SSRF protection blocks internal URLs
  7. Verify cost tracking works correctly

## 4. 📋 MASTER EXECUTION CHECKLIST
*AI MUST update this list as tasks are completed.*
- [x] Task 1: Database Schema Updates
- [x] Task 2: API Endpoint Implementation
- [x] Task 3: Core Services Development
- [x] Task 4: Security & Resilience Features
- [x] Task 5: Integration & Refactoring
- [x] Task 6: Testing & Validation