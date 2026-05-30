# ContactsHub

Purpose
- Manage contact identities, profiles, relationships, preferences, and privacy policies.
- Provide canonicalization, alias resolution, enrichment, and contact-specific access controls.

Scope
- Contact lifecycle CRUD
- Identifier resolution and deduplication
- Relationship graph management
- Preference and tone profiling
- Privacy, consent, and erasure handling
- Enrichment integration and profile snapshots

Modules
- Contact Registry: Create, update, merge, and deactivate contacts.
- Identifier Resolver: Normalize phone, email, external IDs, and resolve aliases.
- Relationship Graph: Store and expose connections between contacts.
- Profile Engine: Assemble profile, tone, rules, and preferences.
- Consent & Privacy: Track consents and process erasure requests.
- Enrichment & Metadata: Pull data from external sources and update contact records.

API Endpoints
- `POST /api/v1/contacts`
  - Body: `{ external_ids?: array, name: string, emails?: array, phones?: array, metadata?: object }`
  - Behavior: create or upsert a contact using identifier resolution.
  - Idempotency: support `X-Idempotency-Key`.

- `GET /api/v1/contacts/{id}`
  - Behavior: return canonical contact record with profile, preferences, relationships, and rules.

- `POST /api/v1/contacts/{id}/merge`
  - Body: `{ source_contact_id: uuid, strategy: "prefer_new" | "prefer_trusted" | "manual" }`
  - Behavior: merge duplicate contact into canonical record, preserve provenance.

- `POST /api/v1/contacts/{id}/aliases`
  - Body: `{ alias_name: string, confidence: number, context: string }`
  - Behavior: add alias and optionally resolve to existing contact.

- `GET /api/v1/contacts/{id}/relationships`
  - Behavior: return relationship network and strengths.

- `GET /api/v1/contacts/{id}/preferences`
  - Behavior: return inferred and explicit preferences for prompt building.

- `DELETE /api/v1/contacts/{id}/erase`
  - Behavior: trigger GDPR erasure across memory, logs, and derived data.

Data Models
- Contact
  - `id`, `canonical_name`, `identifiers`, `profile`, `preferences`, `rules`, `created_at`, `updated_at`, `deleted_at`
- ContactIdentifier
  - `id`, `contact_id`, `type`, `value`, `trusted`, `created_at`
- ContactRelationship
  - `id`, `contact_id`, `related_contact_id`, `relationship_type`, `mention_count`, `confidence`, `updated_at`
- ContactPreference
  - `id`, `contact_id`, `preference_type`, `value`, `confidence`, `inferred_from_count`, `updated_at`
- ContactAlias
  - `id`, `primary_contact_id`, `alias_name`, `confidence`, `created_context`, `created_at`
- ContactRule
  - `id`, `contact_id`, `rule_text`, `rule_type`, `priority`, `enabled`, `created_at`

Runtime behavior
1. `ContactsHub` receives contact create/upsert request.
2. Identifier Resolver normalizes IDs and searches existing `contact_identifiers`.
3. If duplicates are found, use merge logic or return candidate contacts for manual resolution.
4. Persist contact record, identifiers, aliases, preferences, and rules.
5. Emit `contact.created` or `contact.updated` event and update `ContactsHub` cache.

Alias resolution
- When an alias is added, resolve against existing contact names and identifiers.
- Build a `contact_aliases` map in Redis for fast alias lookup.
- Use fuzzy matching and relationship signals to disambiguate aliases.

Relationship graph
- Capture explicit mentions and inferred connections.
- Support relationship types: family, work, social, vendor, partner.
- Strengthen relationship confidence with repeated mentions.
- Provide graph export to UI and `MemoryHub` for graph-based reasoning.

Privacy & consent
- Store consents in `consent_events` via `LogsHub` or `ContactsHub` integration.
- `DELETE /api/v1/contacts/{id}/erase`:
  - Mark contact `deleted_at`.
  - Queue erasure for memories, aliases, preferences, and logs.
  - Emit `contact.erased` event and record audit trail.

Enrichment
- Support `POST /api/v1/contacts/{id}/enrich` to fetch external profile data.
- Merge enrichment results into `profile` JSON and keep source provenance.
- Respect privacy flags and do not enrich contacts with `do_not_contact`.

Security & access
- Authorization: only authenticated services and authorized users may read/write contacts.
- Scoping: contact access may be limited by tenant, ownership, or privacy settings.
- Logging: audit all read and write operations.

Observability
- Metrics: contact creations, merges, alias resolutions, erasure requests.
- Tracing: propagate `trace_id` and include `contact_id` in logs.

Testing
- Unit tests for identifier resolution, merge strategies, alias linking, and privacy erasure.
- Integration tests for contact create/update flows and relationship graph queries.

Events
- `contact.created`, `contact.updated`, `contact.merged`, `contact.alias_added`, `contact.erased`.
- Published to broker and outbox for reliability.

Example OpenAPI snippet
```yaml
paths:
  /api/v1/contacts/{id}/merge:
    post:
      summary: Merge duplicate contact records
      requestBody:
        content:
          application/json:
            schema:
              type: object
              properties:
                source_contact_id: { type: string, format: uuid }
                strategy:
                  type: string
                  enum: [prefer_new, prefer_trusted, manual]
      responses:
        '200': { description: OK }
```

Next steps
- Draft `AiModelsHub` spec and define provider routing, fallback chains, and usage accounting.
