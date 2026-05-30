<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DlqController extends Controller
{
    public function index(Request $request)
    {
        $perPage = (int) $request->query('per_page', 20);
        $failedJobs = DB::table('failed_jobs')
            ->orderBy('failed_at', 'desc')
            ->paginate($perPage);

        $failedJobs->getCollection()->transform(function ($job) {
            $payload = json_decode($job->payload, true) ?: [];
            return [
                'id' => $job->id,
                'uuid' => $job->uuid,
                'connection' => $job->connection,
                'queue' => $job->queue,
                'failed_at' => $job->failed_at,
                'exception' => $job->exception,
                'job_class' => $payload['data']['command'] ?? null,
                'payload' => $payload['data'] ?? [],
            ];
        });

        return response()->json($failedJobs);
    }

    public function retry(string $id)
    {
        $failed = DB::table('failed_jobs')->where('id', $id)->first();
        if (! $failed) {
            return response()->json(['message' => 'Failed job not found'], 404);
        }

        Artisan::call('queue:retry', ['id' => $id]);

        return response()->json([
            'message' => 'Job requeued for retry',
            'id' => $id,
        ]);
    }

    public function destroy(string $id)
    {
        $deleted = DB::table('failed_jobs')->where('id', $id)->delete();
        if (! $deleted) {
            return response()->json(['message' => 'Failed job not found'], 404);
        }

        return response()->json([
            'message' => 'Failed job removed from DLQ',
            'id' => $id,
        ]);
    }

    public function batchRetry(Request $request)
    {
        $data = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'required|integer|distinct',
        ]);

        $ids = array_map('strval', $data['ids']);
        Artisan::call('queue:retry', ['id' => implode(',', $ids)]);

        return response()->json([
            'message' => 'Batch retry queued',
            'retry_ids' => $ids,
        ]);
    }
}
