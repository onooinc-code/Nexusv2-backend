<?php

namespace Database\Seeders;

use App\Models\Agent;
use App\Models\AgentPersona;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AgentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $persona = AgentPersona::first();
        $personaId = $persona ? $persona->id : null;

        $agents = [
            [
                'name' => 'Data Ingestion Alpha',
                'key' => 'data_ingestion_alpha',
                'description' => 'System agent for processing incoming data pipelines.',
                'type' => 'autonomous',
                'status' => Agent::STATUS_ACTIVE,
                'is_active' => true,
                'is_system' => true,
                'owner_id' => 1,
                'persona_id' => $personaId,
                'rate_limit_per_minute' => 120,
            ],
            [
                'name' => 'Outreach Coordinator',
                'key' => 'outreach_coordinator',
                'description' => 'Handles automated communication loops.',
                'type' => 'specialized',
                'status' => Agent::STATUS_ACTIVE,
                'is_active' => true,
                'is_system' => false,
                'owner_id' => 1,
                'persona_id' => $personaId,
                'rate_limit_per_minute' => 60,
            ],
            [
                'name' => 'Memory Synthesizer',
                'key' => 'memory_synthesizer',
                'description' => 'Handles episodic memory compression.',
                'type' => 'reflection',
                'status' => Agent::STATUS_ACTIVE,
                'is_active' => true,
                'is_system' => true,
                'owner_id' => 1,
                'persona_id' => $personaId,
                'rate_limit_per_minute' => 60,
            ],
        ];

        foreach ($agents as $agentData) {
            Agent::updateOrCreate(['name' => $agentData['name']], $agentData);
        }

        $this->command->info('✅ AgentSeeder complete — ' . count($agents) . ' agents seeded.');
    }
}
