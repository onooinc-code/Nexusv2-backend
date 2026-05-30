<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Log;
use App\Services\LogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * LogController
 *
 * Handles log search, filtering, and retrieval.
 * Supports filtering by level, channel, and date range.
 */
class LogController extends Controller
{
    protected LogService $logService;

    public function __construct(LogService $logService)
    {
        $this->logService = $logService;
    }

    /**
     * Display a listing of logs with filters.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'level' => ['nullable', 'string'],
            'channel' => ['nullable', 'string'],
            'user_id' => ['nullable', 'integer'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'search' => ['nullable', 'string', 'max:255'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $query = Log::query();

        // Filter by level
        if ($request->has('level')) {
            $query->byLevel($request->input('level'));
        }

        // Filter by channel
        if ($request->has('channel')) {
            $query->byChannel($request->input('channel'));
        }

        // Filter by user
        if ($request->has('user_id')) {
            $query->byUser((int) $request->input('user_id'));
        }

        // Filter by date range
        if ($request->has('from')) {
            $to = $request->input('to');
            $query->dateRange($request->input('from'), $to);
        }

        // Search by message
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where('message', 'like', "%{$search}%");
        }

        // Pagination
        $perPage = (int) ($request->input('per_page', 50));
        $logs = $query->orderByDesc('created_at')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $logs->items(),
            'pagination' => [
                'total' => $logs->total(),
                'per_page' => $logs->perPage(),
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
            ],
        ]);
    }

    /**
     * Display the specified log.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show($id): JsonResponse
    {
        $log = $this->logService->getById((int) $id);

        if (!$log) {
            return response()->json([
                'success' => false,
                'message' => 'Log not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $log,
        ]);
    }

    /**
     * Get error-level logs.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function errors(Request $request): JsonResponse
    {
        $perPage = (int) ($request->input('per_page', 50));
        $logs = Log::errors()->orderByDesc('created_at')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $logs->items(),
            'pagination' => [
                'total' => $logs->total(),
                'per_page' => $logs->perPage(),
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
            ],
        ]);
    }

    /**
     * Get available log levels.
     *
     * @return JsonResponse
     */
    public function levels(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->logService->getLevels(),
        ]);
    }

    /**
     * Get available log channels.
     *
     * @return JsonResponse
     */
    public function channels(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->logService->getChannels(),
        ]);
    }

    /**
     * Get log statistics.
     *
     * @return JsonResponse
     */
    public function stats(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->logService->getStats(),
        ]);
    }

    /**
     * Remove the specified log.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy($id): JsonResponse
    {
        $deleted = $this->logService->delete((int) $id);

        if (!$deleted) {
            return response()->json([
                'success' => false,
                'message' => 'Log not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Log deleted successfully.',
        ]);
    }

    /**
     * Clear all logs or logs older than specified days.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function clear(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'older_than_days' => ['nullable', 'integer', 'min:1'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        if ($request->has('older_than_days')) {
            $deleted = $this->logService->clearOldLogs((int) $request->input('older_than_days'));
            return response()->json([
                'success' => true,
                'message' => "Deleted {$deleted} logs older than {$request->input('older_than_days')} days.",
            ]);
        }

        Log::truncate();

        return response()->json([
            'success' => true,
            'message' => 'All logs cleared successfully.',
        ]);
    }
}