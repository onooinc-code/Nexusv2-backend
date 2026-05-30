<?php

namespace App\Http\Controllers;

use App\Models\AgentPersona;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AgentPersonaController extends Controller
{
    public function index(Request $request)
    {
        $query = AgentPersona::query();

        if ($request->has('search')) {
            $query->where('name', 'like', "%{$request->search}%")
                  ->orWhere('description', 'like', "%{$request->search}%");
        }

        $personas = $query->paginate($request->per_page ?? 20);

        return response()->json($personas);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'system_prompt' => 'required|string',
            'tone_preferences' => 'nullable|array',
            'base_traits' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $persona = AgentPersona::create($validator->validated());

        return response()->json(['data' => $persona, 'message' => 'Persona created successfully'], 201);
    }

    public function show(AgentPersona $agentPersona)
    {
        return response()->json(['data' => $agentPersona]);
    }

    public function update(Request $request, AgentPersona $agentPersona)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'system_prompt' => 'sometimes|string',
            'tone_preferences' => 'nullable|array',
            'base_traits' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $agentPersona->update($validator->validated());

        return response()->json(['data' => $agentPersona, 'message' => 'Persona updated successfully']);
    }

    public function destroy(AgentPersona $agentPersona)
    {
        // Check if attached to agents
        if ($agentPersona->agents()->exists()) {
            return response()->json(['message' => 'Cannot delete persona while agents are using it.'], 409);
        }

        $agentPersona->delete();

        return response()->json(['message' => 'Persona deleted successfully']);
    }
}
