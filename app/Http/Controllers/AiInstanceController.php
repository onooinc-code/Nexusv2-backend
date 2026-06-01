<?php

namespace App\Http\Controllers;

use App\Models\AiInstance;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class AiInstanceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = AiInstance::query();
        
        if ($request->has('workspace_id')) {
            $query->where('workspace_id', $request->input('workspace_id'));
        }

        return response()->json([
            'success' => true,
            'data' => $query->get(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'provider' => 'required|string|max:255',
            'model_name' => 'required|string|max:255',
            'is_active' => 'boolean',
            'config' => 'nullable|array',
            'routing_tag' => 'nullable|string|max:255',
            'workspace_id' => 'required|exists:workspaces,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $instance = AiInstance::create($validator->validated());

        return response()->json(['success' => true, 'data' => $instance], 201);
    }

    public function show(AiInstance $aiInstance): JsonResponse
    {
        return response()->json(['success' => true, 'data' => $aiInstance]);
    }

    public function update(Request $request, AiInstance $aiInstance): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'provider' => 'sometimes|string|max:255',
            'model_name' => 'sometimes|string|max:255',
            'is_active' => 'boolean',
            'config' => 'nullable|array',
            'routing_tag' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $aiInstance->update($validator->validated());

        return response()->json(['success' => true, 'data' => $aiInstance]);
    }

    public function destroy(AiInstance $aiInstance): JsonResponse
    {
        $aiInstance->delete();
        return response()->json(['success' => true, 'message' => 'Instance deleted successfully.']);
    }
}
