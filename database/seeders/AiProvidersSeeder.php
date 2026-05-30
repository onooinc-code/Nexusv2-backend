<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AiProvidersSeeder extends Seeder
{
    /**
     * Seed 10 AI providers and their models with full routing profiles.
     */
    public function run(): void
    {
        $now = Carbon::now();

        $providers = [
            // ── 1. OpenAI ────────────────────────────────────────────────────────────
            [
                'provider' => [
                    'id'                     => Str::uuid(),
                    'name'                   => 'OpenAI',
                    'base_url'               => 'https://api.openai.com',
                    'models_fetch_endpoint'  => '/v1/models',
                    'generate_endpoint'      => '/v1/chat/completions',
                    'auth_header_format'     => 'Bearer {key}',
                    'payload_format'         => 'openai',
                    'is_active'              => true,
                    'created_at'             => $now,
                    'updated_at'             => $now,
                ],
                'models' => [
                    ['name' => 'gpt-4o',           'external_id' => 'gpt-4o',           'quality_tier' => 'premium',  'cost_profile' => 'high',   'latency_profile' => 'balanced', 'security_class' => 'standard',   'language_support' => ['en','ar','fr','de','es','zh']],
                    ['name' => 'gpt-4o-mini',      'external_id' => 'gpt-4o-mini',      'quality_tier' => 'standard', 'cost_profile' => 'medium', 'latency_profile' => 'fast',     'security_class' => 'standard',   'language_support' => ['en','ar','fr','de','es']],
                    ['name' => 'gpt-4-turbo',      'external_id' => 'gpt-4-turbo',      'quality_tier' => 'premium',  'cost_profile' => 'high',   'latency_profile' => 'balanced', 'security_class' => 'sensitive',  'language_support' => ['en','fr','de','es','zh']],
                    ['name' => 'gpt-3.5-turbo',    'external_id' => 'gpt-3.5-turbo',    'quality_tier' => 'basic',    'cost_profile' => 'low',    'latency_profile' => 'fast',     'security_class' => 'standard',   'language_support' => ['en','ar']],
                ],
            ],

            // ── 2. Anthropic ──────────────────────────────────────────────────────────
            [
                'provider' => [
                    'id'                     => Str::uuid(),
                    'name'                   => 'Anthropic',
                    'base_url'               => 'https://api.anthropic.com',
                    'models_fetch_endpoint'  => '/v1/models',
                    'generate_endpoint'      => '/v1/messages',
                    'auth_header_format'     => 'x-api-key {key}',
                    'payload_format'         => 'anthropic',
                    'is_active'              => true,
                    'created_at'             => $now,
                    'updated_at'             => $now,
                ],
                'models' => [
                    ['name' => 'claude-opus-4',    'external_id' => 'claude-opus-4-20251101',    'quality_tier' => 'premium',  'cost_profile' => 'high',   'latency_profile' => 'safe',     'security_class' => 'restricted', 'language_support' => ['en','fr','de','es']],
                    ['name' => 'claude-sonnet-4',  'external_id' => 'claude-sonnet-4-20251101',  'quality_tier' => 'premium',  'cost_profile' => 'medium', 'latency_profile' => 'balanced', 'security_class' => 'sensitive',  'language_support' => ['en','ar','fr','de']],
                    ['name' => 'claude-haiku-3.5', 'external_id' => 'claude-haiku-3-5-20241022', 'quality_tier' => 'standard', 'cost_profile' => 'low',    'latency_profile' => 'fast',     'security_class' => 'standard',   'language_support' => ['en','ar']],
                ],
            ],

            // ── 3. Google Gemini ──────────────────────────────────────────────────────
            [
                'provider' => [
                    'id'                     => Str::uuid(),
                    'name'                   => 'Google Gemini',
                    'base_url'               => 'https://generativelanguage.googleapis.com',
                    'models_fetch_endpoint'  => '/v1beta/models',
                    'generate_endpoint'      => '/v1beta/models/gemini-2.5-pro:generateContent',
                    'auth_header_format'     => 'Bearer {key}',
                    'payload_format'         => 'gemini',
                    'is_active'              => true,
                    'created_at'             => $now,
                    'updated_at'             => $now,
                ],
                'models' => [
                    ['name' => 'gemini-2.5-pro',   'external_id' => 'gemini-2.5-pro',   'quality_tier' => 'premium',  'cost_profile' => 'high',   'latency_profile' => 'balanced', 'security_class' => 'standard',   'language_support' => ['en','ar','fr','de','es','zh','hi']],
                    ['name' => 'gemini-2.5-flash', 'external_id' => 'gemini-2.5-flash', 'quality_tier' => 'standard', 'cost_profile' => 'low',    'latency_profile' => 'fast',     'security_class' => 'standard',   'language_support' => ['en','ar','fr']],
                    ['name' => 'gemini-1.5-pro',   'external_id' => 'gemini-1.5-pro',   'quality_tier' => 'premium',  'cost_profile' => 'medium', 'latency_profile' => 'balanced', 'security_class' => 'sensitive',  'language_support' => ['en','de','es','zh']],
                ],
            ],

            // ── 4. Groq ───────────────────────────────────────────────────────────────
            [
                'provider' => [
                    'id'                     => Str::uuid(),
                    'name'                   => 'Groq',
                    'base_url'               => 'https://api.groq.com',
                    'models_fetch_endpoint'  => '/openai/v1/models',
                    'generate_endpoint'      => '/openai/v1/chat/completions',
                    'auth_header_format'     => 'Bearer {key}',
                    'payload_format'         => 'openai',
                    'is_active'              => true,
                    'created_at'             => $now,
                    'updated_at'             => $now,
                ],
                'models' => [
                    ['name' => 'llama-3.3-70b-versatile', 'external_id' => 'llama-3.3-70b-versatile', 'quality_tier' => 'premium',  'cost_profile' => 'low',  'latency_profile' => 'fast', 'security_class' => 'standard', 'language_support' => ['en','ar','fr','de','es']],
                    ['name' => 'llama-3.1-8b-instant',    'external_id' => 'llama-3.1-8b-instant',    'quality_tier' => 'basic',    'cost_profile' => 'low',  'latency_profile' => 'fast', 'security_class' => 'standard', 'language_support' => ['en']],
                    ['name' => 'mixtral-8x7b-32768',      'external_id' => 'mixtral-8x7b-32768',      'quality_tier' => 'standard', 'cost_profile' => 'low',  'latency_profile' => 'fast', 'security_class' => 'standard', 'language_support' => ['en','fr','de']],
                    ['name' => 'gemma2-9b-it',            'external_id' => 'gemma2-9b-it',            'quality_tier' => 'basic',    'cost_profile' => 'low',  'latency_profile' => 'fast', 'security_class' => 'standard', 'language_support' => ['en']],
                ],
            ],

            // ── 5. Mistral AI ─────────────────────────────────────────────────────────
            [
                'provider' => [
                    'id'                     => Str::uuid(),
                    'name'                   => 'Mistral AI',
                    'base_url'               => 'https://api.mistral.ai',
                    'models_fetch_endpoint'  => '/v1/models',
                    'generate_endpoint'      => '/v1/chat/completions',
                    'auth_header_format'     => 'Bearer {key}',
                    'payload_format'         => 'openai',
                    'is_active'              => true,
                    'created_at'             => $now,
                    'updated_at'             => $now,
                ],
                'models' => [
                    ['name' => 'mistral-large-latest',  'external_id' => 'mistral-large-latest',  'quality_tier' => 'premium',  'cost_profile' => 'high',   'latency_profile' => 'balanced', 'security_class' => 'sensitive',  'language_support' => ['en','fr','de','es','it']],
                    ['name' => 'mistral-small-latest',  'external_id' => 'mistral-small-latest',  'quality_tier' => 'standard', 'cost_profile' => 'low',    'latency_profile' => 'fast',     'security_class' => 'standard',   'language_support' => ['en','fr']],
                    ['name' => 'codestral-latest',      'external_id' => 'codestral-latest',      'quality_tier' => 'premium',  'cost_profile' => 'medium', 'latency_profile' => 'balanced', 'security_class' => 'standard',   'language_support' => ['en']],
                ],
            ],

            // ── 6. Cohere ─────────────────────────────────────────────────────────────
            [
                'provider' => [
                    'id'                     => Str::uuid(),
                    'name'                   => 'Cohere',
                    'base_url'               => 'https://api.cohere.com',
                    'models_fetch_endpoint'  => '/v1/models',
                    'generate_endpoint'      => '/v2/chat',
                    'auth_header_format'     => 'Bearer {key}',
                    'payload_format'         => 'cohere',
                    'is_active'              => true,
                    'created_at'             => $now,
                    'updated_at'             => $now,
                ],
                'models' => [
                    ['name' => 'command-r-plus-08-2024', 'external_id' => 'command-r-plus-08-2024', 'quality_tier' => 'premium',  'cost_profile' => 'high',   'latency_profile' => 'balanced', 'security_class' => 'sensitive',  'language_support' => ['en','fr','de','es','ar','zh']],
                    ['name' => 'command-r-08-2024',      'external_id' => 'command-r-08-2024',      'quality_tier' => 'standard', 'cost_profile' => 'medium', 'latency_profile' => 'balanced', 'security_class' => 'standard',   'language_support' => ['en','ar','fr']],
                    ['name' => 'command-light',          'external_id' => 'command-light',          'quality_tier' => 'basic',    'cost_profile' => 'low',    'latency_profile' => 'fast',     'security_class' => 'standard',   'language_support' => ['en']],
                ],
            ],

            // ── 7. Together AI ────────────────────────────────────────────────────────
            [
                'provider' => [
                    'id'                     => Str::uuid(),
                    'name'                   => 'Together AI',
                    'base_url'               => 'https://api.together.xyz',
                    'models_fetch_endpoint'  => '/v1/models',
                    'generate_endpoint'      => '/v1/chat/completions',
                    'auth_header_format'     => 'Bearer {key}',
                    'payload_format'         => 'openai',
                    'is_active'              => true,
                    'created_at'             => $now,
                    'updated_at'             => $now,
                ],
                'models' => [
                    ['name' => 'Llama-4-Maverick-17B',  'external_id' => 'meta-llama/Llama-4-Maverick-17B-128E-Instruct-FP8', 'quality_tier' => 'premium',  'cost_profile' => 'medium', 'latency_profile' => 'balanced', 'security_class' => 'standard', 'language_support' => ['en','ar']],
                    ['name' => 'DeepSeek-V3',           'external_id' => 'deepseek-ai/DeepSeek-V3',                            'quality_tier' => 'premium',  'cost_profile' => 'low',    'latency_profile' => 'balanced', 'security_class' => 'standard', 'language_support' => ['en','zh']],
                    ['name' => 'Qwen2.5-72B-Instruct',  'external_id' => 'Qwen/Qwen2.5-72B-Instruct-Turbo',                   'quality_tier' => 'standard', 'cost_profile' => 'low',    'latency_profile' => 'fast',     'security_class' => 'standard', 'language_support' => ['en','zh','ar']],
                ],
            ],

            // ── 8. Perplexity AI ──────────────────────────────────────────────────────
            [
                'provider' => [
                    'id'                     => Str::uuid(),
                    'name'                   => 'Perplexity AI',
                    'base_url'               => 'https://api.perplexity.ai',
                    'models_fetch_endpoint'  => '/models',
                    'generate_endpoint'      => '/chat/completions',
                    'auth_header_format'     => 'Bearer {key}',
                    'payload_format'         => 'openai',
                    'is_active'              => true,
                    'created_at'             => $now,
                    'updated_at'             => $now,
                ],
                'models' => [
                    ['name' => 'sonar-pro',          'external_id' => 'sonar-pro',          'quality_tier' => 'premium',  'cost_profile' => 'high',   'latency_profile' => 'balanced', 'security_class' => 'standard', 'language_support' => ['en','ar','fr','de']],
                    ['name' => 'sonar',              'external_id' => 'sonar',              'quality_tier' => 'standard', 'cost_profile' => 'medium', 'latency_profile' => 'fast',     'security_class' => 'standard', 'language_support' => ['en']],
                    ['name' => 'sonar-deep-research','external_id' => 'sonar-deep-research','quality_tier' => 'premium',  'cost_profile' => 'high',   'latency_profile' => 'safe',     'security_class' => 'sensitive','language_support' => ['en','fr']],
                ],
            ],

            // ── 9. xAI (Grok) ─────────────────────────────────────────────────────────
            [
                'provider' => [
                    'id'                     => Str::uuid(),
                    'name'                   => 'xAI (Grok)',
                    'base_url'               => 'https://api.x.ai',
                    'models_fetch_endpoint'  => '/v1/models',
                    'generate_endpoint'      => '/v1/chat/completions',
                    'auth_header_format'     => 'Bearer {key}',
                    'payload_format'         => 'openai',
                    'is_active'              => true,
                    'created_at'             => $now,
                    'updated_at'             => $now,
                ],
                'models' => [
                    ['name' => 'grok-3',        'external_id' => 'grok-3',        'quality_tier' => 'premium',  'cost_profile' => 'high',   'latency_profile' => 'balanced', 'security_class' => 'standard', 'language_support' => ['en']],
                    ['name' => 'grok-3-mini',   'external_id' => 'grok-3-mini',   'quality_tier' => 'standard', 'cost_profile' => 'medium', 'latency_profile' => 'fast',     'security_class' => 'standard', 'language_support' => ['en']],
                    ['name' => 'grok-2-vision', 'external_id' => 'grok-2-vision-1212', 'quality_tier' => 'premium', 'cost_profile' => 'high', 'latency_profile' => 'balanced', 'security_class' => 'standard', 'language_support' => ['en']],
                ],
            ],

            // ── 10. DeepSeek ──────────────────────────────────────────────────────────
            [
                'provider' => [
                    'id'                     => Str::uuid(),
                    'name'                   => 'DeepSeek',
                    'base_url'               => 'https://api.deepseek.com',
                    'models_fetch_endpoint'  => '/models',
                    'generate_endpoint'      => '/chat/completions',
                    'auth_header_format'     => 'Bearer {key}',
                    'payload_format'         => 'openai',
                    'is_active'              => true,
                    'created_at'             => $now,
                    'updated_at'             => $now,
                ],
                'models' => [
                    ['name' => 'deepseek-chat',     'external_id' => 'deepseek-chat',     'quality_tier' => 'premium',  'cost_profile' => 'low',  'latency_profile' => 'balanced', 'security_class' => 'standard', 'language_support' => ['en','zh','ar']],
                    ['name' => 'deepseek-reasoner', 'external_id' => 'deepseek-reasoner', 'quality_tier' => 'premium',  'cost_profile' => 'medium','latency_profile' => 'safe',    'security_class' => 'sensitive','language_support' => ['en','zh']],
                    ['name' => 'deepseek-coder',    'external_id' => 'deepseek-coder',    'quality_tier' => 'standard', 'cost_profile' => 'low',  'latency_profile' => 'fast',     'security_class' => 'standard', 'language_support' => ['en']],
                ],
            ],
        ];

        foreach ($providers as $entry) {
            $providerData = $entry['provider'];

            // Skip if already seeded (idempotent)
            if (DB::table('ai_providers')->where('name', $providerData['name'])->exists()) {
                $this->command->info("Skipping existing provider: {$providerData['name']}");
                continue;
            }

            DB::table('ai_providers')->insert($providerData);
            $this->command->info("Seeded provider: {$providerData['name']}");

            foreach ($entry['models'] as $modelData) {
                $modelId = Str::uuid()->toString();

                DB::table('ai_models')->insert([
                    'id'               => $modelId,
                    'provider_id'      => $providerData['id'],
                    'name'             => $modelData['name'],
                    'quality_tier'     => $modelData['quality_tier'],
                    'cost_profile'     => $modelData['cost_profile'],
                    'latency_profile'  => $modelData['latency_profile'],
                    'security_class'   => $modelData['security_class'],
                    'language_support' => json_encode($modelData['language_support']),
                    'created_at'       => $now,
                    'updated_at'       => $now,
                ]);

                $this->command->line("  ↳ Model: {$modelData['name']}");
            }
        }

        $this->command->newLine();
        $this->command->info('✅ AiProvidersSeeder complete — 10 providers and their models seeded.');
    }
}
