<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ProactiveSchedulerCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'proactive:run-scheduler';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run the proactive scheduler to trigger autonomous ECA actions';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Running Proactive Scheduler...');

        $triggers = DB::table('proactive_triggers')
            ->where('status', 'pending')
            ->where('next_run_at', '<=', Carbon::now())
            ->get();

        foreach ($triggers as $trigger) {
            $this->info('Executing trigger: ' . $trigger->id);
            Log::info('Proactive trigger execution started', ['trigger_id' => $trigger->id]);

            try {
                // Determine action from ECA rules
                $ecaRule = DB::table('eca_rules')->where('id', $trigger->eca_rule_id)->first();
                if ($ecaRule && $ecaRule->is_active) {
                    $actions = json_decode($ecaRule->actions, true);
                    
                    if (isset($actions['notify'])) {
                        // Integrate with NotificationHub
                        DB::table('notification_logs')->insert([
                            'channel' => 'system',
                            'recipient' => 'Hedra',
                            'body' => $actions['notify']['message'] ?? 'Autonomous action triggered.',
                            'status' => 'pending',
                            'created_at' => Carbon::now(),
                            'updated_at' => Carbon::now()
                        ]);
                    }

                    // Log autonomous action
                    DB::table('autonomous_logs')->insert([
                        'action_taken' => 'Executed scheduled trigger',
                        'reasoning' => 'Time-based condition met for ECA rule: ' . $ecaRule->name,
                        'status' => 'completed',
                        'created_at' => Carbon::now(),
                        'updated_at' => Carbon::now()
                    ]);
                }

                DB::table('proactive_triggers')
                    ->where('id', $trigger->id)
                    ->update([
                        'status' => 'completed',
                        'updated_at' => Carbon::now()
                    ]);

            } catch (\Exception $e) {
                Log::error('Failed to execute trigger', ['id' => $trigger->id, 'error' => $e->getMessage()]);
                DB::table('proactive_triggers')
                    ->where('id', $trigger->id)
                    ->update([
                        'status' => 'failed',
                        'updated_at' => Carbon::now()
                    ]);
            }
        }

        $this->info('Scheduler run complete.');
    }
}
