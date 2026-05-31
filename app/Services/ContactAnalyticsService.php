<?php

namespace App\Services;

use App\Models\Contact;
use App\Models\ContactMessage;
use App\Models\ContactAnalysisRun;
use App\Models\ContactMemory;
use App\Models\ContactImportBatch;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class ContactAnalyticsService
{
    /**
     * Get global metrics for the ContactHub.
     */
    public function getGlobalStats(): array
    {
        return Cache::remember('contacthub:global_stats', 60, function () {
            $totalContacts = Contact::count();
            $activeContacts = Contact::where('is_active', true)->count();
            
            // Stats from vNext schema
            $newImportedMessages = ContactMessage::count();
            $pendingAnalysisRuns = ContactAnalysisRun::where('status', 'pending')->count();
            
            // Stale memories: last validated more than 30 days ago
            $staleMemories = ContactMemory::where('last_validated_at', '<', now()->subDays(30))
                ->orWhereNull('last_validated_at')
                ->count();
                
            $autonomousReplyCount = Contact::where('reply_mode_override', 'autopilot')->count();
            
            $failedImports = ContactImportBatch::where('status', 'failed')->count();
            $failedAnalysis = ContactAnalysisRun::where('status', 'failed')->count();

            return [
                'total_contacts' => $totalContacts,
                'active_contacts' => $activeContacts,
                'new_imported_messages' => $newImportedMessages,
                'pending_analysis_runs' => $pendingAnalysisRuns,
                'stale_memory_count' => $staleMemories,
                'identity_conflict_count' => 0, // Placeholder until conflict detection is implemented
                'autonomous_reply_enabled_count' => $autonomousReplyCount,
                'failed_imports_count' => $failedImports,
                'failed_analysis_count' => $failedAnalysis,
            ];
        });
    }

    /**
     * Get analytics for a specific contact.
     */
    public function getContactStats(Contact $contact, int $days = 7): array
    {
        $cacheKey = "contact:{$contact->id}:analytics:{$days}";

        return Cache::remember($cacheKey, 60, function () use ($contact, $days) {
            $now = Carbon::now();
            $start = $now->copy()->subDays($days - 1)->startOfDay();

            $memoriesSeries = [];
            $messagesSeries = [];
            $conversationsSeries = [];

            for ($i = 0; $i < $days; $i++) {
                $day = $start->copy()->addDays($i);
                $from = $day->copy()->startOfDay();
                $to = $day->copy()->endOfDay();

                $memoriesSeries[] = [
                    'date' => $day->toDateString(),
                    'count' => $contact->memories()->whereBetween('created_at', [$from, $to])->count(),
                ];

                $messagesSeries[] = [
                    'date' => $day->toDateString(),
                    'count' => $contact->messages()->whereBetween('created_at', [$from, $to])->count(),
                ];

                $conversationsSeries[] = [
                    'date' => $day->toDateString(),
                    'count' => $contact->conversations()->whereBetween('created_at', [$from, $to])->count(),
                ];
            }

            // Message counts by channel
            $channelCounts = $contact->messages()
                ->selectRaw('channel, count(*) as count')
                ->groupBy('channel')
                ->pluck('count', 'channel')
                ->toArray();

            return [
                'type' => $contact->type,
                'last_seen_at' => $contact->last_seen_at?->toDateTimeString(),
                'memory_count' => $contact->memories()->count(),
                'message_count' => $contact->messages()->count(),
                'conversation_count' => $contact->conversations()->count(),
                'baseline' => $contact->metadata['emotional_baseline'] ?? 'neutral',
                'channel_distribution' => $channelCounts,
                'time_series' => [
                    'memories' => $memoriesSeries,
                    'messages' => $messagesSeries,
                    'conversations' => $conversationsSeries,
                ],
            ];
        });
    }
}
