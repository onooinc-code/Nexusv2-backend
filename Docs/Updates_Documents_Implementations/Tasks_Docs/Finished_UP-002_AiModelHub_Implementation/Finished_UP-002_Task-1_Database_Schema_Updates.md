# 🎯 TASK: UP-002 - Task 1: Database Schema Updates
- **Status:** 🔴 PENDING
- **Dependencies:** 

## 1. Objective
Create the necessary database tables for the AI Models Hub: ai_providers, ai_models (updated), ai_api_keys, and intent_routing according to the Blueprint v2.0 specification.

## 2. Files to Create/Modify
- `database/migrations/2026_05_19_000001_create_ai_providers_table.php`: Create ai_providers table
- `database/migrations/2026_05_19_000002_update_ai_models_table.php`: Update ai_models table schema
- `database/migrations/2026_05_19_000003_create_ai_api_keys_table.php`: Create ai_api_keys table
- `database/migrations/2026_05_19_000004_create_intent_routing_table.php`: Create intent_routing table

## 3. Implementation Steps
1. Create migration for ai_providers table with UUID primary key, name, base_url, models_fetch_endpoint, generate_endpoint, auth_header_format, payload_format, is_active, timestamps
2. Create migration to update ai_models table: change id to UUID, add provider_id foreign key, add context_window, input_cost_per_m, output_cost_per_m, last_synced_at, remove provider, external_id, description, capabilities, metadata, status columns
3. Create migration for ai_api_keys table with UUID primary key, provider_id foreign key, key_hash (encrypted), and appropriate indexes
4. Create migration for intent_routing table with UUID primary key, intent_name (unique), default_provider_id foreign key, default_model_id foreign key, fallback_provider_id foreign key (nullable), fallback_model_id foreign key (nullable), timestamps
5. Run migrations to apply changes to database

## ✅ Final Verification
- [ ] Code is complete (No placeholders).
- [ ] Checked against existing project versions.
- [ ] Does not break dependent features.