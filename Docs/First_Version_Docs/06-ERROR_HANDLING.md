# 06 - Error Handling

## Purpose
Define how Nexus handles, logs, and responds to errors consistently to support reliability and debugging.

---

## 1. Error Classification
- **Client errors**: 4xx responses for invalid input or permissions
- **Server errors**: 5xx responses for infrastructure failures
- **External service errors**: provider timeouts, API failures
- **Recoverable errors**: transient failures that can be retried

## 2. API Response Format
- Always return standard error envelope
- Include `code`, `message`, and optional `details`
- Use a trace ID for correlation

Example:
```json
{
  "success": false,
  "error": {
    "code": "AGENT_TASK_FAILED",
    "message": "Agent task execution failed due to timeout",
    "details": {"provider": "openai"}
  },
  "meta": {"trace_id": "abc123"}
}
```

## 3. Backend Error Handling
- Use Laravel exception handlers for API responses
- Convert low-level exceptions into meaningful domain errors
- Avoid exposing sensitive internals in production responses
- Use `App\Exceptions\DomainException` for business rules

## 4. Retries and Resilience
- Retry transient external failures with exponential backoff
- Use circuit breakers for unreliable providers
- Fail gracefully when dependencies are unavailable
- Provide clear fallback behavior when possible

## 5. Logging and Monitoring
- Log every unhandled exception to LogsHub
- Attach context such as user ID, hub, endpoint, and trace ID
- Capture stack traces in development, minimal details in prod
- Emit alerts for recurring failures

## 6. User Feedback
- Surface friendly messages in the UI
- Use loaders and retry banners for recoverable states
- Provide manual retry actions when appropriate
- Preserve user input when errors occur
