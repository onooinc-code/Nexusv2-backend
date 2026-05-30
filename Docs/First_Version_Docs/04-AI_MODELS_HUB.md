# 04 - AiModelsHub

## Purpose

The AiModelsHub centralizes model provider orchestration, model selection, routing, key management, and cost tracking for Nexus AI workloads.
It ensures every request uses the right model and provider combination, supports fallbacks, and captures usage metadata for observability and billing.

## Scope

- Provider discovery and selection
- Fallback and retry strategies across multiple providers
- Model capability mapping and decoupling from business-level intent
- API key rotation and provider credential management
- Cost estimation, telemetry, and usage accounting
- Request shaping for performance, cost, and compliance

## Core Responsibilities

1. Provider orchestration
2. Model selection and routing
3. Key lifecycle and credential management
4. Cost and instrumentation capture
5. Provider health and availability monitoring

## Architecture

AiModelsHub is implemented as a service layer with the following subcomponents:

- `ProviderRegistry`
- `ModelCatalog`
- `RoutingEngine`
- `FallbackChain`
- `KeyManager`
- `UsageTracker`
- `ProviderHealthMonitor`

### ProviderRegistry

Maintains provider metadata and integrates with provider-specific adapters.

Responsibilities:

- Register available providers and supported model families
- Expose provider capabilities, availability, and latency profiles
- Support provider feature flags, region restrictions, and compliance labels

### ModelCatalog

Defines canonical model capabilities for Nexus.

Responsibilities:

- Map business-level intents to model families (e.g. `summarization`, `completion`, `embedding`, `multimodal`)
- Maintain provider-agnostic model profiles and quality tiers
- Support custom model aliases and usage policies
- Enable versioned model selection rules

### RoutingEngine

Determines the chosen provider/model for each request.

Responsibilities:

- Evaluate request attributes: intent, cost sensitivity, latency tolerance, security class
- Consult provider weights, quotas, and policy rules
- Route to the optimal provider/model pair
- Allow explicit override commands from higher-level workflows when needed

### FallbackChain

Implements soft failover for model requests.

Responsibilities:

- Define ordered fallback sequences across providers and models
- Retry requests on provider failure, rate limits, or errors
- Escalate degraded service to alternate tiers when necessary
- Preserve request metadata through fallback attempts

### KeyManager

Handles API keys and provider credentials.

Responsibilities:

- Store provider credentials securely in encrypted vault or KMS-backed storage
- Rotate keys on schedule or on provider request
- Support per-tenant/provider key scoping
- Expose safe retrieval interfaces for runtime adapters
- Revoke or retire credentials cleanly

### UsageTracker

Captures cost and consumption metrics.

Responsibilities:

- Record request costs, prompt tokens, completion tokens, embeddings usage, and output tokens
- Link usage to source workflows, tenants, and feature flags
- Emit telemetry events for billing and quota enforcement
- Provide summary reports for cost optimization and model selection decisions

### ProviderHealthMonitor

Tracks provider availability and performance.

Responsibilities:

- Poll provider health endpoints, status APIs, and rate-limit signals
- Maintain provider scorecards for latency, reliability, and congestion
- Feed health metrics into routing and fallback decisions
- Support provider out-of-service declarations

## API Contract

### `POST /ai-models/route`

Request body:

- `intent`: string
- `workspace_id`: string
- `input_type`: string
- `features`: array<string>
- `cost_profile`: enum `low`,`medium`,`high`
- `latency_profile`: enum `fast`,`balanced`,`safe`
- `security_class`: enum `standard`,`sensitive`,`restricted`
- `provider_override` (optional)
- `model_override` (optional)

Response:

- `provider_id`
- `model_id`
- `endpoint`
- `input_schema`
- `expected_cost`
- `reason`
- `fallback_chain`

### `POST /ai-models/usage`

Request body:

- `request_id`
- `provider_id`
- `model_id`
- `workflow_id`
- `workspace_id`
- `token_usage` object
- `cost_usd`
- `duration_ms`
- `status`

Response:

- `record_id`
- `status`

### `GET /ai-models/providers`

Response:

- `providers`: array of provider metadata
- `health_status`
- `supported_models`

## Model Selection Strategy

### Capability-driven selection

Model selection is guided by a capability-first approach rather than hard-coded provider names.
The AiModelsHub offers a canonical capability layer that maps to providers based on quality, speed, and cost.

### Intent mapping

Common modeling intents:

- `text_completion`
- `text_summarization`
- `question_answering`
- `conversation`
- `text_embedding`
- `image_generation`
- `multimodal_analysis`

Each intent maps to a preferred set of provider/model families.

### Policies

Policy rules may include:

- Provider exclusions for compliance or geo restrictions
- Tiered cost budgets and spend caps
- Model class restrictions for sensitive or regulated workflows
- Prioritized fallbacks for mission-critical requests

## Provider Orchestration

### Provider ranking

Providers are ranked by:

- capability match
- historical success rate
- current health score
- cost per unit
- policy restrictions

### Dynamic weighting

Weight decisions may adjust at runtime based on:

- provider quotas and rate limits
- observed latency spikes
- billing thresholds
- custom tenant preferences

### Fallback behavior

Fallbacks are triggered when:

- a provider returns an error or timeout
- rate limit or quota is exceeded
- request cost exceeds configured threshold
- response quality is unacceptable

### Fallback chain properties

- preserve request context
- avoid repeated retries against the same failing provider
- fall back to lower-cost or higher-availability models as appropriate
- expose fallback decisions for audit and debugging

## Cost Tracking and Reporting

### Cost metadata

Captured fields include:

- provider
- model
- request type
- tokens in/out
- request duration
- cost estimate and actual cost
- workflow and tenant context

### Reporting

- Provide usage aggregations by day, workspace, workflow, and provider
- Support anomaly detection for spikes in model spend
- Feed cost signals into routing decisions and notification channels

## Security and Compliance

- Credentials stored encrypted and retrieved only when needed
- Access to the AiModelsHub restricted to authenticated services and workflows
- Sensitive model usage may require additional auditing and approval
- Provider configuration changes are subject to review and change control

## Resilience and Observability

- Emit route decisions and health signals to central observability
- Capture fallback events and degraded-mode activations
- Monitor provider error rates, response times, and quota exhaustion
- Support graceful degradation to offline or cached responses when external provider access is unavailable

## Implementation Notes

- Prefer adapter-based provider integrations for clean abstraction
- Keep model capability definitions declarative and versioned
- Use a unified service interface for routing and cost capture
- Support both synchronous route decision APIs and async usage reporting

## Example Workflow

1. Workflow submits routing request for `text_summarization` with `cost_profile=low`.
2. `AiModelsHub` evaluates provider health and selects a cost-efficient model.
3. Request metadata is recorded and a safe provider credential is acquired.
4. Model call proceeds through the provider adapter.
5. `UsageTracker` records final token usage and cost.
6. If the provider fails, the `FallbackChain` routes to the fallback provider with preserved context.
