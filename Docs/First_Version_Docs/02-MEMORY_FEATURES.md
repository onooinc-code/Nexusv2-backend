# 02 - Memory Features

## Purpose

Memory Features define how Nexus captures, organizes, and uses knowledge over time.
These capabilities enable the system to retain context, personalize interactions, and reason about long-term user needs.

## Scope

- Memory extraction and consolidation
- Context-aware retrieval
- Memory pruning and retention policies
- Semantic and episodic memory use cases
- Memory relevance scoring and decay
- Memory safety and privacy controls

## Core Memory Types

### Working Memory

A short-lived, high-speed context store for active sessions.
Used to maintain the current conversation state, temporary variables, and immediate task context.

### Episodic Memory

Stores discrete events, conversations, and interactions.
Captures what happened, when, and where, enabling recall of past experiences.

### Semantic Memory

Stores facts, concepts, and generalized knowledge extracted from user interactions.
Supports understanding of relationships, preferences, and world knowledge.

### Structured Memory

Stores normalized entities, relationships, and timeline data in tabular or graph form.
Enables efficient queries for contacts, tasks, and plans.

### Graph Memory

Represents entities and relationships as a graph for inference and network-based reasoning.
Supports relationship strength, influence, and entity traversal.

## Feature Set

### Memory Extraction

- Extract named entities, intents, events, and facts from conversations
- Identify explicit and implicit memory candidates
- Categorize extracted items by memory type and relevance
- Normalize and enrich memory content for better retrieval

### Memory Consolidation

- Merge duplicate or related memory entries
- Create summary representations for longer context
- Link memories to contacts, workflows, and events
- Generate contextual snapshots for fast access

### Memory Retrieval

- Perform relevance-based retrieval using embeddings and semantic similarity
- Use structured queries for precise data retrieval
- Support contextual recall in follow-up conversations
- Prioritize memories by recency, importance, and signal strength

### Memory Decay and Retention

- Apply time-based decay to reduce relevance of old memories
- Enforce retention policies by memory type and privacy classification
- Archive or delete stale memories based on policy
- Support on-demand memory forgetting and redaction

### Memory Relevance Scoring

- Score memories by recency, frequency, and importance
- Use topical and semantic similarity metrics for ranking
- Adjust scores based on user feedback and explicit relevance signals
- Detect memory drift and reweight entries accordingly

### Contextual Memory Resolution

- Resolve relevant memories for a given prompt or workflow
- Combine memory sources into a coherent context set
- Handle conflicting or outdated memories with confidence scoring
- Provide provenance and source attribution for recalled memory

### Privacy and Safety

- Protect sensitive memories with encryption and access controls
- Obfuscate or redact private data from memory retrieval results
- Audit memory access and deletion requests
- Respect user consent for storing and using memory content

## APIs and Integration

### `POST /memory/extract`

Extracts candidate memory items from an input payload.

### `POST /memory/retrieve`

Returns relevant memories for a given query or context.

### `POST /memory/prune`

Initiates retention-based pruning and archiving.

### `GET /memory/summary/{conversation_id}`

Returns consolidated memory summary for a conversation or session.

## Implementation Patterns

- Use embeddings for semantic similarity and recall
- Keep working memory lightweight and fast
- Separate storage formats for each memory type
- Validate recall results based on trust and provenance
- Support hybrid retrieval using vector and structured queries

## Example Workflows

- Summarize yesterday's meeting and recall action items
- Remember a user's preference for quiet meeting times
- Retrieve contact-specific information during a follow-up message
- Remove a memory item when the user requests forgetting

## Notes

- Memory operations should be auditable and reversible
- Retention rules should be configurable by workspace and tenant
- Memory relevance should adapt to user behavior and context
