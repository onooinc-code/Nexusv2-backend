<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\DeadLetterQueueService;
use Illuminate\Http\Request;

class DlqController extends Controller
{
    public function __construct(protected DeadLetterQueueService $dlqService)
    {
    }

    /**
     * Display a listing of failed jobs in the DLQ
     */
    public function index(Request $request)
    {
        $perPage = $request->query('per_page', 20);
        $dlqTasks = $this->dlqService->list((int) $perPage);

        return response()->json($dlqTasks);
    }

    /**
     * Retry a specific failed job from DLQ
     */
    public function retry($id)
    {
        $result = $this->dlqService->retry((int) $id);

        if (!$result) {
            return response()->json([
                'error' => 'Failed to retry dead letter task'
            ], 422);
        }

        return response()->json([
            'message' => 'Dead letter task execution successfully re-dispatched'
        ]);
    }

    /**
     * Discard a specific failed job from DLQ
     */
    public function destroy($id)
    {
        $result = $this->dlqService->delete((int) $id);

        if (!$result) {
            return response()->json([
                'error' => 'Failed to discard dead letter task'
            ], 422);
        }

        return response()->json([
            'message' => 'Dead letter task successfully discarded'
        ]);
    }

    /**
     * Retry a batch of failed jobs
     */
    public function batchRetry(Request $request)
    {
        $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['required', 'integer']
        ]);

        $result = $this->dlqService->batchRetry($request->input('ids'));

        return response()->json([
            'message' => "Batch processing complete. Retried: {$result['success']}, Failed: {$result['failed']}",
            'data' => $result
        ]);
    }
}
