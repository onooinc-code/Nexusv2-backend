<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AiRotateKeysCommand extends Command
{
    protected $signature = 'ai:rotate-keys {--dry-run : Show what would be rotated without making changes}';
    protected $description = 'Mark expiring or expired AI API keys and notify administrators';

    public function handle(): void
    {
        $isDryRun = $this->option('dry-run');
        $now = Carbon::now();
        $expiringThreshold = $now->copy()->addDays(7);

        // Find expired keys
        $expired = DB::table('ai_api_keys')
            ->where('status', 'active')
            ->where('expires_at', '<', $now)
            ->get();

        // Find keys expiring within 7 days
        $expiring = DB::table('ai_api_keys')
            ->where('status', 'active')
            ->whereBetween('expires_at', [$now, $expiringThreshold])
            ->get();

        $this->info("Found {$expired->count()} expired keys.");
        $this->info("Found {$expiring->count()} keys expiring within 7 days.");

        if (!$isDryRun) {
            // Mark expired
            if ($expired->count() > 0) {
                DB::table('ai_api_keys')
                    ->whereIn('id', $expired->pluck('id'))
                    ->update(['status' => 'expired', 'is_active' => false, 'updated_at' => $now]);
                $this->warn("{$expired->count()} keys marked as expired and deactivated.");
            }

            // Mark expiring
            if ($expiring->count() > 0) {
                DB::table('ai_api_keys')
                    ->whereIn('id', $expiring->pluck('id'))
                    ->update(['status' => 'expiring', 'updated_at' => $now]);
                $this->info("{$expiring->count()} keys flagged as expiring soon.");
            }
        } else {
            $this->line('[DRY RUN] No changes were made.');
        }

        $this->info('Key rotation check complete.');
    }
}
