# 03 - Component Patterns

Overview
- Define the five component types and standard interfaces so implementations are consistent, testable, and composable.

Design goals
- Single responsibility per component
- Clear input/output contracts (DTOs)
- Side-effects isolated to Services
- Easily unit-testable and mockable

Common conventions (Laravel context)
- Use constructor dependency injection for Services and Repositories.
- Return DTOs or domain objects instead of raw arrays when crossing component boundaries.
- Log at component boundaries and attach `X-Trace-Id` to logs.

Router
- Responsibility: Validate requests, apply auth/permission checks, choose Engine or Pipeline.
- Interface (PHP):
	```php
	interface RouterInterface {
			public function handle(RequestDto $request): ResponseDto;
	}
	```
- Example (Laravel):
	```php
	class MessageRouter implements RouterInterface {
			public function __construct(private ContextAssemblyPipeline $pipeline) {}
			public function handle(RequestDto $req): ResponseDto {
					$this->authorize($req);
					return $this->pipeline->execute($req);
			}
	}
	```

Engine
- Responsibility: Single-purpose business logic (pure or near-pure functions). Engines must not perform external I/O directly.
- Interface (PHP):
	```php
	interface EngineInterface {
			public function run(InputDto $input): OutputDto;
	}
	```
- Example:
	```php
	class SentimentAnalysisEngine implements EngineInterface {
			public function __construct(private AiModelsService $ai) {}
			public function run(InputDto $input): OutputDto {
					$result = $this->ai->analyzeSentiment($input->text);
					return new OutputDto(payload: $result);
			}
	}
	```

Pipeline
- Responsibility: Compose multiple Engines and Builders into a directed sequence for transformations.
- Characteristics: Support branching, retries, parallel steps, and async handoffs.
- Interface (PHP):
	```php
	interface PipelineInterface {
			public function execute(PipelineContext $ctx): PipelineResult;
	}
	```
- Best practices:
	- Keep steps idempotent where possible.
	- Expose both sync `execute()` and async `enqueue()` modes.
	- Emit progress events for long-running pipelines.

Builder
- Responsibility: Assemble complex domain objects (e.g., PromptBuilder, TaskBuilder) from inputs, templates, and settings.
- Example:
	```php
	class PromptBuilder {
			public function build(Contact $c, Context $ctx): Prompt {
					// compose system + persona + recent memories + business rules
			}
	}
	```

Service
- Responsibility: External integrations and stateful operations (DB access, caches, provider APIs). Services own side-effects and retries.
- Expectations:
	- Provide clear, typed interfaces for the rest of the system.
	- Implement circuit-breaker and retry policies.
	- Emit metrics and health checks.

Idempotency & Transactions
- Write endpoints and pipeline commit steps should accept and persist `idempotency_key`.
- Use database transactions for multi-step writes; where cross-service transactions are needed, use Sagas or outbox pattern.

Observability
- Metrics: expose operation timing, success/failure counts, and provider latency.
- Tracing: propagate `X-Trace-Id` through component calls.

Testing & Contracts
- Unit tests: each Engine/Builder/Service must have unit tests for happy and failure paths.
- Contract tests: lightweight integration test that validates a Pipeline run with mocked Services and real Engines.
- Example test (PHPUnit):
	```php
	public function test_prompt_builder_includes_persona() {
			$builder = new PromptBuilder(...);
			$prompt = $builder->build($contact, $ctx);
			$this->assertStringContainsString($contact->persona->system_text, $prompt->system);
	}
	```

Example end-to-end composition
- `MessageRouter` → `ContextAssemblyPipeline` (steps: AddProfileStep, AddRecentMemoriesStep, AddPersonaStep, ApplyBusinessRulesStep) → `PromptBuilder` → `AiModelsService` → `ResponseQualityEngine` → `ResponseDeliveryPipeline`.

Security considerations for components
- Validate and sanitize all inputs at Router boundaries.
- Services must not log sensitive fields; apply scrubbing middleware.

Change management & extension points
- Keep Plugins: allow Engines and Builders to be registered via DI container for feature toggles.
- Feature flags: `SettingsHub` driven flags should gate experimental components.

