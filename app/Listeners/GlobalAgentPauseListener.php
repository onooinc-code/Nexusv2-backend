<?php

namespace App\Listeners;

use App\Events\GlobalAgentPauseToggled;
use App\Models\Agent;
use App\Services\LogService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class GlobalAgentPauseListener
{
    /**
     * Create the event listener.
     */
    public function __construct(protected LogService $logService)
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(GlobalAgentPauseToggled $event): void
    {
        $enabled = $event->enabled;

        if ($enabled) {
            // Quarantine all non-system agents
            Agent::where('is_system', false)->update(['status' => Agent::STATUS_QUARANTINED]);
            
            $this->logService->warning("Global Agent Pause ACTIVATED. All non-system agents have been quarantined.", [
                'channel' => 'system',
                'type' => 'security',
                'context' => ['reason' => $event->reason],
            ]);
        } else {
            // Unquarantine agents
            Agent::where('status', Agent::STATUS_QUARANTINED)->update(['status' => Agent::STATUS_ACTIVE]);
            
            $this->logService->info("Global Agent Pause DEACTIVATED. Agents restored to active status.", [
                'channel' => 'system',
                'type' => 'security',
                'context' => ['reason' => $event->reason],
            ]);
        }
    }
}
