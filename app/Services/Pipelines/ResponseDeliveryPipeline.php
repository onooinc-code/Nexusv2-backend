<?php

namespace App\Services\Pipelines;

use App\Models\Message;
use App\Models\Conversation;
use Illuminate\Support\Facades\Log;

class ResponseDeliveryPipeline
{
    protected array $formatters = [];
    protected array $channels = [];

    public function __construct(array $formatters = [], array $channels = [])
    {
        $this->formatters = $formatters;
        $this->channels = $channels;
    }

    public function registerFormatter(string $formatType, callable $formatter): void
    {
        $this->formatters[$formatType] = $formatter;
    }

    public function registerChannel(string $channelType, array $config): void
    {
        $this->channels[$channelType] = $config;
    }

    public function deliver(array $payload): array
    {
        $content = $payload['content'] ?? '';
        $conversationId = $payload['conversation_id'] ?? null;
        $channel = $payload['channel'] ?? 'default';
        $format = $payload['format'] ?? 'text';
        $metadata = $payload['metadata'] ?? [];

        $formatted = $this->format($content, $format);
        $channelConfig = $this->channels[$channel] ?? [];

        $message = null;
        if ($conversationId) {
            $message = Message::create([
                'conversation_id' => $conversationId,
                'sender_type' => 'agent',
                'sender_id' => $metadata['agent_id'] ?? null,
                'direction' => 'outbound',
                'content_type' => $format,
                'content' => $formatted,
                'metadata' => array_merge($metadata, [
                    'delivery_channel' => $channel,
                    'formatted' => $format,
                ]),
                'status' => 'sent',
                'sent_at' => now(),
            ]);
        }

        Log::info('Response delivered', [
            'message_id' => $message?->id,
            'channel' => $channel,
            'format' => $format,
            'content_length' => strlen($formatted),
        ]);

        return [
            'success' => true,
            'message' => $message,
            'formatted_content' => $formatted,
            'channel' => $channel,
            'format' => $format,
        ];
    }

    protected function format(string $content, string $format): string
    {
        if (isset($this->formatters[$format])) {
            return ($this->formatters[$format])($content);
        }

        return match ($format) {
            'markdown' => $this->formatMarkdown($content),
            'html' => $this->formatHtml($content),
            'json' => json_encode(['content' => $content], JSON_PRETTY_PRINT),
            'text' => $content,
            default => $content,
        };
    }

    protected function formatMarkdown(string $content): string
    {
        return $content;
    }

    protected function formatHtml(string $content): string
    {
        return nl2br(e($content));
    }

    public function getAvailableChannels(): array
    {
        return array_keys($this->channels);
    }

    public function getAvailableFormats(): array
    {
        return array_keys($this->formatters);
    }
}
