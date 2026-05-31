<?php

namespace App\Services\Contact;

use Carbon\Carbon;

class FacebookImportParser
{
    /**
     * Parse Facebook JSON export format (from Download Your Info)
     * Expected structure: { "participants": [...], "messages": [...] }
     *
     * @param string $jsonContent
     * @param string|null $timezone
     * @return array
     */
    public function parseJson(string $jsonContent, ?string $timezone = 'UTC'): array
    {
        $data = json_decode($jsonContent, true);

        if (!is_array($data)) {
            return [];
        }

        // Extract participants for reference
        $participants = $data['participants'] ?? [];
        $participantMap = $this->buildParticipantMap($participants);

        // Extract messages
        $messagesArray = $data['messages'] ?? [];
        if (!is_array($messagesArray)) {
            return [];
        }

        $messages = [];
        $threadId = $data['title'] ?? $data['thread_id'] ?? null;

        foreach ($messagesArray as $msg) {
            if (!is_array($msg)) {
                continue;
            }

            // Extract key fields
            $body = $msg['content'] ?? $msg['text'] ?? '';
            $sender = $msg['sender_name'] ?? 'Unknown';
            $timestamp = $msg['timestamp_ms'] ?? $msg['timestamp'] ?? null;
            $sourceId = $msg['id'] ?? hash('md5', serialize($msg));

            // Skip empty messages
            if (empty(trim($body))) {
                continue;
            }

            // Skip system messages
            if ($this->isSystemMessage($body)) {
                continue;
            }

            // Parse timestamp (Facebook uses milliseconds)
            $parsedTime = $this->parseTimestamp($timestamp, $timezone);
            if ($parsedTime === null) {
                continue;
            }

            $messages[] = [
                'timestamp' => $parsedTime,
                'sender_name' => $sender,
                'sender_identifier' => $this->normalizeSender($sender),
                'body' => $body,
                'direction' => 'inbound', // Facebook doesn't distinguish clearly, default to inbound
                'source' => 'facebook',
                'source_id' => $sourceId,
                'source_thread_id' => $threadId,
                'channel' => 'facebook_messenger',
                'attachments_metadata' => $this->extractAttachments($msg),
                'raw_metadata' => [
                    'sticker' => $msg['sticker'] ?? null,
                    'share' => $msg['share'] ?? null,
                ],
                'dedupe_hash' => null, // Will be computed later
            ];
        }

        return $messages;
    }

    /**
     * Parse Facebook TXT export format (if available)
     * Format: typically "SenderName: Message content\n"
     *
     * @param string $content
     * @param string|null $timezone
     * @return array
     */
    public function parseTxt(string $content, ?string $timezone = 'UTC'): array
    {
        $messages = [];
        $lines = explode("\n", $content);
        $currentTime = Carbon::now($timezone);
        $msgIndex = 0;

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }

            // Try to match "Sender: Message" pattern
            if (preg_match('/^(.+?):\s+(.*)$/u', $line, $matches)) {
                $sender = trim($matches[1]);
                $body = trim($matches[2]);

                // Skip empty messages and system messages
                if (empty($body) || $this->isSystemMessage($body)) {
                    continue;
                }

                // Estimate timestamp (no timestamp in TXT, use sequence)
                $timestamp = $currentTime->copy()->addSeconds($msgIndex);

                $messages[] = [
                    'timestamp' => $timestamp->setTimezone('UTC')->toDateTimeString(),
                    'sender_name' => $sender,
                    'sender_identifier' => $this->normalizeSender($sender),
                    'body' => $body,
                    'direction' => 'inbound',
                    'source' => 'facebook',
                    'source_id' => null, // TXT doesn't have IDs
                    'channel' => 'facebook_messenger',
                    'attachments_metadata' => [],
                    'dedupe_hash' => null,
                ];

                $msgIndex++;
            }
        }

        return $messages;
    }

    /**
     * Build a map of participants for reference
     *
     * @param array $participants
     * @return array
     */
    private function buildParticipantMap(array $participants): array
    {
        $map = [];

        foreach ($participants as $participant) {
            if (is_array($participant) && isset($participant['name'])) {
                $map[strtolower($participant['name'])] = $participant;
            }
        }

        return $map;
    }

    /**
     * Extract attachment information
     *
     * @param array $message
     * @return array
     */
    private function extractAttachments(array $message): array
    {
        $attachments = [];

        if (isset($message['attachments']) && is_array($message['attachments'])) {
            foreach ($message['attachments'] as $attachment) {
                $attachments[] = [
                    'type' => $attachment['type'] ?? 'unknown',
                    'url' => $attachment['href'] ?? null,
                    'title' => $attachment['title'] ?? null,
                ];
            }
        }

        return $attachments;
    }

    /**
     * Normalize sender identifier for contact matching
     *
     * @param string $sender
     * @return string
     */
    private function normalizeSender(string $sender): string
    {
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
            'liked a message',
            'reacted',
            'ended a video chat',
            'started a video chat',
            'missed the call',
            'added you',
            'left the conversation',
            'set the group topic',
            'made you admin',
            'removed you as admin',
            'changed the group photo',
            'removed group photo',
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
     * Parse various timestamp formats (Facebook uses milliseconds typically)
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

        try {
            // Facebook typically uses milliseconds
            if (is_numeric($timestamp)) {
                $seconds = intval($timestamp / 1000);
                return Carbon::createFromTimestamp($seconds, $timezone)
                    ->setTimezone('UTC')
                    ->toDateTimeString();
            }

            // Try string parsing
            if (is_string($timestamp)) {
                $formats = [
                    'Y-m-d H:i:s',
                    'Y-m-d\TH:i:s',
                    'd/m/Y, H:i',
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
        } catch (\Exception $e) {
            return null;
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
