<?php

namespace App\Http\Controllers;

use App\Models\SchedulerJob;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SchedulerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $jobs = SchedulerJob::orderBy('id', 'desc')->get();
        return response()->json([
            'success' => true,
            'data' => $jobs
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|in:command,job,webhook,script',
            'cron_expression' => 'required|string',
            'payload' => 'nullable|array',
            'status' => 'nullable|string|in:active,paused',
        ]);

        $job = SchedulerJob::create($validated);

        return response()->json([
            'success' => true,
            'data' => $job
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(SchedulerJob $schedulerJob): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $schedulerJob
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id): JsonResponse
    {
        $schedulerJob = SchedulerJob::findOrFail($id);
        
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'type' => 'sometimes|string|in:command,job,webhook,script',
            'cron_expression' => 'sometimes|string',
            'payload' => 'nullable|array',
            'status' => 'sometimes|string|in:active,paused',
        ]);

        $schedulerJob->update($validated);

        return response()->json([
            'success' => true,
            'data' => $schedulerJob
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id): JsonResponse
    {
        $schedulerJob = SchedulerJob::findOrFail($id);
        $schedulerJob->delete();

        return response()->json([
            'success' => true,
            'message' => 'Job deleted successfully'
        ]);
    }
}
