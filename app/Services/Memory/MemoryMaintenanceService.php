<?php

namespace App\Services\Memory;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class MemoryMaintenanceService
{
    /**
     * Merge duplicate episodic memories for a contact.
     * This is a simplified version that merges memories with similar content.
     *
     * @param int $contactId
     * @param float $similarityThreshold Threshold for considering memories as duplicates (0-1)
     * @return int Number of memories merged
     */
    public function mergeEpisodicMemories(int $contactId, float $similarityThreshold = 0.8): int
    {
        try {
            // Get all episodic memories for the contact
            $memories = DB::table('messages')
                ->where('contact_id', $contactId)
                ->whereJsonContains('metadata->memory_type', 'episodic')
                ->get();

            $mergedCount = 0;

            // Compare each pair of memories (simplified O(n^2) approach)
            for ($i = 0; $i < $memories->count(); $i++) {
                for ($j = $i + 1; $j < $memories->count(); $j++) {
                    $mem1 = $memories[$i];
                    $mem2 = $memories[$j];

                    // Calculate similarity (simple string similarity for content)
                    $similarity = $this->calculateSimilarity(
                        $mem1->content,
                        $mem2->content
                    );

                    if ($similarity >= $similarityThreshold) {
                        // Merge: we'll keep the newer one and delete the older one
                        // In a real system, we might combine the data
                        $older = $mem1->created_at < $mem2->created_at ? $mem1 : $mem2;
                        $newer = $mem1->created_at < $mem2->created_at ? $mem2 : $mem1;

                        // For demonstration, we'll just delete the older one
                        // and log that we merged them
                        Log::info('Merging episodic memories', [
                            'kept' => $newer->id,
                            'merged' => $older->id,
                            'similarity' => $similarity
                        ]);

                        // Delete the older memory
                        DB::table('messages')->where('id', $older->id)->delete();
                        $mergedCount++;
                    }
                }
            }

            return $mergedCount;
        } catch (\Exception $e) {
            Log::error('MemoryMaintenanceService::mergeEpisodicMemories failed', [
                'contactId' => $contactId,
                'error' => $e->getMessage()
            ]);
            return 0;
        }
    }

    /**
     * Prune stale memories older than a certain threshold.
     *
     * @param int $daysOld
     * @return int Number of memories pruned
     */
    public function pruneStaleMemories(int $daysOld = 30): int
    {
        try {
            $cutoffDate = Carbon::now()->subDays($daysOld);

            // Prune episodic memories (messages table)
            $episodicDeleted = DB::table('messages')
                ->whereJsonContains('metadata->memory_type', 'episodic')
                ->where('created_at', '<', $cutoffDate)
                ->delete();

            // Prune structured memories
            $structuredDeleted = DB::table('structured_memories')
                ->where('created_at', '<', $cutoffDate)
                ->delete();

            // Note: For working memory (Redis), we rely on TTL.
            // For semantic and graph memory, we would add similar logic.

            $totalDeleted = $episodicDeleted + $structuredDeleted;

            Log::info('Pruned stale memories', [
                'daysOld' => $daysOld,
                'episodicDeleted' => $episodicDeleted,
                'structuredDeleted' => $structuredDeleted,
                'totalDeleted' => $totalDeleted
            ]);

            return $totalDeleted;
        } catch (\Exception $e) {
            Log::error('MemoryMaintenanceService::pruneStaleMemories failed', [
                'daysOld' => $daysOld,
                'error' => $e->getMessage()
            ]);
            return 0;
        }
    }

    /**
     * Calculate similarity between two strings (simple implementation).
     * In a real system, you might use embeddings or more sophisticated algorithms.
     *
     * @param string $str1
     * @param string $str2
     * @return float Similarity score between 0 and 1
     */
    protected function calculateSimilarity(string $str1, string $str2): float
    {
        // Simple similarity based on longest common subsequence ratio
        // This is a placeholder for demonstration
        if ($str1 === '' && $str2 === '') {
            return 1.0;
        }
        if ($str1 === '' || $str2 === '') {
            return 0.0;
        }

        // Convert to lowercase for case-insensitive comparison
        $str1 = strtolower($str1);
        $str2 = strtolower($str2);

        // If strings are equal, return 1.0
        if ($str1 === $str2) {
            return 1.0;
        }

        // Simple similarity: ratio of common characters to total length
        // This is a very basic metric and not suitable for production
        $longer = strlen($str1) > strlen($str2) ? $str1 : $str2;
        $shorter = strlen($str1) > strlen($str2) ? $str2 : $str1;

        $matches = 0;
        $lenShorter = strlen($shorter);
        for ($i = 0; $i < $lenShorter; $i++) {
            if (strpos($longer, $shorter[$i]) !== false) {
                $matches++;
            }
        }

        return $matches / strlen($longer);
    }

    /**
     * Run memory maintenance tasks (merge and prune).
     *
     * @param int $contactId Optional contact ID to limit maintenance to a specific contact
     * @param int $daysOld Days after which memories are considered stale
     * @param float $similarityThreshold Threshold for merging duplicates
     * @return array Results of the maintenance operations
     */
    public function runMaintenance(int $contactId = null, int $daysOld = 30, float $similarityThreshold = 0.8): array
    {
        $results = [
            'merged' => 0,
            'pruned' => 0,
            'errors' => []
        ];

        try {
            if ($contactId !== null) {
                $results['merged'] = $this->mergeEpisodicMemories($contactId, $similarityThreshold);
            } else {
                // For global merge, we would need to get all contacts and run for each
                // For simplicity, we'll skip global merge in this example
                Log::warning('Global merge not implemented in this example');
            }

            $results['pruned'] = $this->pruneStaleMemories($daysOld);
        } catch (\Exception $e) {
            $results['errors'][] = $e->getMessage();
            Log::error('MemoryMaintenanceService::runMaintenance failed', [
                'contactId' => $contactId,
                'error' => $e->getMessage()
            ]);
        }

        return $results;
    }
}