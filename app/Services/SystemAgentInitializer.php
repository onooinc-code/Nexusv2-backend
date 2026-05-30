<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\AgentPersona;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SystemAgentInitializer
{
    /**
     * The default system agents required by the Master Architecture.
     */
    protected array $defaultSystemAgents = [
        [
            'key' => 'memory-extractor',
            'name' => 'Memory Extractor Agent',
            'description' => 'Extracts episodic knowledge and structured graphs from raw conversational logs.',
            'type' => Agent::TYPE_SPECIALIZED,
            'settings' => [
                'temperature' => 0.1,
                'max_tokens' => 1024,
            ],
            'persona' => [
                'name' => 'Memory Extractor Persona',
                'description' => 'Default persona for knowledge extraction.',
                'system_prompt' => 'You are a precise data extraction agent. Extract structured entities and relationships from the provided text. Return ONLY JSON.',
                'tone_preferences' => ['tone' => 'clinical', 'style' => 'direct'],
            ]
        ],
        [
            'key' => 'intent-analyzer',
            'name' => 'Intent Analyzer Agent',
            'description' => 'Analyzes user intent to route requests to the correct hub or workflow.',
            'type' => Agent::TYPE_AUTONOMOUS,
            'settings' => [
                'temperature' => 0.3,
                'max_tokens' => 512,
            ],
            'persona' => [
                'name' => 'Intent Analyzer Persona',
                'description' => 'Default persona for intent routing.',
                'system_prompt' => 'You are an intent classification agent. Analyze the user request and determine the primary intent category. Respond concisely.',
                'tone_preferences' => ['tone' => 'neutral', 'style' => 'analytical'],
            ]
        ],
        [
            'key' => 'contact-reply',
            'name' => 'Contact Reply Agent',
            'description' => 'Generates context-aware replies for contacts based on memory and preferred tone.',
            'type' => Agent::TYPE_TEAM,
            'settings' => [
                'temperature' => 0.7,
                'max_tokens' => 2048,
            ],
            'persona' => [
                'name' => 'Contact Reply Persona',
                'description' => 'Default persona for drafting replies.',
                'system_prompt' => 'You are a communication assistant. Draft a polite and empathetic reply to the contact based on their previous messages and context.',
                'tone_preferences' => ['tone' => 'empathetic', 'style' => 'conversational'],
            ]
        ]
    ];

    /**
     * Seeds the system agents into the database if they don't already exist.
     */
    public function seed(): array
    {
        $seeded = [];
        DB::beginTransaction();

        try {
            // Ensure a default system owner exists (e.g., admin user)
            $owner = \App\Models\User::first();
            $ownerId = $owner ? $owner->id : null;

            foreach ($this->defaultSystemAgents as $config) {
                // Check if agent exists
                $agent = Agent::where('key', $config['key'])->first();

                if (!$agent) {
                    // Create Persona
                    $persona = AgentPersona::create([
                        'id' => Str::uuid()->toString(),
                        'name' => $config['persona']['name'],
                        'description' => $config['persona']['description'],
                        'system_prompt' => $config['persona']['system_prompt'],
                        'tone_preferences' => $config['persona']['tone_preferences'],
                    ]);

                    // Create Agent
                    $agent = Agent::create([
                        'id' => Str::uuid()->toString(),
                        'name' => $config['name'],
                        'key' => $config['key'],
                        'description' => $config['description'],
                        'type' => $config['type'],
                        'status' => Agent::STATUS_ACTIVE,
                        'is_system' => true,
                        'is_active' => true,
                        'owner_id' => $ownerId,
                        'persona_id' => $persona->id,
                        'settings' => $config['settings'],
                    ]);

                    $seeded[] = $agent->name;
                    Log::info("Seeded system agent: {$agent->name}");
                }
            }

            DB::commit();
            return ['success' => true, 'seeded' => $seeded];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to seed system agents: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
