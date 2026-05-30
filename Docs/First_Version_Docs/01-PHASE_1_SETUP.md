# Phase 1: Environment & Project Setup

## 🎯 Goal
To establish a stable, production-ready Laravel environment with all necessary third-party integrations and local development tools.

---

## 1. Project Initialization
- **Command**: `laravel new nexus --github --pest --vue`
- **Updates**: 
    - Setup `composer.json` with required packages: `spatie/laravel-permission`, `openai-php/laravel`, `pinecone-php/client`.
    - Setup `package.json` with `lucide-vue-next`, `framer-motion`, and `tailwindcss`.

---

## 2. Environment Configuration (`.env`)
Ensure the following are configured and documented in the `.env.example`:
- **DB_CONNECTION**: `mysql` (v8.0+).
- **CACHE_DRIVER**: `redis`.
- **QUEUE_CONNECTION**: `redis`.
- **VITE_REVERB_HOST**: For real-time WebSocket communication.
- **AI_KEYS**: OpenAI, Anthropic, Gemini.
- **VECTOR_STORE**: Pinecone or Milvus credentials.

---

## 3. Database & Storage Setup
- **Migrations**: Run core migrations for `users`, `personal_access_tokens`, and `failed_jobs`.
- **Storage**: Link local storage (`php artisan storage:link`) and configure S3 buckets for `audit-screenshots` and `memory-archives`.

---

## 4. Developer Experience (DX)
- **Local Dev**: Setup **Laravel Sail** with Docker.
- **Linters**: Configure `pint` and `eslint`.
- **Testing**: Initialize **Pest** with a basic "System Health" test case.

---

## 5. Success Criteria
- [ ] Application responds on `nexus.test`.
- [ ] Database, Redis, and Reverb connections are verified as "Connected."
- [ ] `php artisan test` passes with 0 failures.
