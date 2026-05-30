# 01 - Contact Intelligence

## Purpose

Contact Intelligence defines how Nexus captures, enriches, and reasons about people, organizations, and relationships.
It enables personalized assistant behavior by maintaining beliefs, preferences, affinities, and history for named contacts.

## Scope

- Contact discovery and profile enrichment
- Relationship and affinity modeling
- Preference capture and consent-aware memory
- Contact belief extraction and inference
- Privacy-safe contact context usage
- Contact lifecycle management

## Key Concepts

### Contact Profiles

A contact profile is the canonical record for a person, organization, or entity.
Profiles store structured identity data, relevant metadata, and relationship links.

Core profile fields:

- `contact_id`
- `name`
- `aliases`
- `entity_type`
- `preferred_channels`
- `communication_history`
- `metadata`
- `privacy_class`

### Beliefs

Beliefs represent inferred or remembered facts about a contact.
They may come from interactions, external sources, or explicit user input.

Common belief categories:

- preferences
- roles and relationships
- sentiment and affinity
- expertise and responsibilities
- priorities and availability

### Affinity & Relationship Graph

Affinity models the strength and quality of a relationship.
Nexus tracks connections across contacts, groups, and organizations.

Graph elements include:

- `relationship_type`
- `interaction_count`
- `last_contacted`
- `trust_score`
- `influence_score`

### Preference Signals

Preference signals are captured from explicit configuration and observed behavior.
They help personalize notifications, scheduling, suggestion ranking, and response tone.

Preference categories:

- communication style
- preferred channel
- availability windows
- privacy boundaries
- content sensitivity

## Feature Set

### Contact Ingestion

- Capture contact details from inbound messages, calendar invites, CRM data, and manual input
- Normalize names, emails, phone numbers, and organization identifiers
- Consolidate duplicate records using identity matching and heuristics

### Profile Enrichment

- Augment contact profiles with external metadata and context
- Resolve affiliations, roles, titles, and social signals
- Attach contact tags for segmentation and workflow targeting

### Relationship Intelligence

- Build and maintain a relationship graph for contacts and organizations
- Measure affinity using recency, frequency, and sentiment of interactions
- Surface close collaborators, decision makers, and escalation contacts

### Preference Capture

- Record explicit preferences from user statements and settings
- Learn implicit preferences from repeated behavior and interactions
- Protect sensitive preferences with privacy scope and audit controls

### Belief Management

- Store contact-centric beliefs as structured memory entries
- Differentiate between asserted facts, inferred beliefs, and tentative hypotheses
- Expire or demote beliefs as contact context changes

### Consent-aware Actions

- Honor consent and communication preferences when using contact data
- Respect do-not-disturb rules, channel restrictions, and privacy classifications
- Provide audit logs for contact consent decisions

### Contact-aware Suggestions

- Use contact intelligence to rank replies, meeting recommendations, and follow-up actions
- Apply relationship-aware tone adjustments for sensitive contacts
- Suggest task owners and recipients based on contact affinity

## APIs and Integration

### `GET /contacts/{contact_id}`

Returns profile, affinity, and belief summary.

### `POST /contacts/search`

Supports identity resolution by name, email, phone, and metadata.

### `POST /contacts/{contact_id}/preferences`

Updates contact-specific preferences and communication rules.

### `GET /contacts/graph/{contact_id}`

Returns relationship graph data and affinity scores.

### `POST /contacts/{contact_id}/beliefs`

Adds or updates a belief entry for the contact.

## Data Flow

1. Inbound data arrives from email, chat, calendar, or CRM.
2. `ContactsHub` ingests and normalizes contact details.
3. The system enriches the profile with relationship and preference metadata.
4. Contact beliefs and affinity scores are stored in `MemoryHub` and `StructuredMemory`.
5. Workflows use contact intelligence to personalize responses and routing.

## Security and Privacy

- Contacts are labeled by privacy classification and access scope
- Sensitive fields may be encrypted or redacted at runtime
- Contact usage is governed by user consent and retention policies
- Contact inference data is auditable and reversible

## Implementation Notes

- Treat contact data as a first-class entity across Nexus hubs
- Keep profile enrichment separate from core identity resolution
- Use graph structures for relationship reasoning, not flat contact lists
- Support both automatic and manual contact corrections

## Example Use Cases

- Suggest the best follow-up channel for an important colleague
- Automatically choose a formal tone for a senior executive
- Detect and prioritize a contact who should receive urgent updates
- Respect a contact's preference to avoid certain message types
