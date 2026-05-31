<?php

namespace App\Services;

use App\Models\Contact;
use App\Models\ContactAnalysisRun;
use App\Models\ContactIdentifier;
use App\Models\ContactImportBatch;
use App\Models\ContactMemory;
use App\Models\ContactMessage;
use App\Models\ContactRelationship;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ContactStatsService
{
    /**
     * Return hub-level operational stats for the topbar strip.
     * Cached for 60 seconds to avoid hot-path DB pressure.
     */
    public function getHubStats(): array
    {
        return Cache::remember('contact_hub:stats', 60, function () {
            $total = Contact::count();
            $active = Contact::where('is_active', true)->count();

            // New imported messages in last 24 h
            $newMessages = ContactMessage::where('created_at', '>=', now()->subDay())->count();

            // Pending AI analysis runs
            $pendingAnalysis = ContactAnalysisRun::where('status', 'pending')->count();

            // Stale memories: last_validated_at older than 30 days or never validated
            $staleMemories = ContactMemory::where(function ($q) {
                $q->whereNull('last_validated_at')
                  ->orWhere('last_validated_at', '<', now()->subDays(30));
            })->count();

            // Identity conflicts: contacts with 2+ identifiers of the same type/value (naïve count)
            $identityConflicts = DB::table('contact_identifiers')
                ->select('type', 'value', DB::raw('COUNT(*) as cnt'))
                ->whereNull('deleted_at')
                ->groupBy('type', 'value')
                ->havingRaw('cnt > 1')
                ->count();

            // Contacts with autopilot reply mode
            $autopilotCount = Contact::where('reply_mode_override', 'autopilot')->count();

            // Failed imports
            $failedImports = ContactImportBatch::where('status', 'failed')->count();

            // Failed / errored analysis runs
            $failedAnalysis = ContactAnalysisRun::where('status', 'failed')->count();

            return [
                'total_contacts'       => $total,
                'active_contacts'      => $active,
                'new_imported_messages'=> $newMessages,
                'pending_analysis_runs'=> $pendingAnalysis,
                'stale_memory_count'   => $staleMemories,
                'identity_conflict_count' => $identityConflicts,
                'autopilot_enabled_count' => $autopilotCount,
                'failed_imports'       => $failedImports,
                'failed_analysis_runs' => $failedAnalysis,
                'generated_at'         => now()->toIso8601String(),
            ];
        });
    }

    /**
     * Flush the cached stats so the next request gets fresh data.
     */
    public function invalidate(): void
    {
        Cache::forget('contact_hub:stats');
    }
}
