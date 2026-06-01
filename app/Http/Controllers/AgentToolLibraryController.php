<?php

namespace App\Http\Controllers;

use App\Models\AgentToolLibrary;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AgentToolLibraryController extends Controller
{
    /**
     * Display a listing of the tools.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $query = AgentToolLibrary::query();

        if ($request->has('category')) {
            $query->category($request->input('category'));
        }

        if ($request->has('is_system')) {
            $query->where('is_system', $request->boolean('is_system'));
        }

        if ($request->has('grouped') && $request->boolean('grouped')) {
            return response()->json([
                'success' => true,
                'data' => $query->get()->groupBy('category')
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $query->get()
        ]);
    }

    /**
     * Display the specified tool.
     *
     * @param string $id
     * @return JsonResponse
     */
    public function show(string $id): JsonResponse
    {
        $tool = AgentToolLibrary::findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $tool
        ]);
    }
}
