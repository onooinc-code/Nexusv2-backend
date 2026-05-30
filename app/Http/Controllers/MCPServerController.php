<?php

namespace App\Http\Controllers;

use App\Models\MCPServer;
use App\Services\MCPIntegrationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MCPServerController extends Controller
{
    public function __construct(protected MCPIntegrationService $mcpService) {}

    public function index(Request $request)
    {
        $query = MCPServer::query();

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        $servers = $query->paginate($request->per_page ?? 20);

        return response()->json($servers);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:mcp_servers,name',
            'type' => 'required|string|in:local,remote',
            'connection_config' => 'required|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $server = $this->mcpService->registerServer($data['name'], array_merge($data['connection_config'], ['type' => $data['type']]));

        return response()->json(['data' => $server, 'message' => 'MCP Server registered successfully'], 201);
    }

    public function show(MCPServer $mcpServer)
    {
        return response()->json(['data' => $mcpServer]);
    }

    public function update(Request $request, MCPServer $mcpServer)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255|unique:mcp_servers,name,' . $mcpServer->id,
            'type' => 'sometimes|string|in:local,remote',
            'connection_config' => 'sometimes|array',
            'status' => 'sometimes|string|in:online,offline,error,connected,disconnected',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $mcpServer->update($validator->validated());

        return response()->json(['data' => $mcpServer, 'message' => 'MCP Server updated successfully']);
    }

    public function destroy(MCPServer $mcpServer)
    {
        $this->mcpService->unregister($mcpServer->name);

        return response()->json(['message' => 'MCP Server deleted successfully']);
    }

    public function connect(string $name)
    {
        $result = $this->mcpService->connect($name);
        return response()->json($result, $result['success'] ? 200 : 400);
    }

    public function disconnect(string $name)
    {
        $result = $this->mcpService->disconnect($name);
        return response()->json(['success' => $result]);
    }
}
