<?php

namespace Database\Seeders;

use App\Models\Agent;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SystemAgentSeeder extends Seeder
{
    /**
     * Seed system agents that are protected from deletion.
     */
    public function run(): void
    {
        $systemAgents = [
            [
                'name' => 'System Router',
                'key' => 'system-router',
                'description' => 'Core system agent for routing tasks to appropriate handlers. Protected system agent.',
                'type' => 'autonomous',
                'provider' => 'openai',
                'settings' => [
                    'timeout' => 60,
                    'retries' => 5,
                    'priority' => 'critical',
                ],
                'is_system' => true,
            ],
            [
                'name' => 'Error Handler',
                'key' => 'system-error-handler',
                'description' => 'System agent for handling errors and exceptions. Protected system agent.',
                'type' => 'specialized',
                'provider' => 'openai',
                'settings' => [
                    'timeout' => 30,
                    'retries' => 3,
                    'priority' => 'high',
                ],
                'is_system' => true,
            ],
            [
                'name' => 'Event Coordinator',
                'key' => 'system-event-coordinator',
                'description' => 'System agent for coordinating events and notifications. Protected system agent.',
                'type' => 'team',
                'provider' => 'openai',
                'settings' => [
                    'timeout' => 45,
                    'retries' => 4,
                    'priority' => 'high',
                ],
                'is_system' => true,
            ],
            [
                'name' => 'Performance Monitor',
                'key' => 'system-performance-monitor',
                'description' => 'System agent for monitoring and reporting performance metrics. Protected system agent.',
                'type' => 'specialized',
                'provider' => 'openai',
                'settings' => [
                    'timeout' => 120,
                    'retries' => 2,
                    'priority' => 'medium',
                ],
                'is_system' => true,
            ],
            [
                'name' => 'Data Validator',
                'key' => 'system-data-validator',
                'description' => 'System agent for validating data integrity and quality. Protected system agent.',
                'type' => 'specialized',
                'provider' => 'openai',
                'settings' => [
                    'timeout' => 90,
                    'retries' => 3,
                    'priority' => 'medium',
                ],
                'is_system' => true,
            ],
        ];

        foreach ($systemAgents as $agentData) {
            Agent::updateOrCreate(
                ['key' => $agentData['key']],
                array_merge($agentData, [
                    'is_active' => true,
                    'metadata' => array_merge(
                        $agentData['settings'] ?? [],
                        ['system_agent' => true, 'protected' => true]
                    ),
                ])
            );
        }

        $this->command->info('✅ SystemAgentSeeder complete — ' . count($systemAgents) . ' system agents seeded.');
        $this->command->warn('⚠️ System agents are protected and cannot be deleted.');
    }
}
