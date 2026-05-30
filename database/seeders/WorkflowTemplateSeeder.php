<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;

class WorkflowTemplateSeeder extends Seeder
{
    protected array $templates = [
        [
            'name' => 'Contact Onboarding',
            'key' => 'contact-onboarding',
            'description' => 'Automated workflow for new contact onboarding',
            'trigger_type' => 'event',
            'trigger_config' => ['event' => 'contact.created'],
            'steps' => [
                [
                    'name' => 'Create contact profile',
                    'action' => 'agent',
                    'agent_type' => 'autonomous',
                    'task' => 'Initialize new contact profile',
                    'step_order' => 1,
                ],
                [
                    'name' => 'Send welcome message',
                    'action' => 'agent',
                    'agent_type' => 'autonomous',
                    'task' => 'Send welcome message to new contact',
                    'step_order' => 2,
                ],
                [
                    'name' => 'Log onboarding',
                    'action' => 'log',
                    'message' => 'Contact onboarded successfully',
                    'step_order' => 3,
                ],
            ],
            'settings' => [
                'max_retries' => 3,
                'retry_delay' => 60,
                'timeout' => 300,
            ],
        ],
        [
            'name' => 'Daily Summary',
            'key' => 'daily-summary',
            'description' => 'Generate daily summary of activities',
            'trigger_type' => 'scheduled',
            'trigger_config' => ['schedule' => '0 8 * * *', 'timezone' => 'UTC'],
            'steps' => [
                [
                    'name' => 'Collect daily data',
                    'action' => 'agent',
                    'agent_type' => 'autonomous',
                    'task' => 'Collect daily activity data',
                    'step_order' => 1,
                ],
                [
                    'name' => 'Generate summary',
                    'action' => 'agent',
                    'agent_type' => 'reflection',
                    'task' => 'Generate daily summary report',
                    'step_order' => 2,
                ],
                [
                    'name' => 'Send notification',
                    'action' => 'agent',
                    'agent_type' => 'autonomous',
                    'task' => 'Send daily summary notification',
                    'step_order' => 3,
                ],
            ],
            'settings' => [
                'max_retries' => 2,
                'retry_delay' => 300,
                'timeout' => 600,
            ],
        ],
        [
            'name' => 'Error Recovery',
            'key' => 'error-recovery',
            'description' => 'Automated error detection and recovery',
            'trigger_type' => 'event',
            'trigger_config' => ['event' => 'error.detected'],
            'steps' => [
                [
                    'name' => 'Detect error',
                    'action' => 'condition',
                    'condition' => ['field' => 'status', 'operator' => '==', 'value' => 'error'],
                    'step_order' => 1,
                ],
                [
                    'name' => 'Retry operation',
                    'action' => 'agent',
                    'agent_type' => 'autonomous',
                    'task' => 'Retry failed operation',
                    'step_order' => 2,
                    'max_retries' => 3,
                    'retry_delay' => 60,
                ],
                [
                    'name' => 'Alert if failed',
                    'action' => 'log',
                    'message' => 'Recovery failed - manual intervention required',
                    'step_order' => 3,
                ],
            ],
            'settings' => [
                'max_retries' => 3,
                'retry_delay' => 60,
                'timeout' => 300,
            ],
        ],
        [
            'name' => 'Contact Analysis',
            'key' => 'contact-analysis',
            'description' => 'Deep analysis of contact interactions',
            'trigger_type' => 'manual',
            'steps' => [
                [
                    'name' => 'Gather contact data',
                    'action' => 'agent',
                    'agent_type' => 'autonomous',
                    'task' => 'Gather all contact interaction data',
                    'step_order' => 1,
                ],
                [
                    'name' => 'Analyze sentiment',
                    'action' => 'agent',
                    'agent_type' => 'reflection',
                    'task' => 'Analyze contact sentiment patterns',
                    'step_order' => 2,
                ],
                [
                    'name' => 'Generate insights',
                    'action' => 'agent',
                    'agent_type' => 'specialized',
                    'task' => 'Generate contact insights',
                    'step_order' => 3,
                ],
            ],
            'settings' => [
                'max_retries' => 2,
                'retry_delay' => 120,
                'timeout' => 900,
            ],
        ],
    ];

    public function run(): void
    {
        foreach ($this->templates as $template) {
            \App\Models\Workflow::updateOrCreate(
                ['key' => $template['key']],
                Arr::except($template, ['key'])
            );
        }

        $this->command->info('Workflow templates seeded successfully.');
    }
}
