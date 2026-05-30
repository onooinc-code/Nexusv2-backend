<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\SchedulerJob;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Cron\CronExpression;

class SchedulerWorker extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scheduler:worker {--daemon}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run the scheduled jobs via atomic claiming';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting scheduler worker...');
        
        $isDaemon = $this->option('daemon');

        while (true) {
            $this->processDueJobs();
            
            if (!$isDaemon) {
                break;
            }
            
            sleep(60);
        }
    }
    
    protected function processDueJobs()
    {
        $now = Carbon::now();
        
        DB::transaction(function () use ($now) {
            // Atomic claim using SELECT FOR UPDATE
            $jobs = SchedulerJob::where('status', 'active')
                ->where('is_running', false)
                ->where(function ($q) use ($now) {
                    $q->whereNull('next_run_at')
                      ->orWhere('next_run_at', '<=', $now);
                })
                ->lockForUpdate()
                ->get();
                
            foreach ($jobs as $job) {
                try {
                    $cron = new CronExpression($job->cron_expression);
                    
                    // Mark as running to claim it atomically within the transaction
                    $job->is_running = true;
                    $job->save();
                    
                    $this->info("Executing job: {$job->name}");
                    
                    // Simulate execution payload
                    // In a real scenario, we might trigger a Job class, webhook, or system command based on $job->type
                    
                    // Update next run time and mark as not running
                    $job->last_run_at = $now;
                    $job->next_run_at = Carbon::instance($cron->getNextRunDate($now));
                    $job->is_running = false;
                    $job->save();
                } catch (\Exception $e) {
                    $this->error("Error processing job {$job->id}: {$e->getMessage()}");
                    $job->is_running = false;
                    $job->status = 'failing';
                    $job->save();
                }
            }
        });
    }
}
