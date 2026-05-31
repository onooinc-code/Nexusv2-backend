<?php

namespace App\Services\Contact;

use Carbon\Carbon;
use Illuminate\Support\Str;

class WhatsAppImportParser
{
    /**
     * Parse WhatsApp TXT export format
     * Expected format: [2026-05-31, 14:30:45] Sender: Message content
     *
     * @param string $content
     * @param string $phoneNumber Contact's WhatsApp phone number
     * @param string|null $timezone Timezone for timestamp parsing
     * @return array
     */
    public function parseTxt(string $content, string $phoneNumber, ?string $timezone = 'UTC'): array
    {
        $messages = [];
        $lines = explode("\n", $content);

        $current = null;

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }

            // Pattern: [YYYY-MM-DD, HH:MM:SS] Sender: Message
            if (preg_match('/^\[(\d{4}-\d{2}-\d{2}),\s*(\d{2}:\d{2}(?::\d{2})?)\]\s+(.+?):\s+(.*)$/u', $line, $matches)) {
                if ($current !== null && ! $this->isSystemMessage($current['body'])) {
                    $messages[] = $current;
                }

                $current = $this->buildTxtMessage($matches[1], $matches[2], trim($matches[3]), trim($matches[4]), 'Y-m-d', $phoneNumber, $timezone);
                continue;
            }

            // Common WhatsApp export format: 31/05/2026, 2:30 PM - Sender: Message
            if (preg_match('/^(\d{1,2}[\/\-]\d{1,2}[\/\-]\d{2,4}),\s*(\d{1,2}:\d{2}(?::\d{2})?\s*(?:AM|PM|am|pm)?)\s+-\s+(.+?):\s+(.*)$/u', $line, $matches)) {
                if ($current !== null && ! $this->isSystemMessage($current['body'])) {
                    $messages[] = $current;
                }

                $current = $this->buildTxtMessage($matches[1], $matches[2], trim($matches[3]), trim($matches[4]), null, $phoneNumber, $timezone);
                continue;
            }

            // Multi-line message continuation.
            if ($current !== null) {
                $current['body'] .= "\n" . $line;
            }
        }

        if ($current !== null && ! $this->isSystemMessage($current['body'])) {
            $messages[] = $current;
        }

        return $messages;
    }

    /**
     * Parse WhatsApp JSON export format
     * Expected: Array of message objects with timestamp, text, from, fromName, etc.
     *
     * @param string $jsonContent
     * @param string $phoneNumber Contact's WhatsApp phone number
     * @param string|null $timezone
     * @return array
     */
    public function parseJson(string $jsonContent, string $phoneNumber, ?string $timezone = 'UTC'): array
    {
        $data = json_decode($jsonContent, true);

        if (!is_array($data)) {
            return [];
        }

        // Handle both direct messages array and nested structure
        $messagesArray = $data['messages'] ?? $data;
        if (!is_array($messagesArray)) {
            return [];
        }

        $messages = [];

        foreach ($messagesArray as $msg) {
            if (!is_array($msg)) {
                continue;
            }

            // Extract key fields - handle variations in JSON structure
            $timestamp = $msg['timestamp'] ?? $msg['date'] ?? $msg['time'] ?? $msg['timestamp_ms'] ?? null;
            $body = $msg['text'] ?? $msg['body'] ?? $msg['message'] ?? $msg['caption'] ?? '';
            $sourceId = $msg['id'] ?? $msg['msgId'] ?? $msg['_id'] ?? null;
            $fromMe = (bool) ($msg['fromMe'] ?? $msg['from_me'] ?? false);
            $sender = $msg['from'] ?? $msg['sender'] ?? $msg['fromName'] ?? $msg['participant'] ?? ($fromMe ? 'You' : 'Unknown');

            // Skip empty messages
            if (empty(trim($body))) {
                continue;
            }

            // Skip system messages
            if ($this->isSystemMessage($body)) {
                continue;
            }

            // Parse timestamp
            $parsedTime = $this->parseTimestamp($timestamp, $timezone);
            if ($parsedTime === null) {
                continue;
            }

            $messages[] = [
                'timestamp' => $parsedTime,
                'sender_name' => $sender,
                'sender_identifier' => $this->normalizeSender($sender),
                'body' => $body,
                'direction' => $fromMe ? 'outbound' : $this->determineDirection($sender, $phoneNumber),
                'source' => 'whatsapp',
                'source_id' => $sourceId,
                'source_thread_id' => $msg['chatId'] ?? $msg['chat_id'] ?? null,
                'channel' => 'whatsapp',
                'attachments_metadata' => $msg['attachments'] ?? $msg['media'] ?? [],
                'raw_metadata' => [
                    'chat_id' => $msg['chatId'] ?? $msg['chat_id'] ?? null,
                    'ack' => $msg['ack'] ?? null,
                    'type' => $msg['type'] ?? null,
                ],
                'dedupe_hash' => null, // Will be computed later
            ];
        }

        return $messages;
    }

    /**
     * Determine message direction (inbound/outbound)
     *
     * @param string $sender
     * @param string $phoneNumber
     * @return string 'inbound' or 'outbound'
     */
    private function determineDirection(string $sender, string $phoneNumber): string
    {
        // If sender is "You" it's outbound. If it contains the target
        // contact phone number, it is an inbound contact message.
        if (
            stripos($sender, 'you') !== false ||
            stripos($sender, 'me') !== false
        ) {
            return 'outbound';
        }

        return 'inbound';
    }

    private function buildTxtMessage(
        string $date,
        string $time,
        string $sender,
        string $body,
        ?string $dateFormat,
        string $phoneNumber,
        string $timezone
    ): array {
        $timestamp = $this->parseTxtTimestamp($date, $time, $timezone, $dateFormat);

        return [
            'timestamp' => $timestamp,
            'sender_name' => $sender,
            'sender_identifier' => $this->normalizeSender($sender),
            'body' => $body,
            'direction' => $this->determineDirection($sender, $phoneNumber),
            'source' => 'whatsapp',
            'source_id' => null,
            'channel' => 'whatsapp',
            'attachments_metadata' => [],
            'raw_metadata' => [],
            'dedupe_hash' => null,
        ];
    }

    private function parseTxtTimestamp(string $date, string $time, string $timezone, ?string $dateFormat): string
    {
        $time = preg_match('/^\d{1,2}:\d{2}$/', $time) ? $time . ':00' : $time;
        $formats = $dateFormat
            ? ["{$dateFormat} H:i:s", "{$dateFormat} g:i A", "{$dateFormat} g:i:s A"]
            : ['d/m/Y H:i:s', 'd/m/Y g:i A', 'd/m/Y g:i:s A', 'm/d/Y H:i:s', 'm/d/Y g:i A', 'm/d/Y g:i:s A', 'd/m/y H:i:s', 'd/m/y g:i A', 'm/d/y H:i:s', 'm/d/y g:i A'];

        foreach ($formats as $format) {
            try {
                return Carbon::createFromFormat($format, "{$date} {$time}", $timezone)
                    ->setTimezone('UTC')
                    ->toDateTimeString();
            } catch (\Throwable) {
                continue;
            }
        }

        return Carbon::parse("{$date} {$time}", $timezone)->setTimezone('UTC')->toDateTimeString();
    }

    /**
     * Normalize sender identifier for contact matching
     *
     * @param string $sender
     * @return string
     */
    private function normalizeSender(string $sender): string
    {
        // Remove special characters, convert to lowercase
        return strtolower(preg_replace('/[^a-zA-Z0-9\s\-_]/', '', $sender));
    }

    /**
     * Check if message is a system message
     *
     * @param string $body
     * @return bool
     */
    private function isSystemMessage(string $body): bool
    {
        $systemPatterns = [
            'messages and calls are encrypted',
            'ended the call',
            'started a call',
            'joined using this group invite link',
            'left',
            'added',
            'removed',
            'changed this group\'s subject',
            'changed this group\'s description',
            'changed the group description',
            'group icon',
            'missed call',
        ];

        $lowerBody = strtolower($body);
        foreach ($systemPatterns as $pattern) {
            if (str_contains($lowerBody, $pattern)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Parse various timestamp formats
     *
     * @param mixed $timestamp
     * @param string $timezone
     * @return string|null
     */
    private function parseTimestamp($timestamp, string $timezone): ?string
    {
        if (empty($timestamp)) {
            return null;
        }

        // If it's already a string timestamp, try common formats
        if (is_string($timestamp)) {
            $formats = [
                'Y-m-d H:i:s',
                'Y-m-d\TH:i:s',
                'Y-m-d\TH:i:s.uP',
                'd/m/Y, H:i',
                'M d, Y, h:i A',
            ];

            foreach ($formats as $format) {
                try {
                    $carbon = Carbon::createFromFormat($format, $timestamp, $timezone);
                    return $carbon->setTimezone('UTC')->toDateTimeString();
                } catch (\Exception $e) {
                    continue;
                }
            }
        }

        // If it's a Unix timestamp (integer)
        if (is_numeric($timestamp)) {
            try {
                $timestamp = (int) $timestamp;
                $seconds = $timestamp > 9999999999 ? (int) floor($timestamp / 1000) : $timestamp;

                return Carbon::createFromTimestamp($seconds, $timezone)
                    ->setTimezone('UTC')
                    ->toDateTimeString();
            } catch (\Exception $e) {
                return null;
            }
        }

        return null;
    }

    /**
     * Calculate dedupe hash for a message
     *
     * @param array $message
     * @return string
     */
    public function calculateDedupeHash(array $message): string
    {
        $data = [
            $message['timestamp'],
            $message['sender_identifier'],
            $message['body'],
            $message['source_id'],
        ];

        return hash('sha256', implode('|', $data));
    }
}
