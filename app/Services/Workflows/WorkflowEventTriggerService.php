<?php

namespace App\Services\Workflows;

use App\Models\WorkflowEventTrigger;
use App\Services\WorkflowExecutor;
use App\Services\LogService;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Arr;

class WorkflowEventTriggerService
{
    public function __construct(
        protected WorkflowExecutor $executor,
        protected LogService $logService
    ) {}

    public function handleEvent(string $eventName, array $payload): void
    {
        if (!\Illuminate\Support\Facades\Schema::hasTable('workflow_event_triggers')) {
            return;
        }

        $triggers = WorkflowEventTrigger::where('event_name', $eventName)
            ->where('is_active', true)
            ->with('workflow')
            ->get();

        foreach ($triggers as $trigger) {
            try {
                if (!$trigger->workflow || !$trigger->workflow->is_active) {
                    continue;
                }

                if ($this->matchesConditions($trigger->condition_payload, $payload)) {
                    $this->logService->info('Executing event-triggered workflow', [
                        'channel' => 'workflow',
                        'type' => 'event_trigger',
                        'related_id' => $trigger->workflow_id,
                        'related_type' => 'App\Models\Workflow',
                        'context' => [
                            'trigger_id' => $trigger->id,
                            'event' => $eventName
                        ],
                    ]);

                    $this->executor->execute($trigger->workflow, $payload);
                }
            } catch (\Exception $e) {
                $this->logService->error('Failed to execute event-triggered workflow', [
                    'channel' => 'workflow',
                    'type' => 'event_trigger_failed',
                    'related_id' => $trigger->workflow_id,
                    'related_type' => 'App\Models\Workflow',
                    'context' => [
                        'trigger_id' => $trigger->id,
                        'error' => $e->getMessage()
                    ]
                ]);
            }
        }
    }

    protected function matchesConditions(?array $conditions, array $payload): bool
    {
        if (empty($conditions)) {
            return true;
        }

        foreach ($conditions as $key => $value) {
            if (Arr::get($payload, $key) !== $value) {
                return false;
            }
        }

        return true;
    }

    public function registerWildcardListener(): void
    {
        Event::listen('*', function (string $eventName, array $data) {
            if (str_starts_with($eventName, 'Illuminate\\') || str_starts_with($eventName, 'eloquent.')) {
                return;
            }

            $payload = [];
            if (count($data) > 0) {
                $first = $data[0];
                if (is_object($first) && method_exists($first, 'toArray')) {
                    $payload = $first->toArray();
                } elseif (is_array($first)) {
                    $payload = $first;
                } else {
                    $payload = json_decode(json_encode($first), true) ?: [];
                }
            }

            $this->handleEvent($eventName, $payload);
        });
    }
}
