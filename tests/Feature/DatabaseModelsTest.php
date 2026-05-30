<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DatabaseModelsTest extends TestCase
{
    use RefreshDatabase;

    public function test_phase_two_tables_exist()
    {
        $tables = [
            'contacts',
            'topics',
            'conversations',
            'messages',
            'conversation_sessions',
            'contact_rules',
            'contact_notes',
            'contact_tags',
            'contact_custom_fields',
            'memories',
            'agents',
            'agent_tools',
            'agent_skills',
            'agent_tasks',
            'task_steps',
            'settings',
            'logs',
            'ai_models',
            'api_keys',
            'sessions',
        ];

        foreach ($tables as $table) {
            $this->assertTrue(Schema::hasTable($table), "The table '{$table}' should exist.");
        }
    }

    public function test_conversation_sessions_table_has_expected_columns()
    {
        $columns = [
            'conversation_id',
            'name',
            'status',
            'source',
            'metadata',
            'started_at',
            'ended_at',
        ];

        foreach ($columns as $column) {
            $this->assertTrue(Schema::hasColumn('conversation_sessions', $column), "The conversation_sessions table should include the '{$column}' column.");
        }
    }
}
