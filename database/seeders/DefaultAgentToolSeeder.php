<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class DefaultAgentToolSeeder extends Seeder
{
    /**
     * Seed default agent tools available system-wide.
     */
    public function run(): void
    {
        $now = Carbon::now();

        $tools = [
            // Data Tools
            [
                'name' => 'Database Query',
                'description' => 'Execute SELECT queries against the database',
                'category' => 'data',
                'type' => 'query',
                'is_system' => true,
                'config' => json_encode(['timeout' => 30, 'max_rows' => 1000]),
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Data Export',
                'description' => 'Export data to CSV, JSON, or Excel formats',
                'category' => 'data',
                'type' => 'export',
                'is_system' => true,
                'config' => json_encode(['formats' => ['csv', 'json', 'xlsx'], 'max_size_mb' => 100]),
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Data Transformation',
                'description' => 'Transform and clean data using predefined operations',
                'category' => 'data',
                'type' => 'transform',
                'is_system' => true,
                'config' => json_encode(['operations' => ['filter', 'map', 'reduce', 'sort']]),
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // Communication Tools
            [
                'name' => 'Send Email',
                'description' => 'Send emails through configured mail service',
                'category' => 'communication',
                'type' => 'email',
                'is_system' => true,
                'config' => json_encode(['requires_auth' => true, 'rate_limit' => 100]),
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Send Notification',
                'description' => 'Send in-app notifications to users',
                'category' => 'communication',
                'type' => 'notification',
                'is_system' => true,
                'config' => json_encode(['channels' => ['email', 'sms', 'push', 'in_app']]),
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Schedule Message',
                'description' => 'Schedule a message to be sent at a later time',
                'category' => 'communication',
                'type' => 'scheduled_message',
                'is_system' => true,
                'config' => json_encode(['timezone_support' => true, 'max_future_days' => 365]),
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // Workflow Tools
            [
                'name' => 'Trigger Workflow',
                'description' => 'Trigger another workflow from within an agent',
                'category' => 'workflow',
                'type' => 'trigger',
                'is_system' => true,
                'config' => json_encode(['requires_auth' => true, 'supports_async' => true]),
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Queue Job',
                'description' => 'Queue a job for asynchronous processing',
                'category' => 'workflow',
                'type' => 'queue_job',
                'is_system' => true,
                'config' => json_encode(['queues' => ['default', 'high', 'low'], 'retry_max' => 3]),
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Schedule Task',
                'description' => 'Schedule a task to run at specific times',
                'category' => 'workflow',
                'type' => 'schedule_task',
                'is_system' => true,
                'config' => json_encode(['cron_support' => true, 'timezone_support' => true]),
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // Integration Tools
            [
                'name' => 'API Call',
                'description' => 'Make HTTP requests to external APIs',
                'category' => 'integration',
                'type' => 'http_request',
                'is_system' => true,
                'config' => json_encode(['methods' => ['GET', 'POST', 'PUT', 'DELETE', 'PATCH'], 'timeout' => 30]),
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Webhook Call',
                'description' => 'Send data to configured webhooks',
                'category' => 'integration',
                'type' => 'webhook',
                'is_system' => true,
                'config' => json_encode(['retry_policy' => 'exponential', 'max_retries' => 5]),
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // Utility Tools
            [
                'name' => 'Log Event',
                'description' => 'Log events for audit trail and monitoring',
                'category' => 'utility',
                'type' => 'logging',
                'is_system' => true,
                'config' => json_encode(['levels' => ['debug', 'info', 'warning', 'error', 'critical']]),
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Set Timer',
                'description' => 'Set a timer for delayed execution',
                'category' => 'utility',
                'type' => 'timer',
                'is_system' => true,
                'config' => json_encode(['min_seconds' => 1, 'max_seconds' => 86400]),
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Store Variable',
                'description' => 'Store and retrieve variables for workflow state',
                'category' => 'utility',
                'type' => 'state_storage',
                'is_system' => true,
                'config' => json_encode(['max_size_bytes' => 1000000, 'ttl_hours' => 24]),
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // Analysis Tools
            [
                'name' => 'Sentiment Analysis',
                'description' => 'Analyze sentiment of text content',
                'category' => 'analysis',
                'type' => 'sentiment',
                'is_system' => true,
                'config' => json_encode(['languages' => ['en', 'ar', 'fr', 'de', 'es', 'zh']]),
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Content Classification',
                'description' => 'Classify content into predefined categories',
                'category' => 'analysis',
                'type' => 'classification',
                'is_system' => true,
                'config' => json_encode(['model' => 'default', 'confidence_threshold' => 0.7]),
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Text Summarization',
                'description' => 'Generate summaries of long text content',
                'category' => 'analysis',
                'type' => 'summarization',
                'is_system' => true,
                'config' => json_encode(['max_length' => 500, 'summary_ratio' => 0.3]),
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        foreach ($tools as $tool) {
            if (!DB::table('agent_tools_library')->where('name', $tool['name'])->exists()) {
                DB::table('agent_tools_library')->insert(array_merge(['id' => Str::uuid()], $tool));
                $this->command->line("  ✓ Tool: {$tool['name']} ({$tool['category']})");
            }
        }

        $this->command->newLine();
        $this->command->info('✅ DefaultAgentToolSeeder complete — ' . count($tools) . ' system tools seeded.');
    }
}
