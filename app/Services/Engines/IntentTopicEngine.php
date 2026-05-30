<?php

namespace App\Services\Engines;

use Illuminate\Support\Facades\Log;

class IntentTopicEngine
{
    protected array $intentPatterns = [
        'greeting' => ['hello', 'hi', 'hey', 'good morning', 'good evening', 'howdy', 'yo'],
        'farewell' => ['bye', 'goodbye', 'see you', 'later', 'take care', 'farewell', 'cya'],
        'question' => ['what', 'how', 'why', 'when', 'where', 'who', 'which', 'can you', 'could you', '?'],
        'request' => ['please', 'can you', 'could you', 'would you', 'help me', 'i need', 'i want'],
        'complaint' => ['problem', 'issue', 'error', 'broken', 'not working', 'frustrated', 'disappointed'],
        'praise' => ['thank', 'great', 'awesome', 'excellent', 'wonderful', 'amazing', 'love'],
        'schedule' => ['schedule', 'meeting', 'appointment', 'calendar', 'book', 'reserve', 'slot'],
        'support' => ['help', 'support', 'assist', 'guide', 'troubleshoot', 'fix'],
        'billing' => ['bill', 'invoice', 'payment', 'charge', 'refund', 'price', 'cost'],
        'feedback' => ['feedback', 'suggestion', 'idea', 'improve', 'feature request'],
    ];

    protected array $topicKeywords = [
        'product' => ['product', 'item', 'goods', 'merchandise', 'catalog', 'inventory'],
        'service' => ['service', 'offering', 'solution', 'support plan', 'subscription'],
        'account' => ['account', 'profile', 'login', 'password', 'settings', 'preferences'],
        'order' => ['order', 'purchase', 'transaction', 'checkout', 'cart', 'delivery'],
        'technical' => ['api', 'integration', 'code', 'bug', 'error', 'deploy', 'server', 'database'],
        'billing' => ['invoice', 'payment', 'charge', 'refund', 'subscription', 'billing'],
        'general' => ['hello', 'hi', 'thanks', 'bye', 'ok', 'yes', 'no'],
    ];

    public function detectIntent(string $message): array
    {
        $lower = strtolower($message);
        $scores = [];

        foreach ($this->intentPatterns as $intent => $patterns) {
            $score = 0;
            foreach ($patterns as $pattern) {
                if (str_contains($lower, $pattern)) {
                    $score++;
                }
            }
            if ($score > 0) {
                $scores[$intent] = $score;
            }
        }

        if (empty($scores)) {
            return [
                'intent' => 'unknown',
                'confidence' => 0.0,
                'all_scores' => [],
            ];
        }

        arsort($scores);
        $topIntent = array_key_first($scores);
        $total = array_sum($scores);
        $confidence = round(($scores[$topIntent] / $total) * 100, 2);

        return [
            'intent' => $topIntent,
            'confidence' => $confidence,
            'all_scores' => $scores,
        ];
    }

    public function detectTopic(string $message): array
    {
        $lower = strtolower($message);
        $scores = [];

        foreach ($this->topicKeywords as $topic => $keywords) {
            $score = 0;
            foreach ($keywords as $keyword) {
                if (str_contains($lower, $keyword)) {
                    $score++;
                }
            }
            if ($score > 0) {
                $scores[$topic] = $score;
            }
        }

        if (empty($scores)) {
            return [
                'topic' => 'general',
                'confidence' => 0.0,
                'all_scores' => [],
            ];
        }

        arsort($scores);
        $topTopic = array_key_first($scores);
        $total = array_sum($scores);
        $confidence = round(($scores[$topTopic] / $total) * 100, 2);

        return [
            'topic' => $topTopic,
            'confidence' => $confidence,
            'all_scores' => $scores,
        ];
    }

    public function analyze(string $message): array
    {
        $intent = $this->detectIntent($message);
        $topic = $this->detectTopic($message);

        return [
            'success' => true,
            'intent' => $intent['intent'],
            'intent_confidence' => $intent['confidence'],
            'intent_scores' => $intent['all_scores'],
            'topic' => $topic['topic'],
            'topic_confidence' => $topic['confidence'],
            'topic_scores' => $topic['all_scores'],
        ];
    }

    public function getAvailableIntents(): array
    {
        return array_keys($this->intentPatterns);
    }

    public function getAvailableTopics(): array
    {
        return array_keys($this->topicKeywords);
    }
}
