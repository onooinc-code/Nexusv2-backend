<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            // General Settings
            ['key' => 'app_name', 'value' => 'Nexus Platform', 'type' => 'string', 'group' => 'general', 'description' => 'Application name', 'is_public' => true, 'is_encrypted' => false],
            ['key' => 'app_version', 'value' => '2.0.0', 'type' => 'string', 'group' => 'general', 'description' => 'Application version', 'is_public' => true, 'is_encrypted' => false],
            ['key' => 'timezone', 'value' => 'UTC', 'type' => 'string', 'group' => 'general', 'description' => 'System timezone', 'is_public' => false, 'is_encrypted' => false],
            ['key' => 'max_file_size', 'value' => 10485760, 'type' => 'integer', 'group' => 'general', 'description' => 'Maximum file upload size in bytes', 'is_public' => false, 'is_encrypted' => false],
            ['key' => 'session_timeout', 'value' => 3600, 'type' => 'integer', 'group' => 'general', 'description' => 'Session timeout in seconds', 'is_public' => false, 'is_encrypted' => false],

            // Security Settings
            ['key' => 'enable_2fa', 'value' => true, 'type' => 'boolean', 'group' => 'security', 'description' => 'Enable two-factor authentication', 'is_public' => false, 'is_encrypted' => false],
            ['key' => 'password_min_length', 'value' => 12, 'type' => 'integer', 'group' => 'security', 'description' => 'Minimum password length', 'is_public' => false, 'is_encrypted' => false],
            ['key' => 'password_require_special', 'value' => true, 'type' => 'boolean', 'group' => 'security', 'description' => 'Require special characters in password', 'is_public' => false, 'is_encrypted' => false],
            ['key' => 'rate_limit_enabled', 'value' => true, 'type' => 'boolean', 'group' => 'security', 'description' => 'Enable API rate limiting', 'is_public' => false, 'is_encrypted' => false],
            ['key' => 'cors_enabled', 'value' => true, 'type' => 'boolean', 'group' => 'security', 'description' => 'Enable CORS', 'is_public' => false, 'is_encrypted' => false],

            // Email Settings
            ['key' => 'mail_driver', 'value' => 'smtp', 'type' => 'string', 'group' => 'email', 'description' => 'Email driver to use', 'is_public' => false, 'is_encrypted' => false],
            ['key' => 'mail_host', 'value' => 'smtp.mailtrap.io', 'type' => 'string', 'group' => 'email', 'description' => 'Mail server host', 'is_public' => false, 'is_encrypted' => false],
            ['key' => 'mail_port', 'value' => 587, 'type' => 'integer', 'group' => 'email', 'description' => 'Mail server port', 'is_public' => false, 'is_encrypted' => false],
            ['key' => 'mail_username', 'value' => '', 'type' => 'string', 'group' => 'email', 'description' => 'Mail username', 'is_public' => false, 'is_encrypted' => true],
            ['key' => 'mail_password', 'value' => '', 'type' => 'string', 'group' => 'email', 'description' => 'Mail password', 'is_public' => false, 'is_encrypted' => true],

            // Database Settings
            ['key' => 'db_connection', 'value' => 'mysql', 'type' => 'string', 'group' => 'database', 'description' => 'Database connection type', 'is_public' => false, 'is_encrypted' => false],
            ['key' => 'db_host', 'value' => 'localhost', 'type' => 'string', 'group' => 'database', 'description' => 'Database host', 'is_public' => false, 'is_encrypted' => false],
            ['key' => 'db_port', 'value' => 3306, 'type' => 'integer', 'group' => 'database', 'description' => 'Database port', 'is_public' => false, 'is_encrypted' => false],
            ['key' => 'db_name', 'value' => 'nexus', 'type' => 'string', 'group' => 'database', 'description' => 'Database name', 'is_public' => false, 'is_encrypted' => false],

            // Cache Settings
            ['key' => 'cache_driver', 'value' => 'redis', 'type' => 'string', 'group' => 'cache', 'description' => 'Cache driver to use', 'is_public' => false, 'is_encrypted' => false],
            ['key' => 'cache_ttl', 'value' => 3600, 'type' => 'integer', 'group' => 'cache', 'description' => 'Cache time-to-live in seconds', 'is_public' => false, 'is_encrypted' => false],
            ['key' => 'cache_enabled', 'value' => true, 'type' => 'boolean', 'group' => 'cache', 'description' => 'Enable caching', 'is_public' => false, 'is_encrypted' => false],

            // Queue Settings
            ['key' => 'queue_driver', 'value' => 'redis', 'type' => 'string', 'group' => 'queue', 'description' => 'Queue driver to use', 'is_public' => false, 'is_encrypted' => false],
            ['key' => 'queue_timeout', 'value' => 300, 'type' => 'integer', 'group' => 'queue', 'description' => 'Queue job timeout in seconds', 'is_public' => false, 'is_encrypted' => false],

            // Integrations - OpenAI
            ['key' => 'openai_api_key', 'value' => '', 'type' => 'string', 'group' => 'integrations', 'description' => 'OpenAI API Key', 'is_public' => false, 'is_encrypted' => true],
            ['key' => 'openai_organization', 'value' => '', 'type' => 'string', 'group' => 'integrations', 'description' => 'OpenAI Organization ID', 'is_public' => false, 'is_encrypted' => true],

            // Integrations - Anthropic
            ['key' => 'anthropic_api_key', 'value' => '', 'type' => 'string', 'group' => 'integrations', 'description' => 'Anthropic Claude API Key', 'is_public' => false, 'is_encrypted' => true],

            // Integrations - Google Gemini
            ['key' => 'gemini_api_key', 'value' => '', 'type' => 'string', 'group' => 'integrations', 'description' => 'Google Gemini API Key', 'is_public' => false, 'is_encrypted' => true],

            // Integrations - Groq
            ['key' => 'groq_api_key', 'value' => '', 'type' => 'string', 'group' => 'integrations', 'description' => 'Groq API Key', 'is_public' => false, 'is_encrypted' => true],

            // Integrations - Pinecone
            ['key' => 'pinecone_api_key', 'value' => '', 'type' => 'string', 'group' => 'integrations', 'description' => 'Pinecone API Key', 'is_public' => false, 'is_encrypted' => true],
            ['key' => 'pinecone_environment', 'value' => '', 'type' => 'string', 'group' => 'integrations', 'description' => 'Pinecone environment', 'is_public' => false, 'is_encrypted' => false],

            // Integrations - Neo4j
            ['key' => 'neo4j_uri', 'value' => '', 'type' => 'string', 'group' => 'integrations', 'description' => 'Neo4j connection URI', 'is_public' => false, 'is_encrypted' => true],
            ['key' => 'neo4j_username', 'value' => '', 'type' => 'string', 'group' => 'integrations', 'description' => 'Neo4j username', 'is_public' => false, 'is_encrypted' => true],
            ['key' => 'neo4j_password', 'value' => '', 'type' => 'string', 'group' => 'integrations', 'description' => 'Neo4j password', 'is_public' => false, 'is_encrypted' => true],

            // Integrations - WAHA
            ['key' => 'waha_url', 'value' => '', 'type' => 'string', 'group' => 'integrations', 'description' => 'WAHA API URL', 'is_public' => false, 'is_encrypted' => false],
            ['key' => 'waha_api_key', 'value' => '', 'type' => 'string', 'group' => 'integrations', 'description' => 'WAHA API Key', 'is_public' => false, 'is_encrypted' => true],

            // Feature Flags
            ['key' => 'feature_proactive_ai', 'value' => true, 'type' => 'boolean', 'group' => 'features', 'description' => 'Enable Proactive AI features', 'is_public' => false, 'is_encrypted' => false],
            ['key' => 'feature_workflow_automation', 'value' => true, 'type' => 'boolean', 'group' => 'features', 'description' => 'Enable Workflow Automation', 'is_public' => false, 'is_encrypted' => false],
            ['key' => 'feature_agent_hub', 'value' => true, 'type' => 'boolean', 'group' => 'features', 'description' => 'Enable Agent Hub', 'is_public' => false, 'is_encrypted' => false],
            ['key' => 'feature_memory_hub', 'value' => true, 'type' => 'boolean', 'group' => 'features', 'description' => 'Enable Memory Hub', 'is_public' => false, 'is_encrypted' => false],

            // Monitoring Settings
            ['key' => 'monitor_health_checks', 'value' => true, 'type' => 'boolean', 'group' => 'monitoring', 'description' => 'Enable health checks', 'is_public' => false, 'is_encrypted' => false],
            ['key' => 'health_check_interval', 'value' => 300, 'type' => 'integer', 'group' => 'monitoring', 'description' => 'Health check interval in seconds', 'is_public' => false, 'is_encrypted' => false],
            ['key' => 'log_retention_days', 'value' => 30, 'type' => 'integer', 'group' => 'monitoring', 'description' => 'Log retention period in days', 'is_public' => false, 'is_encrypted' => false],
            ['key' => 'enable_telemetry', 'value' => true, 'type' => 'boolean', 'group' => 'monitoring', 'description' => 'Enable telemetry collection', 'is_public' => false, 'is_encrypted' => false],
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
    }
}
