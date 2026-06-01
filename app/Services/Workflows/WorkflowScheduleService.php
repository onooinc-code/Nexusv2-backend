<?php

namespace App\Services\Workflows;

use App\Models\WorkflowSchedule;
use App\Services\WorkflowExecutor;
use App\Services\LogService;
use Cron\CronExpression;

class WorkflowScheduleService
{
    public function __construct(
        protected WorkflowExecutor $executor,
        protected LogService $logService
    ) {}

    public function processScheduledWorkflows(): void
    {
        $schedules = WorkflowSchedule::where('is_active', true)->with('workflow')->get();

        foreach ($schedules as $schedule) {
            try {
                if (!$schedule->workflow || !$schedule->workflow->is_active) {
                    continue;
                }

                $cron = new CronExpression($schedule->cron_expression);
                
                // If it's due now, or if next_run_at is <= now
                if ($cron->isDue(now()) || ($schedule->next_run_at && $schedule->next_run_at <= now())) {
                    $this->logService->info('Executing scheduled workflow', [
                        'channel' => 'workflow',
                        'type' => 'scheduled_trigger',
                        'related_id' => $schedule->workflow_id,
                        'related_type' => 'App\Models\Workflow',
                        'context' => ['schedule_id' => $schedule->id],
                    ]);

                    $this->executor->execute($schedule->workflow, $schedule->input_payload ?? []);

                    $schedule->update([
                        'last_run_at' => now(),
                        'next_run_at' => $cron->getNextRunDate(),
                    ]);
                }
            } catch (\Exception $e) {
                $this->logService->error('Failed to execute scheduled workflow', [
                    'channel' => 'workflow',
                    'type' => 'scheduled_trigger_failed',
                    'related_id' => $schedule->workflow_id,
                    'related_type' => 'App\Models\Workflow',
                    'context' => [
                        'schedule_id' => $schedule->id,
                        'error' => $e->getMessage()
                    ]
                ]);
            }
        }
    }
}
