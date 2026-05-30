# 📘 AiModels Hub - Master Blueprint & Relational Specification (v2.0 - Dynamic Orchestration)

## 1. 🎯 Executive Definition & Core Philosophy

**Definition:** 
The `AiModelsHub` is the central orchestration layer and neurological core of the Nexus platform. It completely decouples business-level cognitive intents (e.g., *summarization, intent detection, conversational generation*) from underlying AI providers. Moving beyond hardcoded integrations, the Hub operates as a **Fully Dynamic AI Gateway**. Providers and models are injected, configured, and synced at runtime, acting as a "bring-your-own-API" orchestration engine.

**Core Philosophy & Architectural Principles:**
*   **Dynamic Provider Injection:** Nexus is unbound by static SDKs. Any LLM provider (OpenAI-compatible or custom REST) can be added via the UI by defining its Base URL, Auth method, and Endpoints.
*   **Intent-Based Deterministic Routing:** The system doesn't guess which model to use. The user (Hédra) explicitly defines a default `Provider` + `Model` pairing for every distinct cognitive `Intent`.
*   **Resilience & Graceful Degradation:** Driven by a robust `FallbackChain`, the hub guarantees uninterrupted service through automatic failovers and circuit breakers, even if a dynamically added provider goes offline.
*   **Cost & Usage Supremacy:** Every single token is tracked, budgeted, and optimized regardless of the external provider used. 

---

## 2. 📋 Exhaustive Requirements

### Functional Requirements
*   **Dynamic Provider Registry (CRUD):** Administrators can add, edit, or delete AI providers at runtime. A provider definition includes: `Provider Name`, `Base API URL`, `Models Fetch Endpoint` (to dynamically pull available models), and `Generate URL` (for inference requests).
*   **Automated Model Synchronization:** The system periodically hits the `Models Fetch Endpoint` of registered providers to update the internal catalog of available models, retiring deprecated ones and surfacing new ones.
*   **Payload Adapters/Mapping:** Providers support a standard payload format (e.g., OpenAI chat completions format) or customizable JSON payload templates to handle structural differences between providers.
*   **Intent-Based Routing Matrix:** A configuration matrix allowing Hédra to map specific intents (e.g., `extract_memory`, `generate_chat`, `analyze_sentiment`) to a preferred `Provider` and `Model` combination.
*   **Dynamic Fallback & Retries:** Automated failover to a secondary Intent-Model mapping upon rate-limit (429), server error (5xx), or timeout (>30s) thresholds.
*   **Key Lifecycle Management:** Secure storage of API keys (`Bearer` or custom headers) per provider, supporting multiple keys for load-balancing.

### Non-Functional Requirements
*   **Latency Targets:** Standard generative requests MUST complete in < 2.0s (P95). Vector embedding target latency is < 100ms.
*   **Dynamic Extensibility:** Adding a new provider must require **zero** code deployment; it is entirely database and configuration-driven.

### UI/UX Requirements
*   **Provider Management Dashboard:** A dedicated UI inside SettingsHub/AiModelsHub to input Base URLs, test connections, and map API keys.
*   **Intent Routing Matrix UI:** A visual grid/table where Hédra selects drop-downs for Provider and Model for every system intent.
*   **Real-Time Token & Cost Meter:** Live fractional cost estimates based on dynamically updated per-token pricing attached to the synced models.

### Security & Privacy Constraints
*   **Encryption at Rest:** All API keys and custom auth headers are encrypted using AES-256 in the database.
*   **SSRF Protection:** Base URLs provided dynamically must be validated to prevent Server-Side Request Forgery (e.g., blocking localhost or internal IP ranges).

---

## 3. ⚙️ Technical & Architectural Details

### Database Schema & Storage
The Hub relies on structured MySQL tables to support dynamic configurations.

*   **Table: `ai_providers`** (Updated for Dynamic Architecture)
    *   `id` (UUID, PK)
    *   `name` (VARCHAR) - e.g., "Groq", "OpenRouter", "Local_Ollama"
    *   `base_url` (VARCHAR) - e.g., `https://api.groq.com/openai/v1`
    *   `models_fetch_endpoint` (VARCHAR) - e.g., `/models`
    *   `generate_endpoint` (VARCHAR) - e.g., `/chat/completions`
    *   `auth_header_format` (VARCHAR) - e.g., `Bearer {key}` or `x-api-key: {key}`
    *   `payload_format` (ENUM: 'openai_standard', 'anthropic_standard', 'custom')
    *   `is_active` (BOOLEAN)
*   **Table: `ai_models`** (Dynamically Populated)
    *   `id` (UUID, PK)
    *   `provider_id` (UUID, FK -> ai_providers.id)
    *   `model_name` (VARCHAR) - e.g., `llama3-70b-8192`
    *   `context_window` (INT)
    *   `input_cost_per_m` (DECIMAL)
    *   `output_cost_per_m` (DECIMAL)
    *   `last_synced_at` (DATETIME)
*   **Table: `ai_api_keys`**
    *   `id` (UUID, PK)
    *   `provider_id` (UUID, FK -> ai_providers.id)
    *   `key_hash` (VARCHAR) - Encrypted payload.
*   **Table: `intent_routing`** (New Configuration Matrix)
    *   `id` (UUID, PK)
    *   `intent_name` (VARCHAR, UNIQUE) - e.g., `summarization`, `sentiment_analysis`
    *   `default_provider_id` (UUID, FK -> ai_providers.id)
    *   `default_model_id` (UUID, FK -> ai_models.id)
    *   `fallback_provider_id` (UUID, FK) - Nullable
    *   `fallback_model_id` (UUID, FK) - Nullable

### Service & Logic Layers
*   **`DynamicProviderRegistry`:** Responsible for CRUD operations on providers and executing the `syncModels()` method by hitting the provider's `models_fetch_endpoint`.
*   **`IntentRoutingEngine`:** Intercepts incoming requests, reads the requested `intent`, queries the `intent_routing` table, and resolves the precise `base_url` and `generate_endpoint` required.
*   **`PayloadAdapterFactory`:** Formats the Nexus standard prompt structure into the specific JSON payload required by the dynamic provider (defaulting to the OpenAI Chat Completion spec, which is widely adopted by Groq, OpenRouter, Ollama, etc.).

### API Surface
*   **`POST /api/v1/ai/providers`** - Create a new dynamic provider (Expects `name`, `base_url`, `models_fetch_endpoint`, `generate_endpoint`).
*   **`POST /api/v1/ai/providers/{id}/sync-models`** - Triggers a fetch to populate `ai_models` table.
*   **`PUT /api/v1/ai/intents/routing`** - Update the default provider/model for a specific intent.
*   **`POST /api/v1/ai/request`**
    *   *Payload:* `{ intent: string, prompt: object, context_data: object }`
    *   *Response:* Resolves internally via `intent_routing` table, fetches the key, calls the dynamic URL, and returns standardized response + usage.

---

## 4. 🕸️ The Relational Matrix (Deep System Integration)

*   **WorkflowsAndTasksHub:**
    *   *Interaction:* Workflows trigger specific intents (e.g., a "Summarize Meeting" workflow step sends `intent: 'summarization'`). It no longer cares about *which* model is used; AiModelsHub handles the lookup dynamically.
*   **AgentsHub:**
    *   *Interaction:* Agents can have specific routing overwrites. An agent persona might enforce `intent: 'agent_reflection'`, which is mapped in the DB to an advanced reasoning model (e.g., a dynamic OpenAI-compatible deep-think model).
*   **SettingsHub:**
    *   *Interaction:* Owns the UI and caching of the `intent_routing` matrix and provider configurations. When Hédra updates an intent mapping in the Settings UI, SettingsHub invalidates the Redis cache used by AiModelsHub.
*   **MemoryHub:**
    *   *Interaction:* Sends `intent: 'semantic_embedding'`. AiModelsHub looks up the provider assigned to embeddings (e.g., a local Ollama embedding endpoint) and returns the vector array.
*   **LogsHub:**
    *   *Interaction:* Logs the exact dynamically resolved URL, Provider ID, Model ID, and Token counts for billing and traceability.

---

## 5. 🚦 Edge Cases, Constraints & Business Rules

*   **Edge Case - Provider Model Deprecation:** If a dynamic provider silently drops a model, the next `syncModels()` job will flag the model as inactive. If `intent_routing` is using an inactive model, the `IntentRoutingEngine` instantly defaults to the `fallback_model_id` and alerts Hédra via NotificationsHub.
*   **Edge Case - Payload Mismatch:** If a custom provider does not follow standard OpenAI/Anthropic schemas, the request will fail. The system must catch `400 Bad Request` schema errors, log the exact expected vs. actual payload, and trigger the fallback chain.
*   **Constraint - Intent Fallback:** If an intent is requested that is NOT mapped in the `intent_routing` table, the system must fallback to a global default `system_default` intent mapping to prevent execution failure.

***
 