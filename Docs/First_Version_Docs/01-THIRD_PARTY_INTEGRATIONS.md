# 01 - Third-Party Integrations

## Overview
This document specifies how Nexus integrates with external services, APIs, and platforms. It covers authentication flows, data synchronization, error handling, and dependency management for all third-party systems.

---

## 1. AI Model Providers

### 1.1 Google Gemini Integration

**Purpose**: Primary LLM for response generation, reasoning, and embeddings

**Authentication**:
- API Key stored in `GEMINI_API_KEY` environment variable
- Rotated monthly via secrets manager
- Rate-limited per project quota

**Endpoints Used**:
- `models/gemini-2.0-flash` - Fast responses (<500ms target)
- `models/gemini-1.5-pro` - Complex reasoning (fallback if needed)
- `models/embedding-004` - Vector embeddings for semantic search

**Request Flow**:
1. `AiModelsHub` receives request with intent and context
2. `ModelRouter` selects provider based on:
   - Task complexity (simple/complex/embedding)
   - Current latency metrics
   - Budget constraints
3. Request sent with trace-id and auth header
4. Response parsed and cached if cacheable
5. Metrics collected and stored in MetricsHub

**Error Handling**:
- Rate limit (429): Exponential backoff, max 5 retries
- Server error (5xx): Retry with 2s delay, max 3 retries
- Invalid request (4xx): Log and alert, don't retry
- Timeout (>30s): Use cached result if available, else fallback model

**Cost Optimization**:
- Token caching enabled for repeated prompts
- Batch processing for non-urgent embeddings
- Model downgrade logic if budget exceeded

---

### 1.2 OpenAI Integration (Secondary)

**Purpose**: Fallback LLM if Gemini unavailable, secondary reasoning

**Authentication**:
- API Key in `OPENAI_API_KEY`
- Organization ID for billing separation
- Monitored via OpenAI dashboard

**Endpoints Used**:
- `gpt-4-turbo` - Complex reasoning
- `gpt-3.5-turbo` - Fast responses
- `text-embedding-3-small` - Lightweight embeddings

**Routing Decision**:
- Use OpenAI if Gemini quota exceeded
- Use OpenAI for specialized tasks (coding assistance)
- Cost comparison: if OpenAI cheaper, route there

**Response Format Compatibility**:
- Responses normalized to same format regardless of provider
- Custom adapter in `AiModelsHub` handles differences

---

### 1.3 Pinecone (Vector Database)

**Purpose**: Semantic search for memory retrieval

**Authentication**:
- API key in `PINECONE_API_KEY`
- Namespace per environment (prod/staging/dev)
- Index: `nexus-memories` with 1536 dimensions

**Operations**:
- **Upsert**: Add/update memory embeddings after Gemini generates them
- **Query**: Retrieve top-K similar memories for context assembly
- **Delete**: Remove memory when contact is erased (GDPR)
- **Metadata**: Store `contact_id`, `created_at`, `confidence` with vectors

**Performance**:
- Query latency target: <100ms
- Batch upserts: 100 vectors at a time
- Retry on failure: Up to 3 times with exponential backoff

**Data Lifecycle**:
- Fresh memories: Query immediately after insert
- Old memories: Gradually deprioritize in search results
- Archived memories: Move to cold storage after 1 year

---

## 2. Communication Channels

### 2.1 WAHA Integration (WhatsApp)

**Purpose**: Primary messaging channel for contacts

**Authentication**:
- Phone number instance via WAHA API
- Session key managed in SettingsHub
- Media webhook for file uploads/downloads

**Message Flow**:
1. Contact sends message to Nexus phone number
2. WAHA posts to `webhooks/whatsapp/incoming`
3. MessageRouter extracts contact, content, timestamp
4. Message processed through ContextAssemblyPipeline
5. AI generates response via AiModelsHub
6. WAHA sends response back to contact
7. Memory extraction runs async

**Features Supported**:
- Text messages (required)
- Media: images, documents, audio, video (supported)
- Location sharing (stored with privacy controls)
- Typing indicators (show "Nexus is typing..." while generating)
- Message reactions/receipts (acknowledged)
- Group messages (not supported - 1-to-1 only)

**Error Handling**:
- Failed send (media too large): Inform contact, suggest smaller file
- Rate-limited: Queue message, retry after backoff period
- Invalid phone: Alert Hédra to verify contact number
- Session expired: Restart session via SettingsHub UI

**Webhook Security**:
- Verify WAHA signature on each incoming webhook
- IP whitelist WAHA server addresses
- TLS enforced for all connections

---

### 2.2 Email Integration

**Purpose**: Asynchronous communication with business contacts

**SMTP Configuration**:
- Provider: SendGrid or similar
- Auth: API key in `EMAIL_API_KEY`
- From address: `noreply@nexus-domain.com`

**Email Processing**:
1. Incoming email to Nexus mailbox
2. EmailParsingService extracts sender, subject, body
3. If sender is known contact: Route to ContextAssemblyPipeline
4. Generate response
5. Send via SMTP
6. Store in memory as conversation

**Features**:
- Thread tracking: Link responses to original email
- Attachment support: Download, scan for PII, store securely
- Signature preservation: Don't include in memory extraction
- Forwarded emails: Extract only new content, not full chain

**Template Management**:
- Professional templates in SettingsHub
- Customizable salutations, closings
- Signature block with Hédra's details

---

### 2.3 SMS Integration

**Purpose**: Quick transactional messages

**Provider**: Twilio or similar

**Features**:
- Send SMS responses if contact prefers SMS
- Two-way SMS for quick replies
- Character limit awareness: Split long messages
- Unicode support: Handle emoji and special characters

**Use Cases**:
- Confirmation messages ("Your appointment is at 3 PM")
- Time-sensitive alerts
- OTP relay (if applicable)

---

## 3. Calendar & Scheduling

### 3.1 Google Calendar Integration

**Purpose**: Sync meetings and availability for scheduling

**Authentication**:
- OAuth2 scope: `calendar.readonly` (or `calendar` for write)
- Token stored in redis with 1-hour TTL
- Refresh token stored securely in database

**Operations**:
- **Read**: Fetch Hédra's calendar events to understand availability
- **Check availability**: Before suggesting meeting times
- **Create event**: Add scheduled meetings to calendar
- **Update**: Modify event details if contact confirms

**Scheduling Flow**:
1. Contact proposes meeting time
2. Check Hédra's Google Calendar for conflicts
3. If available: Auto-confirm and add to calendar
4. If unavailable: Suggest alternatives based on calendar gaps
5. Send confirmation to contact

**Privacy**:
- Only read calendar titles (not description details)
- Treat "busy" generic event as blocking without detail

---

## 4. Data Synchronization Services

### 4.1 Webhook Orchestration

**Purpose**: Receive real-time updates from external systems

**Webhook Registry**:
- URL: `https://nexus-domain.com/webhooks/{provider}`
- Auth: HMAC-SHA256 signature verification
- Retry: Automatic retry if we fail to process

**Supported Webhooks**:
- WAHA: Incoming messages, media, status updates
- Stripe: Payment events (if applicable)
- HubSpot: Contact profile syncs
- Custom: User-defined webhooks via SettingsHub

**Processing**:
1. Receive webhook POST
2. Verify signature
3. Enqueue to message broker
4. Acknowledge with 200 OK immediately
5. Process async to avoid timeouts

---

### 4.2 Scheduled Sync Jobs

**Purpose**: Periodic syncs with external services

**Jobs**:
- **Hourly**: Check for updates from integrated services
- **Daily**: Sync calendar, update contact info
- **Weekly**: Archive old messages, clean cache
- **Monthly**: Billing reconciliation, report generation

**Implementation**:
- Use Laravel's task scheduler (cron-like)
- Each job tracks last run time
- Idempotent: Safe to run multiple times

---

## 5. Payment & Billing

### 5.1 Stripe Integration

**Purpose**: (Optional) Handle payments or subscriptions

**Features**:
- Customer management via Stripe
- Invoice generation
- Payment webhook handling
- Subscription management

**Configuration**:
- Secret key in `STRIPE_SECRET_KEY`
- Publishable key in frontend env
- Webhook endpoint: `/webhooks/stripe`

---

## 6. Analytics & Observability

### 6.1 Datadog Integration

**Purpose**: Centralized monitoring and alerting

**Instrumentation**:
- Application Performance Monitoring (APM)
- Log aggregation
- Metrics collection
- Custom events

**Key Metrics**:
- API latency (p50, p95, p99)
- Error rate per endpoint
- Token usage and cost
- Memory hit/miss rates
- Hub health (response times, error rates)

**Dashboards**:
- System health overview
- AI model performance
- Contact activity timeline
- Cost tracking

---

## 7. Dependency Version Management

### 7.1 Critical Dependencies

| Service | Version | Update Policy | Risk |
|---------|---------|---------------|------|
| Gemini API | v1.0 | Follows Google releases | High (core) |
| WAHA | Pinned | Test before update | High (messaging) |
| Pinecone | v1.0 | Update quarterly | Medium (search) |
| MySQL | 8.0+ | LTS versions only | High (data) |
| Redis | 7.0+ | Stable releases | Medium (cache) |

### 7.2 Update Strategy

- Test in staging environment first
- Gradual rollout: 10% → 50% → 100%
- Rollback plan: Keep previous version deployed
- Monitor for 24 hours after update

---

## 8. Rate Limiting & Quotas

### 8.1 Provider Quotas

**Gemini**:
- Per minute: 60 requests
- Per day: 50,000 requests
- Fallback: OpenAI if exceeded

**WAHA**:
- Per contact: 10 messages/minute (prevent spam)
- Batch mode: Queue and send in sequence

**Email**:
- Per hour: 1,000 emails
- Per day: 10,000 emails

### 8.2 Budget Gates

- Monthly spend cap: Configurable in SettingsHub
- Alert at 80% threshold
- Auto-fallback to cheaper models at 90%
- Hard stop at 100% (until next month)

---

## Summary Table

| Integration | Provider | Purpose | Auth | Update |
|-------------|----------|---------|------|--------|
| LLM | Gemini (primary) | Reasoning | API Key | Dynamic |
| LLM | OpenAI (fallback) | Backup | API Key | Dynamic |
| Vector DB | Pinecone | Embeddings | API Key | Periodic |
| Chat | WAHA | WhatsApp | Session | Real-time |
| Email | SendGrid | Email | API Key | On-demand |
| SMS | Twilio | SMS | API Key | On-demand |
| Calendar | Google | Scheduling | OAuth2 | Real-time |
| Payments | Stripe | Billing | API Key | Webhook |
| Monitoring | Datadog | Observability | API Key | Continuous |

---

**Document Status**: COMPLETE - All integrations specified  
**Last Updated**: 2025-05-16  
**Next Document**: 02-OPERATIONAL_PROCEDURES.md
