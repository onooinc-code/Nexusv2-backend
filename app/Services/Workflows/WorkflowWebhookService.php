<?php

namespace App\Services\Workflows;

use App\Models\WorkflowWebhook;
use App\Services\WorkflowExecutor;
use App\Services\LogService;

class WorkflowWebhookService
{
    public function __construct(
        protected WorkflowExecutor $executor,
        protected LogService $logService
    ) {}

    public function handleWebhook(WorkflowWebhook $webhook, array $payload, ?string $signature = null): array
    {
        if (!$webhook->is_active) {
            throw new \Exception("Webhook is inactive.");
        }

        if (!$webhook->workflow || !$webhook->workflow->is_active) {
            throw new \Exception("Associated workflow is inactive or missing.");
        }

        if ($webhook->secret_key) {
            if (!$signature) {
                throw new \Exception("Signature is required for this webhook.");
            }
            
            // Expected signature is hash_hmac sha256 of the payload JSON
            $expectedSignature = hash_hmac('sha256', json_encode($payload), $webhook->secret_key);
            
            // Compare securely
            if (!hash_equals($expectedSignature, $signature)) {
                $this->logService->warning('Invalid webhook signature', [
                    'channel' => 'workflow',
                    'webhook_id' => $webhook->id,
                    'provided_signature' => $signature,
                ]);
                throw new \Exception("Invalid webhook signature.");
            }
        }

        $this->logService->info('Executing webhook-triggered workflow', [
            'channel' => 'workflow',
            'type' => 'webhook_trigger',
            'related_id' => $webhook->workflow_id,
            'related_type' => 'App\Models\Workflow',
            'context' => [
                'webhook_id' => $webhook->id,
            ],
        ]);

        $this->executor->execute($webhook->workflow, $payload);

        return ['success' => true, 'message' => 'Workflow triggered successfully.'];
    }
}
