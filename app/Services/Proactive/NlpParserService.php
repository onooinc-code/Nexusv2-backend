<?php

namespace App\Services\Proactive;

use Carbon\Carbon;
use Illuminate\Support\Str;

class NlpParserService
{
    /**
     * Parses a natural language rule into structured conditions and actions.
     * Example input: "If Mohamed contacts me regarding X, reply with Y, and then notify me."
     * Example input: "Remind me tomorrow at 3 PM about X"
     */
    public function parseRule(string $naturalLanguageText): array
    {
        $text = strtolower($naturalLanguageText);
        $result = [
            'type' => 'unknown',
            'conditions' => [],
            'actions' => [],
            'next_run_at' => null,
            'event_type' => null,
        ];

        // 1. Check for time-based reminder rules
        if (Str::contains($text, ['remind me', 'send a message to', 'notify me'])) {
            if (Str::contains($text, 'tomorrow') || preg_match('/at (\d+)\s*(am|pm)?/', $text)) {
                $result['type'] = 'time_based';
                $result['next_run_at'] = $this->parseTime($text);
                
                $actionMessage = $this->extractActionMessage($text);
                $result['actions']['notify'] = [
                    'message' => $actionMessage,
                    'recipient' => 'Hedra' // Default user
                ];
                return $result;
            }
        }

        // 2. Check for event-based condition rules
        if (Str::startsWith($text, 'if') || Str::contains($text, 'when')) {
            $result['type'] = 'event_based';
            $result['event_type'] = 'ContactMessageReceived'; // Default assumed event

            // Extract conditions
            if (Str::contains($text, 'contacts me')) {
                // Extract contact name roughly
                preg_match('/if (.*?) contacts me/i', $text, $matches);
                if (isset($matches[1])) {
                    $result['conditions']['contact_name'] = trim($matches[1]);
                }
            }

            if (Str::contains($text, 'regarding')) {
                preg_match('/regarding (.*?)(?:,|$)/i', $text, $matches);
                if (isset($matches[1])) {
                    $result['conditions']['topic'] = trim($matches[1]);
                }
            }

            // Extract actions
            if (Str::contains($text, 'reply with')) {
                preg_match('/reply with (.*?)(?:,|$)/i', $text, $matches);
                if (isset($matches[1])) {
                    $result['actions']['reply'] = ['message' => trim($matches[1])];
                }
            }

            if (Str::contains($text, 'notify me')) {
                $result['actions']['notify'] = ['message' => 'Autonomous action completed based on rule.'];
            }

            return $result;
        }

        // Fallback for generic inputs
        $result['actions']['notify'] = ['message' => 'Unparsed rule: ' . $naturalLanguageText];
        return $result;
    }

    /**
     * Dummy time parser for NLP (Simplistic implementation for demonstration)
     */
    protected function parseTime(string $text): ?string
    {
        $now = Carbon::now();
        if (Str::contains($text, 'tomorrow')) {
            $now->addDay();
        }

        if (preg_match('/at (\d+)\s*(am|pm)?/i', $text, $matches)) {
            $hour = (int) $matches[1];
            $ampm = isset($matches[2]) ? strtolower($matches[2]) : 'am';
            
            if ($ampm === 'pm' && $hour < 12) {
                $hour += 12;
            }
            if ($ampm === 'am' && $hour === 12) {
                $hour = 0;
            }
            
            $now->setTime($hour, 0);
        }

        return $now->toDateTimeString();
    }

    protected function extractActionMessage(string $text): string
    {
        if (preg_match('/about (.*)$/i', $text, $matches)) {
            return 'Reminder: ' . trim($matches[1]);
        }
        return 'Scheduled Reminder from AI Assistant';
    }
}
