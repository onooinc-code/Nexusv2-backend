<?php

namespace App\Services\Memory;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class MemorySummaryService
{
    /**
     * Summarize a memory or memory thread
     *
     * @param string $content
     * @param int $maxLength Maximum length of summary
     * @return string
     */
    public function summarize(string $content, int $maxLength = 200): string
    {
        try {
            // If content is already short enough, return as-is
            if (strlen($content) <= $maxLength) {
                return $content;
            }

            // Simple summarization: take first and last parts
            // In a real implementation, you might use an AI model for better summarization
            $halfLength = floor($maxLength / 2);
            $summary = substr($content, 0, $halfLength) . '...' . substr($content, -$halfLength);

            // Ensure we don't exceed maxLength
            if (strlen($summary) > $maxLength) {
                $summary = substr($summary, 0, $maxLength);
            }

            return $summary;
        } catch (\Exception $e) {
            Log::error('MemorySummaryService::summarize failed', [
                'contentLength' => strlen($content),
                'maxLength' => $maxLength,
                'error' => $e->getMessage()
            ]);
            // Fallback: return truncated content
            return substr($content, 0, $maxLength);
        }
    }

    /**
     * Summarize episodic memories for a contact
     *
     * @param int $contactId
     * @param int $limit Number of memories to summarize
     * @param int $maxLength Maximum length of summary
     * @return string
     */
    public function summarizeEpisodicMemories(int $contactId, int $limit = 10, int $maxLength = 500): string
    {
        try {
            // This would typically use the EpisodicMemoryService to get memories
            // For now, we'll return a placeholder
            Log::info('Summarizing episodic memories', [
                'contactId' => $contactId,
                'limit' => $limit,
                'maxLength' => $maxLength
            ]);

            // Placeholder implementation
            return "Summary of {$limit} most recent episodic memories for contact {$contactId}.";
        } catch (\Exception $e) {
            Log::error('MemorySummaryService::summarizeEpisodicMemories failed', [
                'contactId' => $contactId,
                'limit' => $limit,
                'error' => $e->getMessage()
            ]);
            return "Unable to generate summary.";
        }
    }

    /**
     * Summarize structured memories for a contact
     *
     * @param int $contactId
     * @param string|null $factType
     * @param int $maxLength Maximum length of summary
     * @return string
     */
    public function summarizeStructuredMemories(int $contactId, string $factType = null, int $maxLength = 300): string
    {
        try {
            Log::info('Summarizing structured memories', [
                'contactId' => $contactId,
                'factType' => $factType,
                'maxLength' => $maxLength
            ]);

            // Placeholder implementation
            $typeText = $factType ? " of type '{$factType}'" : '';
            return "Summary of structured memories{$typeText} for contact {$contactId}.";
        } catch (\Exception $e) {
            Log::error('MemorySummaryService::summarizeStructuredMemories failed', [
                'contactId' => $contactId,
                'factType' => $factType,
                'error' => $e->getMessage()
            ]);
            return "Unable to generate summary.";
        }
    }

    /**
     * Create a summary for prompt injection
     *
     * @param array $memories Array of memory objects or arrays
     * @param int $maxLength Maximum length of summary
     * @return string
     */
    public function createPromptSummary(array $memories, int $maxLength = 400): string
    {
        try {
            if (empty($memories)) {
                return "No relevant memories found.";
            }

            // Convert memories to text representation
            $memoryTexts = [];
            foreach ($memories as $memory) {
                if (is_array($memory)) {
                    // Handle array representation
                    $text = $memory['content'] ?? $memory['data'] ?? json_encode($memory);
                } elseif (is_object($memory)) {
                    // Handle object representation
                    $text = $memory->content ?? $memory->data ?? (string) $memory;
                } else {
                    $text = (string) $memory;
                }
                $memoryTexts[] = $text;
            }

            // Join all memories with separator
            $combinedText = implode("\n\n---\n\n", $memoryTexts);

            // Summarize if too long
            if (strlen($combinedText) <= $maxLength) {
                return $combinedText;
            }

            return $this->summarize($combinedText, $maxLength);
        } catch (\Exception $e) {
            Log::error('MemorySummaryService::createPromptSummary failed', [
                'memoryCount' => count($memories),
                'maxLength' => $maxLength,
                'error' => $e->getMessage()
            ]);
            return "Error generating memory summary.";
        }
    }
}