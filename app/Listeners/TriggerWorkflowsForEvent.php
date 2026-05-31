<?php

namespace App\Listeners;

use App\Events\ContactCreated;
use App\Events\MessageReceived;
use App\Models\Workflow;
use App\Services\WorkflowExecutor;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Str;

class TriggerWorkflowsForEvent implements ShouldQueue
{
    public function __construct(protected WorkflowExecutor $executor) {}

    public function handle(object $event): void
    {
        $eventName = $this->eventName($event);
        $payload = $this->payload($event);

        Workflow::query()
            ->where('trigger_type', Workflow::TRIGGER_EVENT)
            ->where('is_active', true)
            ->whereIn('status', [Workflow::STATUS_DRAFT, Workflow::STATUS_ACTIVE, Workflow::STATUS_COMPLETED, Workflow::STATUS_FAILED])
            ->get()
            ->filter(fn (Workflow $workflow) => ($workflow->trigger_config['event'] ?? null) === $eventName)
            ->each(function (Workflow $workflow) use ($payload) {
                $this->executor->execute($workflow, $payload, 'async');
            });
    }

    protected function eventName(object $event): string
    {
        return match (true) {
            $event instanceof ContactCreated => 'contact.created',
            $event instanceof MessageReceived => 'message.received',
            default => Str::of(class_basename($event))->snake('.')->toString(),
        };
    }

    protected function payload(object $event): array
    {
        if ($event instanceof ContactCreated) {
            return [
                'event' => 'contact.created',
                'contact_id' => $event->contact->id,
                'metadata' => $event->metadata,
            ];
        }

        if ($event instanceof MessageReceived) {
            return [
                'event' => 'message.received',
                'conversation_id' => $event->conversationId,
                'message_id' => $event->messageId,
                'agent_id' => $event->agentId,
                'response_data' => $event->responseData,
            ];
        }

        return ['event' => $this->eventName($event)];
    }
}
