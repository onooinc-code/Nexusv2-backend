# 📋 FEATURE REQUIREMENTS - Nexus 80+ Feature Catalog

## Document Purpose
This document specifies **all 80+ features** with detailed implementation requirements:
- **What**: Feature description & purpose
- **Extract**: When & how to extract the data
- **Store**: Where to persist the data
- **Retrieve**: When & how to use it
- **Update**: When & how to keep it fresh
- **Dependencies**: What's required for this feature

---

## Structure Overview

Features organized into **8 categories**:
1. Contact Intelligence (10 features)
2. Memory Features (10 features)
3. Conversation Dynamics (10 features)
4. Temporal & Spatial Awareness (10 features)
5. Analytics & Insights (9 features)
6. Collaboration Features (10 features)
7. Optimization Features (8 features)
8. Privacy & Security Features (8 features)

---

## ⭐ Category 1: Contact Intelligence (10 Features)

### Feature 1: Belief Auto-Update
**Purpose**: Automatically refine old beliefs when contact changes opinion

**What**: 
- Track when contact contradicts previous belief
- Update old statement with new information
- Mark confidence level & date updated

**Extract**:
- When: After every AI response to contact
- How: Call `SentimentAnalysisEngine` + `BeliefExtractionEngine`
- Trigger: Any statement contradicting stored beliefs

**Store**:
- Table: `contact_beliefs`
- Fields: `contact_id`, `belief_text`, `confidence`, `version`, `updated_at`
- Cache: Redis with 24h TTL (key: `contact:{id}:beliefs`)

**Retrieve**:
- When: During context assembly for new message
- How: Query recent beliefs with highest confidence
- Limit: Top 10 beliefs per contact (most recent)

**Update**:
- Event: `BeliefsUpdated` fires when new belief conflicts with old
- Process: Create version history, mark old as superseded
- Frequency: Per message (async background job)

**Dependencies**:
- SentimentAnalysisEngine
- BeliefExtractionEngine
- MemoryHub integration

---

### Feature 2: Implicit Preference Extraction
**Purpose**: Detect unstated preferences (e.g., "prefers short messages")

**What**:
- Analyze communication patterns
- Infer preferences not explicitly stated
- Track consistency across multiple interactions

**Extract**:
- When: Background consolidation (nightly)
- How: Analyze last 50 messages per contact
- Analysis: Response length, emoji usage, tone, timing

**Store**:
- Table: `contact_preferences`
- Fields: `contact_id`, `preference_type`, `value`, `confidence`, `inferred_from_count`
- Example: `(1, 'message_length', 'short', 0.85, 12)`

**Retrieve**:
- When: During prompt construction
- How: Filter preferences with confidence >0.75
- Priority: Higher confidence preferences override

**Update**:
- Event: After every 5 new messages from contact
- Process: Recalculate confidence scores
- Frequency: Daily consolidation job

**Dependencies**:
- MessageAnalysisEngine
- PatternDetectionEngine

---

### Feature 3: Relationship Graph Mapping
**Purpose**: Build networks connecting contacts to each other

**What**:
- Map explicit mentions ("my brother Ahmed", "my boss Sarah")
- Link family, work, social networks
- Track relationship strength & type

**Extract**:
- When: During message processing
- How: NLP entity extraction + manual mention detection
- Confidence: Higher if mentioned multiple times

**Store**:
- Table: `contact_relationships`
- Fields: `contact_id`, `related_contact_id`, `relationship_type`, `mention_count`, `confidence`
- Index: On both contact IDs

**Retrieve**:
- When: Building contact profile/context
- How: Query all relationships for contact
- Display: In UI relationship graph

**Update**:
- Trigger: When contact is mentioned in conversation
- Frequency: Per message (increment mention_count)
- Confidence decay: If not mentioned for 90 days

**Dependencies**:
- EntityExtractionEngine
- ContactHubService

---

### Feature 4: Emotional Baseline Tracking
**Purpose**: Remember if contact is naturally angry/cheerful/serious

**What**:
- Calculate average sentiment across conversations
- Identify natural emotional disposition
- Track mood variations & patterns

**Extract**:
- When: After sentiment analysis (every message)
- How: SentimentAnalysisEngine extracts mood value (-1 to +1)
- Aggregate: Moving average (last 20 messages)

**Store**:
- Table: `contact_emotional_baseline`
- Fields: `contact_id`, `baseline_sentiment`, `volatility`, `last_calculated_at`
- Cache: Redis key `contact:{id}:emotional_baseline`

**Retrieve**:
- When: Constructing persona for response
- How: Fetch baseline to calibrate tone
- Use case: Adjust apologies/enthusiasm based on baseline

**Update**:
- Frequency: After every 5 messages
- Process: Recalculate moving average
- Volatility: Track std deviation of sentiments

**Dependencies**:
- SentimentAnalysisEngine
- PersonaAndToneEngine

---

### Feature 5: Tone Mirroring
**Purpose**: Mirror contact's emoji usage and communication style

**What**:
- Track emoji preferences ("uses lots of 😂", "uses 💯 for agreement")
- Detect formality level (abbreviations vs formal)
- Mirror punctuation style

**Extract**:
- When: During message analysis
- How: Regex + style pattern detection
- Analysis: Emoji frequency, formality metrics

**Store**:
- Table: `contact_tone_profile`
- Fields: `contact_id`, `preferred_emojis` (JSON), `formality_level`, `punctuation_style`
- Example: `(1, '["😂", "💯", "🔥"]', 'casual', 'abbreviations')`

**Retrieve**:
- When: Building response
- How: Fetch tone profile during prompt assembly
- Application: Tone adjustment in PromptBuilder

**Update**:
- Trigger: Every message from contact
- Process: Update emoji frequency distribution
- Frequency: Every 10 messages recalculate

**Dependencies**:
- StyleAnalysisEngine
- PromptBuilder

---

### Feature 6: Alias Resolution
**Purpose**: Link "Abu Hamid" to "Ahmed" across conversations

**What**:
- Identify when person is referred to by different names
- Create alias links
- Maintain single identity across system

**Extract**:
- When: During entity extraction
- How: Entity linker + fuzzy matching on relationships
- Confidence: Higher if family relationship confirmed

**Store**:
- Table: `contact_aliases`
- Fields: `primary_contact_id`, `alias_name`, `created_context`, `confidence`
- Index: Full-text search on alias_name

**Retrieve**:
- When: Normalizing contact references in analysis
- How: Lookup alias to find primary contact
- Cache: Redis with alias → contact_id mapping

**Update**:
- Manual: Through ContactHubUI
- Automatic: Suggestion when same person mentioned differently
- Frequency: On-demand when ambiguity detected

**Dependencies**:
- EntityExtractionEngine
- ContactHubService

---

### Feature 7: Cultural & Spatial Memory
**Purpose**: Remember location for contextually appropriate suggestions

**What**:
- Track primary location (city/country)
- Note secondary locations (work, vacation spots)
- Tailor suggestions by geography

**Extract**:
- When: Explicitly mentioned or inferred from context
- How: Location entity extraction + inference
- Validation: Multiple mentions increase confidence

**Store**:
- Table: `contact_locations`
- Fields: `contact_id`, `location_type` (primary/work/vacation), `city`, `country`, `confidence`
- Geocoding: Store lat/long for radius queries

**Retrieve**:
- When: Making recommendations or scheduling
- How: Fetch primary location
- Use: Filter recommendations to same region

**Update**:
- Event: `ContactLocationMentioned` if new location
- Process: Add new location or update confidence
- Frequency: On-demand

**Dependencies**:
- LocationExtractionEngine
- GeocodingService

---

### Feature 8: Hierarchical Information Priority
**Purpose**: Fixed rules trump passing memories in decision-making

**What**:
- Store permanent rules ("never discuss politics with Ahmed")
- Apply rules before consulting memories
- Prevent contradictory decisions

**Extract**:
- When: Manually set or auto-learned from pattern
- How: User input + pattern detection
- Validation: High-confidence patterns only

**Store**:
- Table: `contact_rules`
- Fields: `contact_id`, `rule_text`, `rule_type` (communication/topic/action), `priority`, `enabled`
- No TTL - permanent unless deleted

**Retrieve**:
- When: Before every response
- How: Apply rules as filters on available actions
- Use: Override conflicting suggestions

**Update**:
- Process: Manual through SettingsHub
- Or: Auto-learn from repeated patterns
- Frequency: On-demand

**Dependencies**:
- RuleEngine
- SettingsHub

---

### Feature 9: Dynamic Profile Summarization
**Purpose**: Generate mini-personas for dashboard display

**What**:
- Create 2-3 sentence summary of contact personality
- Auto-generate from beliefs, preferences, baseline
- Update periodically

**Extract**:
- When: On-demand or daily
- How: Aggregate beliefs + preferences + baseline
- Generation: Use Gemini Flash to synthesize

**Store**:
- Table: `contact_summaries`
- Fields: `contact_id`, `summary_text`, `generated_at`, `source_features` (JSON)
- Cache: Redis with 24h TTL

**Retrieve**:
- When: Dashboard display
- How: Fetch or regenerate if stale
- Display: In contact card/profile

**Update**:
- Frequency: Daily or on-demand
- Trigger: After significant belief/preference changes
- Process: Call Gemini to regenerate

**Dependencies**:
- AiModelsHub
- Belief/Preference extraction

---

### Feature 10: Purchase Power Inference
**Purpose**: Estimate budget from past deals

**What**:
- Track transaction amounts
- Infer spending capacity
- Suggest appropriately-priced offerings

**Extract**:
- When: Transaction mentioned in conversation
- How: Entity extraction of amounts + context
- Validation: Multiple transactions establish pattern

**Store**:
- Table: `contact_purchase_history`
- Fields: `contact_id`, `amount`, `category`, `date`
- Analytics: Min, max, average per contact

**Retrieve**:
- When: Considering recommendations
- How: Calculate average transaction value
- Use: Filter offerings by contact's typical spend

**Update**:
- Trigger: New transaction mentioned
- Process: Add to history, recalculate statistics
- Frequency: Per conversation

**Dependencies**:
- AmountExtractionEngine
- ContextualAnalysisEngine

---

## 🧠 Category 2: Memory Features (10 Features)

### Feature 11: Infinite Context Simulation
**Purpose**: Retrieve data from months ago via intelligent paging

**What**:
- Implement paging system for large memory sets
- Avoid token explosion for old conversations
- Load relevant sections on-demand

**Extract**: N/A (uses existing memories)

**Store**:
- Existing: Conversation history in MySQL
- Metadata: Indexed by date, topic, relevance

**Retrieve**:
- When: Searching historical context
- How: Pagination API with date/topic filters
- Efficiency: Load only 2-3 summaries of relevant sections

**Update**: N/A (retrieval-only)

**Implementation**:
- MemoryRouter directs to MemoryHub.getHistoricalContext()
- Service: HistoricalContextPaginator
- Cache: Recent summaries in Redis
- Max load: 10 summaries per request

**Dependencies**:
- MemoryHub
- Conversation archive tables
- Summarization service

---

### Feature 12: State Recovery
**Purpose**: Resume from exact point after server restart

**What**:
- Save execution state of long-running tasks
- Persist current message context
- Resume without re-processing

**Extract**: N/A (persistence feature)

**Store**:
- Table: `task_state_snapshots`
- Fields: `task_id`, `state_json`, `checkpoint_step`, `created_at`
- Strategy: Save after each step completion

**Retrieve**:
- When: Task resumes after crash
- How: Query latest snapshot for task
- Application: Load state into WorkflowEngine

**Update**:
- Frequency: After each workflow step
- Process: Snapshot current state to DB
- Cleanup: Delete old snapshots after completion

**Dependencies**:
- WorkflowsAndTasksHub
- CheckpointingService

---

### Feature 13: Background Memory Consolidation
**Purpose**: Link scattered memories into conclusions at night

**What**:
- Scheduled job runs consolidation
- Connects related memories (e.g., "mentioned family" x3 → "is family-oriented")
- Creates new insights from patterns

**Extract**:
- When: Nightly consolidation job (e.g., 2 AM)
- How: Fetch all beliefs/preferences from past week
- Analysis: Call Gemini to identify patterns

**Store**:
- Table: `consolidation_insights`
- Fields: `contact_id`, `insight_text`, `supporting_evidence_ids` (JSON), `confidence`, `created_at`

**Retrieve**:
- When: Building contact profile
- How: Fetch recent insights (last 7 days)
- Use: Include in context as "meta-learnings"

**Update**:
- Frequency: Nightly (scheduled job)
- Trigger: SchedulerHub.consolidateMemories()
- Process: Call ConsolidationEngine with aggregated data

**Dependencies**:
- SchedulerHub
- ConsolidationEngine
- AiModelsHub

---

### Feature 14: Tool Memory
**Purpose**: Remember which tools work/fail with which contacts

**What**:
- Track tool success rate per contact
- Remember tool failures to avoid retry
- Learn tool preferences

**Extract**:
- When: After tool execution
- How: Capture success/failure + execution time
- Analysis: Aggregate over time

**Store**:
- Table: `contact_tool_effectiveness`
- Fields: `contact_id`, `tool_name`, `success_count`, `failure_count`, `last_used_at`
- Calculation: success_rate = success_count / (success_count + failure_count)

**Retrieve**:
- When: TaskRouter selecting tools
- How: Filter tools with success_rate >0.8
- Use: Prefer proven tools

**Update**:
- Trigger: After tool execution
- Process: Increment counters
- Frequency: Per task execution

**Dependencies**:
- TaskRouter
- Tool execution tracking

---

### Feature 15: Knowledge Sharing Between Tasks
**Purpose**: Pass memory from one task to dependent tasks

**What**:
- When Task A (research) completes, pass results to Task B (messaging)
- Avoid re-extraction of same information
- Link related task executions

**Extract**:
- When: Task completion
- How: Extract valuable findings from completion context
- Filter: Include only reusable knowledge

**Store**:
- Table: `task_shared_context`
- Fields: `source_task_id`, `target_task_id`, `context_data` (JSON), `created_at`
- TTL: Expire after task completion

**Retrieve**:
- When: Task B starts (if linked to completed Task A)
- How: Check task dependencies, fetch shared context
- Use: Prepend to Task B's initial context

**Update**:
- Frequency: Per task completion
- Cleanup: Delete after target task completes
- Validation: Only share if confidence >0.85

**Dependencies**:
- WorkflowsAndTasksHub
- TaskDependencyResolver

---

### Feature 16: Self-Reflection Loops
**Purpose**: Agent reads error history before decisions

**What**:
- Before major decision, review past failures
- Learn from mistakes automatically
- Avoid repeating errors

**Extract**:
- When: Before consequential decisions
- How: Query error logs + decision history
- Analysis: Identify similar situations

**Store**:
- Table: `agent_decision_history`
- Fields: `contact_id`, `decision_context`, `decision_made`, `outcome` (success/failure), `timestamp`
- Archive: Keep indefinitely for learning

**Retrieve**:
- When: About to make similar decision
- How: Semantic search for similar contexts
- Use: Include past failure + corrective action in prompt

**Update**:
- Frequency: Per decision
- Process: Log decision + outcome after it resolves
- Learning: Use in future decision-making

**Dependencies**:
- DecisionLoggingService
- SemanticSearchEngine

---

### Feature 17: Auto-Pruning
**Purpose**: Agent removes intermediate steps after task success

**What**:
- After task completes successfully, delete intermediate working memory
- Keep only final results & learnings
- Preserve space for new memories

**Extract**: N/A (cleanup feature)

**Store**:
- Table: `task_working_memory` (ephemeral)
- Retention: While task is active
- Deletion: 1 hour after completion

**Retrieve**: N/A

**Update**:
- Trigger: Task completion with success=true
- Process: Mark intermediate steps for deletion
- Frequency: Per task

**Implementation**:
- WorkflowEngine.on('TaskComplete') calls MemoryHub.pruneWorkingMemory()
- Safe: Keep outcomes & learnings
- Aggressive: Delete temp data

**Dependencies**:
- WorkflowsAndTasksHub
- MemoryHub

---

### Feature 18: Goal-Directed Memory Search
**Purpose**: Search only relevant parts for current goal

**What**:
- When contact says "can you help with X?", search only X-relevant memories
- Ignore irrelevant personal details
- Efficient context assembly

**Extract**: N/A (filtering feature)

**Store**: Uses existing memory tables

**Retrieve**:
- When: Processing contact message
- How: IntentEngine determines goal → MemoryRouter filters
- Scope: Only fetch memories matching goal category

**Update**: N/A

**Implementation**:
- MemoryRouter.searchByGoal(contact_id, goal) → filters query by topic/category
- Example: "help with work" → exclude personal gossip
- Efficiency: Reduces tokens loaded

**Dependencies**:
- IntentAndTopicEngine
- MemoryRouter
- CategoryTagging system

---

### Feature 19: Cross-Agent Memory Sharing
**Purpose**: Transfer memory from one agent to another

**What**:
- Support_Agent learns contact is frustrated → Sales_Agent gets briefed
- Agent handoff includes context transfer
- Consistent understanding across agents

**Extract**:
- When: Agent handoff occurs
- How: Extract key findings from outgoing agent
- Format: Structured briefing document

**Store**:
- Table: `agent_handoff_context`
- Fields: `source_agent_id`, `target_agent_id`, `briefing_data` (JSON), `created_at`

**Retrieve**:
- When: Target agent starts
- How: Fetch latest briefing for contact
- Use: Prepend to target agent's prompt

**Update**:
- Trigger: Manual handoff command
- Frequency: Per handoff
- Cleanup: Archive after target completes

**Dependencies**:
- AgentsHub
- Agent lifecycle management

---

### Feature 20: Counterfactual Reasoning
**Purpose**: Learn from "what if" scenarios

**What**:
- Remember: "When we offered 10% discount, contact agreed"
- Now: "Contact seems interested, suggest 5% discount"
- Reasoning: Based on historical offer acceptance patterns

**Extract**:
- When: Offer accepted/rejected
- How: Capture offer details + outcome
- Pattern: Store as counterfactual example

**Store**:
- Table: `contact_offer_patterns`
- Fields: `contact_id`, `offer_type`, `offer_value`, `accepted`, `context`, `date`
- Analytics: Calculate acceptance rate by offer_value

**Retrieve**:
- When: About to make offer
- How: Query similar past offers
- Use: Predict optimal offer value

**Update**:
- Trigger: Offer response received
- Process: Add to pattern history
- Frequency: Per offer interaction

**Dependencies**:
- OfferAnalysisEngine
- PatternMatchingEngine

---

## 💬 Category 3: Conversation Dynamics (10 Features)

### Feature 21: Topic Drift Detection
**Purpose**: Recognize when conversation shifts to new topic

**What**:
- Detect "we were talking about X, now we're on Y"
- Mark topic boundaries
- Prepare for topic return if interrupted

**Extract**:
- When: After each contact message
- How: Topic classification on message
- Comparison: Compare to previous message topic

**Store**:
- Table: `conversation_topics`
- Fields: `session_id`, `topic_name`, `start_message_id`, `end_message_id`, `duration_minutes`
- Index: On session_id for quick lookup

**Retrieve**:
- When: Processing new message
- How: Fetch current topic from recent messages
- Use: Context for reference resolution

**Update**:
- Trigger: Topic classification changes
- Process: Close old topic, start new one
- Frequency: Per message

**Dependencies**:
- TopicClassificationEngine
- SessionManagementService

---

### Feature 22: Interruption Handling
**Purpose**: Remember unanswered questions during interruptions

**What**:
- Contact: "What's the timeline?" (Q1)
- Same message: "Actually, can you also call John?" (Q2)
- After task done: "Remember you were asking about timeline?"

**Extract**:
- When: Message contains multiple question marks or topics
- How: Question extraction + topic boundaries
- Classification: Primary vs secondary

**Store**:
- Table: `pending_questions`
- Fields: `session_id`, `question_text`, `context_message_id`, `status` (pending/answered), `asked_at`

**Retrieve**:
- When: About to end conversation
- How: Query pending questions for session
- Use: Offer to address before closing

**Update**:
- Trigger: Question detected
- Process: Add to pending list
- Completion: Mark answered after response

**Dependencies**:
- QuestionExtractionEngine
- SessionManagementService

---

### Feature 23: Reference Resolution
**Purpose**: "Book with him" refers to mentioned engineer Ahmed

**What**:
- Understand pronouns & references correctly
- Track who/what pronouns refer to
- Handle anaphora & coreference

**Extract**:
- When: Message contains pronouns (he/she/it/they)
- How: Coreference resolution NLP
- Validation: Confirm with context

**Store**:
- Table: `reference_resolutions`
- Fields: `session_id`, `pronoun_text`, `resolved_entity`, `confidence`, `message_id`
- Context: Store surrounding text for validation

**Retrieve**:
- When: Building context for response
- How: Resolve pronouns before response generation
- Use: Replace pronouns with entities in prompt

**Update**:
- Frequency: Per message with pronouns
- Validation: High confidence only (>0.9)
- Learning: Use successful resolutions to train model

**Dependencies**:
- CoreferenceResolutionEngine (spaCy/Hugging Face)
- ContextualAnalysisEngine

---

### Feature 24: Inside Joke Tracking
**Purpose**: Remember and use shared jokes for bonding

**What**:
- "There's a running joke between us about coffee addiction"
- Use joke appropriately to build rapport
- Avoid forced jokes that don't fit

**Extract**:
- When: Joke identified in conversation
- How: Sentiment + humor detection
- Validation: Repeated joke = confirmed inside joke

**Store**:
- Table: `contact_inside_jokes`
- Fields: `contact_id`, `joke_text`, `context`, `first_used_at`, `reference_count`
- Tagging: Store topic/category

**Retrieve**:
- When: Appropriate moment to build rapport
- How: Filter by topic of current conversation
- Use: Include natural reference in response

**Update**:
- Trigger: Joke mentioned/repeated
- Process: Increment reference_count
- Frequency: Per mention

**Dependencies**:
- HumorDetectionEngine
- RelevanceClassifier

---

### Feature 25: Multi-Session Continuity
**Purpose**: "What happened with that problem from last week?"

**What**:
- Connect conversations across days/weeks
- Reference previous sessions
- Maintain narrative continuity

**Extract**:
- When: New session starts
- How: Fetch previous sessions for contact
- Summary: Generate session summaries

**Store**:
- Table: `conversation_sessions`
- Fields: `contact_id`, `session_start`, `session_end`, `main_topic`, `summary`, `outcomes` (JSON)
- Archive: After 7 days or when closed

**Retrieve**:
- When: New session from same contact
- How: Fetch last 3 sessions + summaries
- Use: Include previous context in prompt

**Update**:
- Trigger: Session closes (inactivity >30min)
- Process: Generate session summary via Gemini
- Frequency: Per session close

**Dependencies**:
- SessionManagementService
- SummarizationEngine

---

### Feature 26: Contextual Summarization
**Purpose**: Compress 100 messages to 3 sentences

**What**:
- Efficiently represent long conversation
- Keep key facts, remove redundancy
- Enable fast context loading

**Extract**:
- When: After 50+ messages in session
- How: Call Gemini summarization
- Compression: Target 5-10% of original length

**Store**:
- Table: `message_summaries`
- Fields: `session_id`, `message_range` (start_id-end_id), `summary_text`, `key_facts` (JSON)

**Retrieve**:
- When: Loading old session context
- How: Use summaries instead of full messages
- Fallback: Link to full messages if needed

**Update**:
- Frequency: Every 50 messages
- Process: Call SummarizationEngine.summarizeRange()
- Quality: Manual review for VIP contacts

**Dependencies**:
- SummarizationEngine
- AiModelsHub

---

### Feature 27: Slot Filling
**Purpose**: "We have name/email but need phone"

**What**:
- Track missing required information
- Prompt user naturally for missing data
- Remember what's still needed

**Extract**:
- When: After conversation
- How: Extract all captured data vs required fields
- Analysis: Identify gaps

**Store**:
- Table: `contact_missing_data`
- Fields: `contact_id`, `field_name`, `required`, `attempts`, `last_prompted_at`
- TTL: Clear when field filled

**Retrieve**:
- When: Conversation closing
- How: Query unfilled required fields
- Use: Natural ask in closing message

**Update**:
- Trigger: Required field is missing
- Process: Add to missing_data table
- Completion: Remove when data provided
- Escalation: If unfilled after 3 attempts, escalate to human

**Dependencies**:
- DataValidationPipeline
- Contact schema validation

---

### Feature 28: Dynamic Prompt Injection
**Purpose**: Include relevant memory based on current intent

**What**:
- Intent is "ask for discount" → include past negotiation memories
- Intent is "relationship inquiry" → include emotional baseline
- Selective injection to stay under token limit

**Extract**: N/A (uses existing memories)

**Store**: Uses existing memory tables

**Retrieve**:
- When: Building prompt during PromptConstructionPipeline
- How: IntentEngine determines intent → MemoryRouter filters relevant memories
- Selection: Top 3-5 most relevant by semantic similarity

**Update**: N/A

**Implementation**:
- PromptBuilder.buildPrompt() calls MemoryRouter.getRelevantMemories(intent)
- Semantic search: Find memories matching intent
- Token check: Ensure total tokens <6000 before injection

**Dependencies**:
- IntentAndTopicEngine
- MemoryRouter
- SemanticSearchEngine

---

### Feature 29: RAG-Based Chat
**Purpose**: "Did this problem happen before?" answered from history

**What**:
- Search entire conversation history
- Find similar past situations
- Include as "precedent" in response

**Extract**: N/A (uses conversation archive)

**Store**: Existing conversation tables + vector embeddings

**Retrieve**:
- When: Contact asks question suggesting past experience
- How: Semantic search on vector store (Pinecone)
- Results: Top 3-5 similar conversations with similarity score

**Update**:
- Frequency: Continuous (embeddings created during archival)
- Process: Archive old messages → generate embeddings → store in Pinecone
- Cleanup: Delete embeddings for deleted messages

**Implementation**:
- ContextAssemblyPipeline.addHistoricalPrecedents()
- Pinecone query: contact_id + semantic query
- Format: "In situation X (date: Y), we did Z"

**Dependencies**:
- VectorStore (Pinecone)
- EmbeddingService
- ArchivalService

---

### Feature 30: Repetition Avoidance
**Purpose**: Stop apologizing after 3rd apology in same thread

**What**:
- Count apologies in current conversation
- Detect repetitive patterns
- Switch to solution mode after repetition threshold

**Extract**:
- When: Processing message (after apology detected)
- How: Sentiment analysis + text matching for apology patterns
- Count: Track per session

**Store**:
- Table: `session_repetition_tracking`
- Fields: `session_id`, `pattern_type` (apology/thanks), `count`, `last_occurrence_message_id`
- Threshold: Define max repetitions per pattern

**Retrieve**:
- When: About to generate apology response
- How: Query repetition count for session
- Decision: If count >= threshold, shift to action

**Update**:
- Trigger: Apology pattern detected
- Process: Increment count
- Reset: When pattern changes or session closes
- Frequency: Per message

**Dependencies**:
- PatternDetectionEngine
- ToneAdjustmentService

---

## ⏰ Category 4: Temporal & Spatial Awareness (10 Features)

### Feature 31: Time-Decay Memory
**Purpose**: Forget old negative emotions, focus on current state

**What**:
- Old anger diminishes over time
- Recent positive overrides old negative
- Sentiment has freshness weight

**Extract**:
- When: Retrieving historical sentiment
- How: Apply decay function to old emotions
- Formula: decay_factor = e^(-days_elapsed / half_life)

**Store**:
- Table: `contact_sentiment_history`
- Fields: `contact_id`, `sentiment_value`, `recorded_at`, `decay_weight` (calculated)

**Retrieve**:
- When: Building emotional baseline
- How: Weighted average of sentiments (apply decay)
- Use: Recent sentiments weighted 3x higher than 3-month-old

**Update**:
- Frequency: Daily (recalculate decay_weight)
- Half-life: 30 days (configurable)
- Query: Include only last 6 months (older decays to near-zero)

**Implementation**:
- MemoryHub.getWeightedEmotionalBaseline() applies decay
- SettingsHub control: Configure half-life per contact type

**Dependencies**:
- MemoryHub
- SentimentAnalysisEngine

---

### Feature 32: Expiry Dating
**Purpose**: Auto-delete discount codes after a week

**What**:
- Store time-limited information with expiry date
- Automatically remove expired items
- Warn before expiry

**Extract**:
- When: Discount/offer/schedule stored
- How: Extract or calculate expiry date
- Validation: Confirm expiry date is in future

**Store**:
- Table: `contact_expiring_data`
- Fields: `contact_id`, `data_type` (discount/offer/note), `value`, `expires_at`, `cleanup_status`

**Retrieve**:
- When: Building context
- How: Filter OUT expired items
- Warning: Alert if expires within 24h

**Update**:
- Trigger: Scheduled cleanup job (hourly)
- Process: Mark cleanup_status='deleted' for expired rows
- Frequency: Hourly archival task

**Implementation**:
- SchedulerHub.cleanupExpiredData() runs hourly
- Soft delete: Keep for audit, mark deleted
- Notification: Alert before expiry if critical

**Dependencies**:
- SchedulerHub
- ExpirableDataStore

---

### Feature 33: Timeline Generation
**Purpose**: Visual timeline of customer journey for dashboard

**What**:
- Show relationship milestones chronologically
- Key conversations & decisions
- Relationship growth visualization

**Extract**:
- When: On-demand or daily refresh
- How: Query key events from contact history
- Selection: Major decisions, milestones, changes

**Store**:
- Table: `contact_timeline_events`
- Fields: `contact_id`, `event_date`, `event_type` (deal/conversation/change), `event_description`, `importance`

**Retrieve**:
- When: Dashboard loads contact profile
- How: Fetch last 12 months of timeline events
- Format: Return chronologically ordered for UI

**Update**:
- Frequency: Real-time when key events occur
- Process: LogsHub writes timeline events
- Manual: Can add custom timeline entries through UI

**Implementation**:
- Dashboard component: TimelineWidget
- Data source: Contact profile page queries timeline_events
- Visualization: Horizontal timeline with event cards

**Dependencies**:
- LogsHub
- Dashboard UI component

---

### Feature 34: Anniversary Notifications
**Purpose**: "One year since first contact with VIP Ahmed"

**What**:
- Track important anniversaries
- Schedule proactive outreach
- Remind of relationship milestones

**Extract**:
- When: Contact first interacts OR manually set
- How: Store relationship start date
- Identification: Mark VIP contacts

**Store**:
- Table: `contact_anniversaries`
- Fields: `contact_id`, `anniversary_type` (first_contact/partner_anniversary/birthday), `date_of_year`, `notification_enabled`

**Retrieve**:
- When: SchedulerHub runs daily
- How: Check today's date against all anniversaries
- Trigger: Create notification task

**Update**:
- Frequency: Set once, auto-repeats yearly
- Customization: Enable/disable per contact
- Format: Allow custom message templates

**Implementation**:
- SchedulerHub.checkAnniversaries() daily at 6 AM
- If matched: Create TaskNotification task
- Template: "One year since we connected, Ahmed!"

**Dependencies**:
- SchedulerHub
- TaskNotificationService
- Contact start date field

---

### Feature 35: Predictive Event Timing
**Purpose**: "Contact usually requests maintenance every 3 months"

**What**:
- Identify recurring request patterns
- Predict next likely request
- Proactive reach-out before request

**Extract**:
- When: Request pattern observed (3+ occurrences)
- How: Analyze intervals between requests
- Calculation: Average interval + std deviation

**Store**:
- Table: `contact_request_patterns`
- Fields: `contact_id`, `request_type`, `average_days_between`, `std_deviation`, `next_predicted_date`

**Retrieve**:
- When: SchedulerHub checks predictions
- How: Find contacts with predictions matching today
- Use: Trigger proactive outreach task

**Update**:
- Trigger: Request fulfilled
- Process: Update pattern, recalculate next_predicted_date
- Frequency: Per request completion
- Learning: More data points = more accurate predictions

**Implementation**:
- PatternPredictionEngine.updatePatternPredictions()
- Runs after each request completion
- SchedulerHub.checkPredictedRequests() triggers outreach

**Dependencies**:
- PatternDetectionEngine
- SchedulerHub
- TaskCompletionTracking

---

### Feature 36: Chronological Anomaly Detection
**Purpose**: Notice unusual early morning messages (potential emergency)

**What**:
- Contact usually messages at 10 AM, today at 4 AM
- Detect deviations from normal patterns
- Flag for potential urgency

**Extract**:
- When: Every contact message arrives
- How: Compare arrival time to historical pattern
- Z-score: Calculate standard deviations from mean

**Store**:
- Table: `contact_messaging_patterns`
- Fields: `contact_id`, `avg_time_of_day`, `std_dev`, `avg_day_of_week`, `frequency_per_day`

**Retrieve**:
- When: Message arrives
- How: Fetch pattern, calculate z-score
- Threshold: Anomaly if z-score > 2.5

**Update**:
- Frequency: Daily pattern recalculation
- Process: Update avg_time_of_day, std_dev
- Weighting: Recent messages weighted higher

**Implementation**:
- MessageRouter calls AnomalyDetectionEngine
- If anomaly detected: Set flag on message for elevated attention
- Use: Include in prompt: "Note: Contact messaged at unusual time (4 AM), may be urgent"

**Dependencies**:
- AnomalyDetectionEngine
- PatternAnalysisEngine

---

### Feature 37: Recency vs. History Weighting
**Purpose**: "New address (yesterday) vs. old address (2 years ago)"

**What**:
- Distinguish outdated info from current
- Weight recent info higher in decisions
- Maintain info versions

**Extract**: N/A (uses timestamps)

**Store**:
- All versioned tables include: `created_at`, `updated_at`, `superseded_at`
- Example: contact_locations has multiple rows (old ones have superseded_at set)

**Retrieve**:
- When: Getting contact info
- How: Filter to NOT superseded, order by updated_at DESC
- Weight: Recent info gets 0.9, 6-month-old gets 0.5, older gets <0.2

**Update**:
- Trigger: New version of info provided
- Process: Set superseded_at on old version
- Frequency: Per update

**Implementation**:
- ProfileAssemblerBuilder sorts by recency
- Confidence scores weighted by age
- Include both versions if significantly different (conflict detection)

**Dependencies**:
- Versioning in all data tables
- ProfileAssemblerBuilder

---

### Feature 38: Seasonal Preference Tracking
**Purpose**: "Contact requests pool services only in summer"

**What**:
- Track request/preference patterns by season
- Predict seasonal needs
- Adapt availability expectations

**Extract**:
- When: Request made (capture season/month)
- How: Categorize by season (quarter)
- Pattern: Track requests per season

**Store**:
- Table: `contact_seasonal_patterns`
- Fields: `contact_id`, `request_type`, `season_q1/q2/q3/q4` (request counts per quarter)

**Retrieve**:
- When: Planning outreach
- How: Fetch seasonal patterns for contact
- Use: Suggest relevant services by season

**Update**:
- Frequency: Per request (increment relevant quarter)
- Trend: Track if patterns change year-over-year
- Learning: Adjust predictions based on history

**Implementation**:
- RequestAnalysisEngine categorizes by season
- SchedulerHub checks seasonal patterns when scheduling outreach
- UI: Show seasonal trend chart in contact profile

**Dependencies**:
- RequestTrackingService
- SeasonalAnalysisEngine

---

### Feature 39: SLA Context Awareness
**Purpose**: Auto-apologize for 2-hour response delay

**What**:
- Track expected response time (SLA)
- If delayed, acknowledge in response
- Self-aware about service level

**Extract**:
- When: Message received
- How: Note arrival time, compare to current time
- Calculation: Delay = now - message_received_time

**Store**:
- Table: `contact_service_levels`
- Fields: `contact_id`, `expected_response_minutes`, `sla_type` (standard/vip/support)
- Default: 30 minutes for standard, 10 for VIP

**Retrieve**:
- When: Building response
- How: Check SLA + actual delay
- Use: If delay > SLA, add apology to prompt

**Update**:
- Manual: Through SettingsHub per contact
- Automatic: Adjust based on contact patterns

**Implementation**:
- ContextAssemblyPipeline calculates delay
- If delay > SLA: Add instruction to response builder: "Start with brief apology for delay"
- Track: Log all SLA misses for reporting

**Dependencies**:
- SettingsHub
- ResponseBuilder

---

### Feature 40: Fact Versioning
**Purpose**: Git-like history for contact data changes

**What**:
- Track all changes to contact attributes
- Keep version history (like git)
- Ability to see what changed and when

**Extract**: N/A (tracking feature)

**Store**:
- Table: `contact_attribute_history`
- Fields: `contact_id`, `attribute_name`, `old_value`, `new_value`, `changed_by` (system/user), `changed_at`, `version_number`
- Index: On contact_id + changed_at for history queries

**Retrieve**:
- When: Viewing change history or resolving conflicts
- How: Query all versions for an attribute
- Display: Show timeline of changes

**Update**:
- Trigger: Any contact attribute changes
- Process: Create history record before updating
- Frequency: Per update

**Implementation**:
- ContactHubService wraps updates with versioning
- UI: Show "History" button in contact profile
- Diff: Highlight what changed, who changed it, when

**Dependencies**:
- ContactHubService
- AuditLogging

---

## Continued in next file...

**Status**: Features 1-40 documented (Contact Intelligence through Temporal Awareness)

**Next Section**: 
- Features 41-49: Analytics & Insights
- Features 50-59: Collaboration Features  
- Features 60-67: Optimization Features
- Features 68-78: Privacy & Security Features

---

*This document continues in the next section. Each feature's extraction/timing/storage is designed for implementation clarity and minimal hallucination risk.*

**Last Updated**: 2025-05-16  
**Next Document**: Continuation with Features 41-78
