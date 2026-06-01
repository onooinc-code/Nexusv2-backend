<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\WorkflowWebhook;
use App\Services\Workflows\WorkflowWebhookService;
use Illuminate\Http\JsonResponse;

class WorkflowWebhookController extends Controller
{
    public function __construct(protected WorkflowWebhookService $webhookService) {}

    public function handle(Request $request, string $id): JsonResponse
    {
        try {
            $webhook = WorkflowWebhook::findOrFail($id);
            $signature = $request->header('X-Hub-Signature-256') ?: $request->header('X-Signature');

            $result = $this->webhookService->handleWebhook($webhook, $request->all(), $signature);

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 400);
        }
    }
}
