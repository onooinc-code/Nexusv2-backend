<?php

namespace App\Services\Routing;

use Illuminate\Support\Facades\Log;

class ToneRouter
{
    protected array $tones = [
        'professional' => [
            'description' => 'Formal, business-appropriate language',
            'formality' => 'high',
            'emoji' => false,
            'sentence_length' => 'medium',
        ],
        'casual' => [
            'description' => 'Relaxed, conversational language',
            'formality' => 'low',
            'emoji' => true,
            'sentence_length' => 'short',
        ],
        'friendly' => [
            'description' => 'Warm, approachable language',
            'formality' => 'medium',
            'emoji' => true,
            'sentence_length' => 'medium',
        ],
        'technical' => [
            'description' => 'Precise, domain-specific terminology',
            'formality' => 'high',
            'emoji' => false,
            'sentence_length' => 'long',
        ],
        'empathetic' => [
            'description' => 'Understanding, supportive language',
            'formality' => 'medium',
            'emoji' => false,
            'sentence_length' => 'medium',
        ],
        'concise' => [
            'description' => 'Brief, to-the-point responses',
            'formality' => 'medium',
            'emoji' => false,
            'sentence_length' => 'short',
        ],
    ];

    protected array $contactTonePreferences = [];

    public function setContactTonePreference(string $contactId, string $tone): void
    {
        if (!isset($this->tones[$tone])) {
            throw new \InvalidArgumentException("Unknown tone: {$tone}");
        }
        $this->contactTonePreferences[$contactId] = $tone;
    }

    public function getToneForContact(string $contactId): string
    {
        return $this->contactTonePreferences[$contactId] ?? 'professional';
    }

    public function route(array $request): array
    {
        $contactId = $request['contact_id'] ?? null;
        $explicitTone = $request['tone'] ?? null;
        $messageContent = strtolower($request['content'] ?? '');

        $tone = $explicitTone ?? $this->detectTone($messageContent);

        if ($contactId) {
            $tone = $this->getToneForContact($contactId);
        }

        if (!isset($this->tones[$tone])) {
            return [
                'success' => false,
                'error' => "Unknown tone: {$tone}",
                'available_tones' => array_keys($this->tones),
            ];
        }

        $toneConfig = $this->tones[$tone];

        return [
            'success' => true,
            'tone' => $tone,
            'config' => $toneConfig,
            'system_prompt_addition' => $this->buildSystemPrompt($tone, $toneConfig),
        ];
    }

    protected function detectTone(string $content): string
    {
        $indicators = [
            'professional' => ['please', 'thank you', 'regards', 'sincerely', 'appreciate', 'formal'],
            'casual' => ['hey', 'yo', 'lol', 'haha', 'bro', 'dude', 'what\'s up', 'nm', 'brb'],
            'friendly' => ['hello', 'hi there', 'how are you', 'nice to meet', 'glad', 'wonderful'],
            'technical' => ['api', 'json', 'error code', 'stack trace', 'debug', 'deploy', 'refactor'],
            'empathetic' => ['sorry', 'understand', 'feel', 'frustrated', 'disappointed', 'worried', 'help'],
            'concise' => ['quick', 'brief', 'short', 'tl;dr', 'summary', 'bottom line'],
        ];

        $scores = array_fill_keys(array_keys($this->tones), 0);

        foreach ($indicators as $tone => $words) {
            foreach ($words as $word) {
                if (str_contains($content, $word)) {
                    $scores[$tone]++;
                }
            }
        }

        arsort($scores);
        $topTone = array_key_first($scores);

        return $scores[$topTone] > 0 ? $topTone : 'professional';
    }

    protected function buildSystemPrompt(string $tone, array $config): string
    {
        $instructions = [
            'professional' => 'Respond in a formal, professional manner. Use complete sentences and proper grammar.',
            'casual' => 'Respond in a casual, relaxed manner. Feel free to use informal language and emojis.',
            'friendly' => 'Respond in a warm, friendly manner. Be approachable and positive.',
            'technical' => 'Respond with technical precision. Use domain-specific terminology and provide detailed explanations.',
            'empathetic' => 'Respond with empathy and understanding. Acknowledge feelings and provide supportive guidance.',
            'concise' => 'Respond briefly and to the point. Avoid unnecessary elaboration.',
        ];

        $base = $instructions[$tone] ?? $instructions['professional'];

        if ($config['emoji'] ?? false) {
            $base .= " Use emojis where appropriate.";
        }
        if (($config['formality'] ?? 'medium') === 'high') {
            $base .= " Maintain a high level of formality.";
        }

        return $base;
    }

    public function getAvailableTones(): array
    {
        $result = [];
        foreach ($this->tones as $name => $config) {
            $result[$name] = [
                'description' => $config['description'],
                'formality' => $config['formality'],
            ];
        }
        return $result;
    }
}
