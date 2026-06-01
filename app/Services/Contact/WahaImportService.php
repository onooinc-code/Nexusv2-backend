<?php

namespace App\Services\Contact;

use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class WahaImportService
{
    protected string $wahaUrl;

    public function __construct()
    {
        $this->wahaUrl = config('services.waha.url', 'http://localhost:3000');
    }

    /**
     * Fetch messages from WAHA API and parse them
     *
     * @param string $session WAHA Session ID
     * @param string $chatId Chat ID (e.g. 123456789@c.us)
     * @param int|null $limit
     * @return array
     */
    public function fetchAndParseMessages(string $session, string $chatId, ?int $limit = 100): array
    {
        $response = Http::get("{$this->wahaUrl}/api/{$session}/chats/{$chatId}/messages", [
            'limit' => $limit,
            'downloadMedia' => false,
        ]);

        if (!$response->successful()) {
            throw new \Exception("Failed to fetch WAHA messages: " . $response->body());
        }

        $messagesArray = $response->json();
        $messages = [];

        foreach ($messagesArray as $msg) {
            if (!is_array($msg)) {
                continue;
            }

            // Map WAHA message structure to standard parsed format
            $body = $msg['body'] ?? $msg['text'] ?? '';
            $fromMe = (bool) ($msg['fromMe'] ?? false);
            $sender = $fromMe ? 'You' : ($msg['from'] ?? 'Unknown');
            $timestamp = $msg['timestamp'] ?? null;
            $sourceId = $msg['id'] ?? null;

            if (empty(trim($body)) || empty($timestamp)) {
                continue;
            }

            // WAHA timestamp is usually a Unix timestamp in seconds
            if (is_numeric($timestamp)) {
                $parsedTime = Carbon::createFromTimestamp($timestamp)->setTimezone('UTC')->toDateTimeString();
            } else {
                $parsedTime = Carbon::parse($timestamp)->setTimezone('UTC')->toDateTimeString();
            }

            $messages[] = [
                'timestamp' => $parsedTime,
                'sender_name' => $sender,
                'sender_identifier' => $this->normalizeSender($sender),
                'body' => $body,
                'direction' => $fromMe ? 'outbound' : 'inbound',
                'source' => 'whatsapp_waha',
                'source_id' => $sourceId,
                'source_thread_id' => $chatId,
                'channel' => 'whatsapp',
                'attachments_metadata' => [],
                'raw_metadata' => [
                    'waha_raw' => $msg
                ],
                'dedupe_hash' => null, // Computed in normalizer
            ];
        }

        return $messages;
    }

    /**
     * Normalize sender identifier
     */
    private function normalizeSender(string $sender): string
    {
        return strtolower(preg_replace('/[^a-zA-Z0-9\s\-_]/', '', $sender));
    }
}
