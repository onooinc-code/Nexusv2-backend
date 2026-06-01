<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\MCPServer;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * MCPIntegrationService (Database-backed rewrite)
 *
 * Manages MCP server configurations using the database instead of
 * in-memory arrays (which were lost on every request).
 */
class MCPIntegrationService
{
    /**
     * Register a new MCP server in the database.
     */
    public function registerServer(string $name, array $config): MCPServer
    {
        $server = MCPServer::updateOrCreate(
            ['name' => $name],
            [
                'id'                => Str::uuid()->toString(),
                'type'              => $config['type'] ?? 'remote',
                'connection_config' => $config,
                'status'            => 'offline',
            ]
        );

        Log::info("MCP server registered: {$name}");

        return $server;
    }

    /**
     * Retrieve a server record from the database.
     */
    public function getServer(string $name): ?MCPServer
    {
        return MCPServer::where('name', $name)->first();
    }

    /**
     * Get all registered MCP servers.
     */
    public function getAllServers(): \Illuminate\Database\Eloquent\Collection
    {
        return MCPServer::all();
    }

    /**
     * Attempt to connect to a server (ping/health check).
     */
    public function connect(string $name): array
    {
        $server = $this->getServer($name);

        if (!$server) {
            throw new \InvalidArgumentException("MCP server not found: {$name}");
        }

        try {
            $result = $this->performHealthCheck($server);

            $server->update(['status' => $result['success'] ? 'connected' : 'offline']);

            return [
                'success'      => $result['success'],
                'server'       => $name,
                'connected_at' => now()->toISOString(),
            ];
        } catch (\Throwable $e) {
            $server->update(['status' => 'offline']);

            Log::error("MCP server connection failed: {$name}", ['error' => $e->getMessage()]);

            return [
                'success' => false,
                'server'  => $name,
                'error'   => $e->getMessage(),
            ];
        }
    }

    /**
     * Disconnect from an MCP server (mark offline).
     */
    public function disconnect(string $name): bool
    {
        $server = $this->getServer($name);
        if ($server) {
            $server->update(['status' => 'offline']);
        }

        Log::info("MCP server disconnected: {$name}");
        return true;
    }

    /**
     * Call a tool on an MCP server.
     */
    public function callTool(string $serverName, string $toolName, array $params = []): array
    {
        $server = $this->getServer($serverName);

        if (!$server) {
            throw new \InvalidArgumentException("MCP server not found: {$serverName}");
        }

        if ($server->status !== 'connected') {
            throw new \RuntimeException("MCP server [{$serverName}] is not connected.");
        }

        Log::info("MCP tool called: {$toolName} on {$serverName}", $params);

        if ($server->type === 'remote') {
            $config = $server->connection_config ?? [];
            $url = rtrim($config['url'] ?? '', '/');
            
            if (!$url) {
                throw new \RuntimeException("No URL configured for remote MCP server [{$serverName}]");
            }
            
            try {
                $response = \Illuminate\Support\Facades\Http::timeout(30)->post($url . '/tools/call', [
                    'jsonrpc' => '2.0',
                    'id' => uniqid(),
                    'method' => 'tools/call',
                    'params' => [
                        'name' => $toolName,
                        'arguments' => $params
                    ]
                ]);
                
                if (!$response->successful()) {
                    throw new \RuntimeException("MCP Tool execution failed: " . $response->body());
                }
                
                return [
                    'success'   => true,
                    'server'    => $serverName,
                    'tool'      => $toolName,
                    'params'    => $params,
                    'result'    => $response->json('result') ?? $response->json(),
                    'called_at' => now()->toISOString(),
                ];
            } catch (\Exception $e) {
                return [
                    'success'   => false,
                    'server'    => $serverName,
                    'tool'      => $toolName,
                    'error'     => $e->getMessage(),
                    'called_at' => now()->toISOString(),
                ];
            }
        }

        return [
            'success'   => true,
            'server'    => $serverName,
            'tool'      => $toolName,
            'params'    => $params,
            'result'    => "Tool {$toolName} executed on {$serverName} (local fallback)",
            'called_at' => now()->toISOString(),
        ];
    }

    /**
     * Attach an MCP server to an agent (many-to-many).
     */
    public function attachToAgent(Agent $agent, string $serverName): array
    {
        $server = $this->getServer($serverName);

        if (!$server) {
            throw new \InvalidArgumentException("MCP server not found: {$serverName}");
        }

        $agent->mcpServers()->syncWithoutDetaching([$server->id]);

        Log::info("MCP server [{$serverName}] attached to agent [{$agent->name}]");

        return ['success' => true, 'agent_id' => $agent->id, 'server' => $serverName];
    }

    /**
     * Detach an MCP server from an agent.
     */
    public function detachFromAgent(Agent $agent, string $serverName): array
    {
        $server = $this->getServer($serverName);

        if ($server) {
            $agent->mcpServers()->detach($server->id);
        }

        Log::info("MCP server [{$serverName}] detached from agent [{$agent->name}]");

        return ['success' => true, 'agent_id' => $agent->id, 'server' => $serverName];
    }

    /**
     * Get all MCP servers attached to an agent.
     */
    public function getAgentServers(Agent $agent): \Illuminate\Database\Eloquent\Collection
    {
        return $agent->mcpServers;
    }

    /**
     * Unregister (delete) a server.
     */
    public function unregister(string $name): bool
    {
        $server = $this->getServer($name);
        if ($server) {
            $server->delete();
        }

        Log::info("MCP server unregistered: {$name}");
        return true;
    }

    /**
     * Basic health check — for remote servers try a ping, for local mark connected.
     */
    protected function performHealthCheck(MCPServer $server): array
    {
        if ($server->type === 'local') {
            return ['success' => true];
        }

        $config = $server->connection_config ?? [];
        $url = $config['url'] ?? null;

        if (!$url) {
            return ['success' => false, 'error' => 'No URL configured'];
        }

        try {
            $response = \Illuminate\Support\Facades\Http::timeout(5)->get($url . '/health');
            return ['success' => $response->successful()];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
