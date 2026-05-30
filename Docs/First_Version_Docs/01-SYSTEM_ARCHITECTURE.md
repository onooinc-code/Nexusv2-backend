# 01 - System Architecture

What:
- High-level architecture for Nexus: hub-based, API-first, event-driven, and layered components (Routers, Engines, Pipelines, Builders, Services).

Why:
- Provide a single reference for developers and architects to implement hubs, integrations, and non-functional requirements.

How (summary):
- Hubs expose REST APIs and consume events. All inter-hub communication uses authenticated HTTP APIs or a secure event bus.
- Core layers:
  - Edge/API Layer: Public APIs, authentication, rate limiting, request routing
  - Orchestration Layer: Hub Controllers, Routers, Engines
  - Processing Layer: Pipelines, Builders, AI Model adapters
  - Storage Layer: SQL (structured/episodic), Redis (working/hot cache), Pinecone (semantic vectors), Archive DB / S3 (cold storage)
  - Integration Layer: External AI providers, WAHA (WhatsApp), Email/SMS gateways, Third-party webhooks

Components & responsibilities:
- AgentsHub: Orchestrates agent behaviors, sequences tasks through WorkflowsHub and AiModelsHub.

-  MemoryHub: Read/write/update across memory types; provides semantic search endpoints.
  
- ContactsHub: CRUD and canonicalization for contact profiles and beliefs.

- AiModelsHub: Provider adaptors, model selection, prompt templates, usage accounting.

- WorkflowsAndTasksHub: Define, schedule, and run multi-step Pipelines and long-running tasks.

- SettingsHub: Feature flags, routing rules, provider priorities.

- LogsHub: Centralized audit, telemetry, and trace collection.

- Supporting hubs: WebhookHub, NotificationHub, SchedulerHub (stateless where possible).

Integration points:
- Auth: OAuth2 / JWT for service-to-service, RBAC for user-level access.
- Observability: OpenTelemetry traces, structured JSON logs, metrics pushed to Prometheus.

Non-functional targets:
- Latency: P95 < 2s for standard queries; async queues for heavy extraction.
- Availability: 99.9% target, design for horizontal scaling per hub.
- Scalability: Stateless API layer; stateful storage (Redis, MySQL, Pinecone) scaled independently.

Dependencies:
- MySQL (core schema), Redis, Pinecone, external AI providers, message broker (Kafka/RabbitMQ optional).

Detailed sequences

Inbound message processing (sequence):
1. Client message arrives to the Edge/API (API Gateway) with signature headers.

2. API Gateway performs auth, rate-limit, and trace-id injection, then forwards to WebhookHub or appropriate hub.

3. `MessageRouter` validates and routes to `ContextAssemblyPipeline` in `WorkflowsAndTasksHub`.
4. `ContextAssemblyPipeline` calls `ContactsHub` (profile), `MemoryHub` (recent/context vectors), and `SettingsHub` (routing and tone rules).
5. Pipeline builds prompt via `PromptBuilder` and requests `AiModelsHub` for NLU + response generation.
6. Generated response is passed to `ResponseQualityEngine`, then to `ResponseDeliveryPipeline` which calls `NotificationHub` for outbound delivery.
7. Memory extraction runs async to `MemoryHub`, and events are emitted to `LogsHub` and event broker.

Agent execution (example):
1. `AgentsHub` receives a trigger to run an agent (user intent or scheduled task).
2. Agent runtime loads persona, tools, and permitted actions from `SettingsHub` and `AiModelsHub`.
3. Agent executes steps via `WorkflowsAndTasksHub` pipelines, calling Services for external I/O.
4. Results are persisted, audit events emitted, and long-running tasks update `Tasks` with status and checkpoints.

Deployment & scaling guidance
- Service topology: each Hub as a separate containerized service with its own deployment, horizontally scalable replica sets behind the API Gateway.
- Storage topology: MySQL primary with read replicas; Redis cluster for working memory with replica groups; Pinecone (or managed vector DB) for semantic stores.
- Broker: Kafka preferred for high-scale event streams; RabbitMQ acceptable for smaller deployments.
- API Gateway: single entry with auth, request shaping, and routing. Use service mesh (Istio/Linkerd) for mTLS and observability in-cluster.

Security & compliance
- Transport: TLS enforced for all inbound and intra-cluster traffic.
- Auth: Service-to-service via mTLS or short-lived JWTs; external clients via OAuth2.
- Secrets: Use a secrets manager (Vault, AWS Secrets Manager) and rotate keys regularly.
- Data protection: Field-level encryption for PII at rest, and strict redaction rules for logs.
- Privacy: Implement GDPR/PIPEDA erasure endpoints in `ContactsHub` and `MemoryHub` with audit trail in `LogsHub`.

Observability & SLOs
- Tracing: OpenTelemetry instrumentation across all hubs with propagated trace ids.
- Metrics: Expose Prometheus metrics; define SLOs (p95 latency <2s, error rate <0.1%).
- Logging: Structured JSON logs with request id, user id, and hub context; ship to centralized logs (ELK/Graylog).

Operational runbook (short):
- On high CPU/latency: scale the API and worker replica sets; check provider rate-limit metrics in `AiModelsHub`.
- On Pinecone lag: throttle semantic queries, fallback to structured DB retrievals and schedule vector backfills.
- On memory corruption: freeze writes, promote read-replicas, and run integrity checks with audit logs.

