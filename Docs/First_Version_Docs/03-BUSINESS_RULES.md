# 📜 BUSINESS RULES - Nexus Core Logic & Constraints

## Document Purpose
This document specifies **core business rules** that govern system behavior:
- How decisions are made
- What constraints must be honored
- Conflict resolution strategies
- Priority ordering for features
- System-wide rules

---

## 1. Memory Extraction Rules

### Rule 1.1: Belief Extraction Confidence Threshold
**Rule**: Only store beliefs with confidence ≥ 0.70
- Threshold: 70% minimum (0-100 scale)
- Below threshold: Treat as uncertain, require confirmation
- Recalculation: Confidence increases with supporting evidence

**Implementation**:
```
Extract belief → Calculate confidence via SentimentAnalysisEngine
IF confidence >= 0.70 THEN store to contact_beliefs
ELSE mark as pending_confirmation
```

---

### Rule 1.2: Belief Superseding Strategy
**Rule**: When new belief contradicts old belief, create version history

**Process**:
1. Detect contradiction (confidence >0.80)
2. Create new version with current belief
3. Mark old belief as `superseded_at = now()`
4. Keep both in history (never delete)
5. Use most recent with highest confidence

**Conflict Handling**:
- If confidence tied: Most recent wins
- If >2 days apart: Assume contact changed mind (both store)
- If same day: Investigate context (potential misunderstanding)

---

### Rule 1.3: Preference Extraction Only from Patterns
**Rule**: Don't infer preferences from single instances

**Requirement**:
- Evidence count ≥ 3 for inferring preference
- Separated by ≥ 5 messages or 1 day
- Consistency check: All 3+ instances align

**Example**:
- One short message = no conclusion
- Three short messages over 3 days = infer "prefers short messages"

---

### Rule 1.4: Named Entity Deduplication
**Rule**: Auto-link same person mentioned by different names

**Process**:
1. Detect name mention (NER)
2. Check if person already in contact_aliases
3. If fuzzy match >0.85: Suggest link
4. If explicit family relationship: Auto-link
5. If uncertain: Store as pending linkage

---

### Rule 1.5: Memory Consolidation Triggers
**Rule**: Consolidation only when sufficient evidence exists

**Triggers**:
- 5+ related beliefs on same topic
- Pattern repeated 3+ times
- Contradiction detected (2 opposing beliefs)
- Confidence improvement (old: 0.7 → new: 0.9)

**Process**:
- Batch consolidation nightly
- Only consolidate high-confidence data
- Create audit trail of consolidation

---

## 2. Contact Management Rules

### Rule 2.1: Contact Type Hierarchy
**Rule**: Relationship types have priority ordering

**Hierarchy** (highest to lowest):
1. Self (don't store beliefs about self)
2. Family/Partner (intimate context)
3. Close friend (personal context)
4. Business associate (professional context)
5. Client (transactional context)
6. Acquaintance (minimal context)

**Application**: 
- Communication tone varies by type
- Memory sharing filtered by type
- Privacy rules stricter for family/partner

---

### Rule 2.2: VIP Contact Priority
**Rule**: VIP contacts receive priority processing

**VIP Status Triggers**:
- Family/Partner always VIP
- High transaction value >$10,000
- Frequent contact (>10 messages/month)
- Manual VIP flagging by Hédra

**VIP Treatment**:
- Response latency SLA: <30 seconds
- Manual quality review: AI responses reviewed
- Memory precision: Only high-confidence beliefs
- Archive slowness acceptable for non-VIP

---

### Rule 2.3: Location Tracking Constraints
**Rule**: Location data collected only when explicitly mentioned

**Allowed**:
- Contact mentions: "I'm in Cairo now"
- Inferred from event: "Let's meet at my office in Alexandria"
- Metadata: Timezone from messaging patterns

**Not Allowed**:
- IP geolocation (privacy risk)
- Hidden tracking
- Assumptions about location

---

### Rule 2.4: Sensitive Data Handling
**Rule**: Specific data types never stored

**Never Store**:
- Credit card numbers (use masking, delete after 1h)
- SSN/ID numbers (if mentioned, delete immediately)
- Medical conditions (store only with explicit consent)
- Political/religious affiliation (if explicitly stated, mark sensitive)

**Storage of Sensitive**:
- Mark field as `is_sensitive = true`
- Encrypt at rest
- Access logged to audit trail
- Auto-delete based on TTL

---

### Rule 2.5: Contact Relationship Transitivity
**Rule**: Don't auto-link relationships beyond direct mentions

**Allowed**:
- "Ahmed is my brother" → Store relationship
- "Ahmed mentioned his friend Sara" → Don't auto-create Ahmed ↔ Sara link

**Reason**: Prevents false relationship creation

---

## 3. Message Processing Rules

### Rule 3.1: Message Deduplication
**Rule**: Don't process duplicate messages

**Detection**:
- Hash of message text + timestamp
- If received within 5 minutes: Duplicate
- Action: Ignore second copy, log

---

### Rule 3.2: Language Handling
**Rule**: Primary language is Egyptian Arabic

**Processing**:
- All NLP models configured for Arabic
- English supported (secondary)
- Code-switching (English/Arabic mix): Supported
- Formal Arabic: Translate to colloquial for sentiment analysis

**Responders**: Respond in same language/dialect as contact

---

### Rule 3.3: Emoji Consistency
**Rule**: Mirror contact's emoji usage

**Rule Detail**:
- Extract contact's emoji preferences
- Confidence threshold: Used in ≥3 messages
- Tone match: Use similar emojis in responses
- Don't use emojis if contact never uses them

---

### Rule 3.4: Multi-Message Batches
**Rule**: Handle multiple messages sent rapidly as one conversation

**Definition**:
- Messages within 30 seconds of each other
- Same topic/conversation thread
- Treat as single unit for response

**Process**:
1. Receive message 1
2. Receive messages 2,3 within 30 seconds
3. Bundle into single conversation
4. Process once (not 3 separate responses)
5. Respond to all questions together

---

### Rule 3.5: Rate Limiting Per Contact
**Rule**: Maximum response frequency per contact

**Limits**:
- Standard contact: Max 3 responses/hour
- VIP contact: Max 10 responses/hour
- Emergency: Bypass rate limiting with keyword

**Purpose**: Prevent spam-like behavior

---

## 4. Response Generation Rules

### Rule 4.1: Tone Auto-Adjustment
**Rule**: Response tone determined by multiple factors (priority order)

**Priority**:
1. Explicit tone override (if Hédra set)
2. Contact relationship type (family ≠ business)
3. Contact emotional baseline (cheerful ≠ serious)
4. Current message sentiment (angry → empathetic)
5. Topic (business ≠ personal)
6. Time of day (late night → shorter responses)

**Implementation**:
```
tone = resolve_tone_hierarchy([
    override_tone,
    relationship_tone,
    baseline_tone,
    message_sentiment_tone,
    topic_tone,
    time_of_day_tone
])
```

---

### Rule 4.2: Context Size Limits
**Rule**: Don't exceed token limits in prompts

**Hard Limits**:
- System instructions + rules: 500 tokens
- Contact profile: 500 tokens
- Recent memory: 1000 tokens
- Message history: 1500 tokens
- Tool outputs: 500 tokens
- **Total**: < 6000 tokens (to allow response generation)

**Optimization if exceeded**:
1. Remove low-confidence beliefs
2. Use summaries instead of full context
3. Remove oldest messages
4. Drop non-critical rules

---

### Rule 4.3: Personality Consistency
**Rule**: Response reflects Hédra's personality profile

**Rules Enforced**:
- Value consistency: Never violate stated values
- Tone consistency: Match established personality
- Knowledge consistency: Don't claim false expertise
- Emotion consistency: Match emotional baseline

**Validation**: Every response checked for consistency

---

### Rule 4.4: Ethical Guardrails
**Rule**: Never assist with harmful activities

**Blocked Activities**:
- Deception/fraud
- Harassment/bullying
- Illegal activities
- Privacy violations
- Financial manipulation

**Action if triggered**:
- Decline politely
- Log incident
- Alert Hédra if critical
- Suggest alternative ethical approach

---

### Rule 4.5: Confidence-Based Response Length
**Rule**: Response length correlates with confidence level

**Rule Detail**:
- High confidence (>0.85): Detailed response (3-5 sentences)
- Medium confidence (0.70-0.85): Standard response (1-3 sentences)
- Low confidence (<0.70): Ask clarifying question

**Purpose**: Prevent confident-sounding false information

---

## 5. Memory Update Rules

### Rule 5.1: Update-Only, Never Silent Change
**Rule**: Never silently update belief; always log the change

**Process**:
1. New evidence contradicts old belief
2. Create version history record
3. Mark old belief as `superseded_at`
4. Log change to audit trail
5. Update confidence/timestamp

**Never**: Overwrite old belief in-place

---

### Rule 5.2: Consolidation Immutability
**Rule**: Don't change consolidated insights; create new version

**Process**:
- Old consolidation insight from 3 days ago
- New evidence suggests different conclusion
- Create new insight with new reasoning
- Keep old insight in history
- Mark with `replaced_by_id = new_id`

---

### Rule 5.3: Confidence Decay Over Time
**Rule**: Old beliefs lose confidence if not reinforced

**Formula**:
```
confidence_decayed = confidence * e^(-days_since_update / half_life)
half_life = 30 days (default, configurable)
```

**Application**:
- Daily recalculation
- Beliefs <30 days old: Retain confidence
- Beliefs 90+ days old: Cut to 50% confidence
- Beliefs 180+ days old: Marked for human review

---

### Rule 5.4: Memory Audit Trail
**Rule**: Every memory change logged immutably

**Logged Fields**:
- What changed (old → new)
- When it changed
- Why it changed (evidence/reasoning)
- Who triggered it (system/AI/human)
- Confidence level

**Retention**: Permanent (never delete audit log)

---

## 6. Conflict Resolution Rules

### Rule 6.1: Contradictory Beliefs Strategy
**Rule**: Store both beliefs; mark as conflicting

**Process**:
1. Detect contradiction
2. Store BOTH beliefs with version history
3. Mark with `conflict_with_id = other_belief_id`
4. Flag in UI for Hédra resolution
5. Don't use conflicted beliefs in context

**Resolution Options**:
- Hédra manually selects correct belief
- If one >7 days old, assume most recent is correct
- If same day, mark as "require clarification"

---

### Rule 6.2: Information Version Selection
**Rule**: When multiple versions exist, use newest unless overridden

**Selection Criteria** (priority):
1. Explicitly selected version (manual override)
2. Most recent version (`updated_at` DESC)
3. Highest confidence
4. Explicitly verified by Hédra

---

### Rule 6.3: Preferred Contact Field
**Rule**: When multiple contacts with same name, ask for clarification

**Process**:
1. "Call Ahmed" identified as ambiguous
2. System offers options: "Ahmed (brother), Ahmed (coworker)"
3. Require selection before proceeding
4. Remember for future (Ahmed = brother)

---

## 7. Privacy & Security Rules

### Rule 7.1: Minimal Data Collection
**Rule**: Collect only data needed for current purpose

**Application**:
- Don't extract preferences you won't use
- Delete data after fulfilling purpose
- Question: "Why are we storing this?"

---

### Rule 7.2: Data Encryption At Rest
**Rule**: All sensitive fields encrypted with AES-256

**Sensitive Fields**:
- contact_locations (privacy)
- contact_medical_info (health)
- contact_financial_info (money)
- contact_relationship_details (intimate)

**Standard Fields**: Not encrypted (too slow)

---

### Rule 7.3: Access Control By Role
**Rule**: Data access filtered by user role

**Roles**:
- `admin`: Full access
- `copilot_viewer`: Read contact profiles
- `task_runner`: Limited to task-relevant data

**Enforcement**: Query-level filters on all data access

---

### Rule 7.4: GDPR Right to Erasure
**Rule**: Complete erasure on request (cascade delete)

**Process**:
1. Contact requests erasure
2. Identify all data (contact, memories, conversations)
3. Create audit record (encrypted, 7-year retention)
4. Irreversibly delete all active data
5. Verify deletion
6. Confirm to contact

**No Recovery**: After deletion, cannot restore

---

### Rule 7.5: PII Masking in Logs
**Rule**: Never log personally identifiable information

**Masking Rules**:
- Names: [CONTACT_NAME]
- Phone: [PHONE]
- Email: [EMAIL]
- Locations: [LOCATION]
- Amounts: [AMOUNT]

**Storage**: Mapping stored encrypted separately

---

## 8. Performance Rules

### Rule 8.1: Response Time SLA
**Rule**: AI response generation must complete in specified time

**SLA by Contact Type**:
- VIP: < 30 seconds
- Standard: < 2 minutes
- Background tasks: < 5 minutes

**Breach Handling**: 
- Log SLA breach
- Alert if VIP SLA breached
- Fallback to faster model if available

---

### Rule 8.2: Cache Invalidation
**Rule**: Clear cache when data changes

**Triggers for Cache Clear**:
- Any contact attribute updated
- Belief/preference added/modified
- Relationship graph changed
- Rules changed

**Timing**: Clear immediately (not delayed)

---

### Rule 8.3: Database Query Optimization
**Rule**: All queries must use indexes

**Requirement**:
- No full table scans
- Always filter by indexed field
- Explain plan < 100ms

---

### Rule 8.4: Background Job Queueing
**Rule**: Non-urgent jobs queued, not synchronous

**Queued Jobs**:
- Memory consolidation
- Analytics calculation
- Long-term archival
- Embedding generation

**Not Queued** (must be synchronous):
- Message processing
- Response generation
- Immediate memory update

---

## 9. Testing Rules

### Rule 9.1: Unit Test Coverage
**Rule**: Every feature must have unit tests

**Requirement**:
- Minimum 85% code coverage
- Every public method tested
- Edge cases tested
- Mock external dependencies

---

### Rule 9.2: Feature Testing Before Deployment
**Rule**: No feature deployed without full test suite passing

**Test Types**:
- Unit tests (functions)
- Integration tests (hub interactions)
- End-to-end tests (message → response)
- Security tests (injection, auth)

---

## 10. System-Wide Rules

### Rule 10.1: Hub Independence
**Rule**: Hubs don't depend on each other (only via API)

**Principle**:
- No direct database access between hubs
- Communication only via REST APIs
- Events for async notification
- Minimal coupling

---

### Rule 10.2: API Versioning
**Rule**: APIs versioned for backward compatibility

**Requirement**:
- All endpoints versioned (v1, v2)
- Old versions supported for 6 months
- Deprecation warnings before removal
- Migration guide provided

---

### Rule 10.3: Event-Driven Architecture
**Rule**: Major operations emit events

**Required Events**:
- contact.created / contact.updated / contact.deleted
- belief.created / belief.updated
- message.received / message.processed
- task.started / task.completed
- error.occurred

**Listeners**: Subscribe to events for async processing

---

### Rule 10.4: Audit Everything
**Rule**: Log all significant operations

**What to Log**:
- Data access (who read what)
- Data modifications (who changed what, when, old→new)
- Authentication events (logins, logouts)
- Authorization decisions (denied accesses)
- Errors/exceptions

**Retention**: Permanent (never delete audit logs)

---

## 11. Feature Interaction Rules

### Rule 11.1: Feature Precedence
**Rule**: When features conflict, this precedence applies

**Priority Order**:
1. Security/Privacy rules (always enforced)
2. Ethical guidelines (never violated)
3. User explicit rules (if Hédra set)
4. Relationship type rules
5. Contact personality
6. Default behavior

---

### Rule 11.2: Memory Injection Hierarchy
**Rule**: When building context, inject in this priority

**Injection Order** (in prompt):
1. System instructions & persona (highest priority)
2. Explicit Hédra rules
3. Contact relationship type
4. Contact emotional baseline
5. Recent memory (last 3 messages)
6. Relevant beliefs (semantic search)
7. Relevant preferences
8. Historical context (lower priority)

---

### Rule 11.3: Error Fallback Strategy
**Rule**: When feature fails, graceful degradation

**Fallback Chain**:
1. Try primary approach
2. If fails: Try simplified approach
3. If fails: Use previous cached result
4. If fails: Ask Hédra
5. If fails: Log error & pause

**Never**: Fail silently or produce incorrect results

---

## Summary Table

| Rule Category | Key Rules | Impact |
|---------------|-----------|--------|
| Memory Extraction | 1.1-1.5 | Data quality |
| Contact Management | 2.1-2.5 | Relationship accuracy |
| Message Processing | 3.1-3.5 | Response appropriateness |
| Response Generation | 4.1-4.5 | User satisfaction |
| Memory Updates | 5.1-5.4 | Historical integrity |
| Conflict Resolution | 6.1-6.3 | Data consistency |
| Privacy & Security | 7.1-7.5 | Compliance & trust |
| Performance | 8.1-8.4 | System responsiveness |
| Testing | 9.1-9.2 | Quality assurance |
| System-Wide | 10.1-10.4 | Architecture strength |
| Feature Interaction | 11.1-11.3 | Feature harmony |

---

## Critical Decision: Rule Enforcement

**All rules are MANDATORY** unless explicitly overridden in code with:
```php
// @violates Rule 4.5 (ConfidenceBasedResponseLength)
// Justification: Business critical response requires detail regardless
```

**Quarterly Review**: All rule violations reviewed for legitimacy

---

**Document Status**: COMPLETE - All business rules specified  
**Last Updated**: 2025-05-16  
**Next Document**: 04-USER_PERSONAS.md (optional, recommended)
