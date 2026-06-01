<?php

namespace App\Listeners;

use App\Events\ContactImportCompleted;
use App\Events\ContactAnalysisCompleted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class TriggerContactWorkflows implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle($event): void
    {
        if ($event instanceof ContactImportCompleted) {
            Log::info("Triggering workflows for ContactImportCompleted", [
                'contact_id' => $event->contact->id,
                'messages_imported' => $event->messagesImported
            ]);
            
            // Future integration with WorkflowsHub to trigger auto-analysis or auto-reply
        }
        
        if ($event instanceof ContactAnalysisCompleted) {
            Log::info("Triggering workflows for ContactAnalysisCompleted", [
                'contact_id' => $event->contact->id,
                'run_id' => $event->run->id
            ]);
            
            // Future integration with TasksHub to create tasks if user action is needed based on AI findings
        }
    }
}
