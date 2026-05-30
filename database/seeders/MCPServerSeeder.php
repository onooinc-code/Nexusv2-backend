<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class MCPServerSeeder extends Seeder
{
    /**
     * Seed MCP (Model Context Protocol) server configurations.
     */
    public function run(): void
    {
        $now = Carbon::now();

        $mcpServers = [
            [
                'id' => Str::uuid(),
                'name' => 'Local Tools Server',
                'description' => 'Local MCP server for built-in tools and utilities',
                'type' => 'local',
                'connection_config' => json_encode([
                    'endpoint' => 'localhost:3001',
                    'protocol' => 'http',
                    'auth_type' => 'none',
                    'timeout_ms' => 5000,
                ]),
                'status' => 'active',
                'is_default' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => Str::uuid(),
                'name' => 'External APIs Server',
                'description' => 'Remote MCP server for external API integrations',
                'type' => 'remote',
                'connection_config' => json_encode([
                    'endpoint' => 'https://mcp.external-apis.local',
                    'protocol' => 'https',
                    'auth_type' => 'bearer_token',
                    'timeout_ms' => 10000,
                ]),
                'status' => 'active',
                'is_default' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => Str::uuid(),
                'name' => 'Data Tools Server',
                'description' => 'MCP server for data processing and analytics tools',
                'type' => 'local',
                'connection_config' => json_encode([
                    'endpoint' => 'localhost:3002',
                    'protocol' => 'http',
                    'auth_type' => 'none',
                    'timeout_ms' => 15000,
                ]),
                'status' => 'inactive',
                'is_default' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => Str::uuid(),
                'name' => 'Communication Server',
                'description' => 'MCP server for email, SMS, and messaging tools',
                'type' => 'local',
                'connection_config' => json_encode([
                    'endpoint' => 'localhost:3003',
                    'protocol' => 'http',
                    'auth_type' => 'none',
                    'timeout_ms' => 8000,
                ]),
                'status' => 'active',
                'is_default' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => Str::uuid(),
                'name' => 'Knowledge Base Server',
                'description' => 'MCP server for document search and knowledge retrieval',
                'type' => 'local',
                'connection_config' => json_encode([
                    'endpoint' => 'localhost:3004',
                    'protocol' => 'http',
                    'auth_type' => 'none',
                    'timeout_ms' => 20000,
                ]),
                'status' => 'active',
                'is_default' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        foreach ($mcpServers as $server) {
            if (!DB::table('mcp_servers')->where('name', $server['name'])->exists()) {
                DB::table('mcp_servers')->insert($server);
                $this->command->line("  ✓ MCP Server: {$server['name']} ({$server['type']})");
            } else {
                $this->command->line("  ⊘ Skipping existing: {$server['name']}");
            }
        }

        $this->command->newLine();
        $this->command->info('✅ MCPServerSeeder complete — ' . count($mcpServers) . ' MCP servers configured.');
    }
}
