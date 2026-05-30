<?php

namespace App\Services\AiModelsHub;

class PayloadAdapterFactory
{
    /**
     * Adapt request to provider-specific format
     */
    public function adaptPayload($format, array $data)
    {
        switch ($format) {
            case 'openai':
                return $this->adaptForOpenAI($data);
            case 'anthropic':
                return $this->adaptForAnthropic($data);
            case 'groq':
                return $this->adaptForGroq($data);
            case 'google':
                return $this->adaptForGoogle($data);
            default:
                // Default to OpenAI format
                return $this->adaptForOpenAI($data);
        }
    }

    /**
     * Adapt response from provider to generic format
     */
    public function adaptResponse($format, $response)
    {
        switch ($format) {
            case 'openai':
                return $this->adaptOpenAIResponse($response);
            case 'anthropic':
                return $this->adaptAnthropicResponse($response);
            case 'groq':
                return $this->adaptGroqResponse($response);
            case 'google':
                return $this->adaptGoogleResponse($response);
            default:
                // Default to OpenAI format
                return $this->adaptOpenAIResponse($response);
        }
    }

    /**
     * OpenAI format adaptation
     */
    protected function adaptForOpenAI(array $data)
    {
        return [
            'model' => $this->getModelIdentifier($data['model_id'] ?? ''),
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $data['prompt']
                ]
            ],
            'temperature' => $data['parameters']['temperature'] ?? 0.7,
            'max_tokens' => $data['parameters']['max_tokens'] ?? null,
            'stream' => $data['parameters']['stream'] ?? false,
        ];
    }

    /**
     * Anthropic format adaptation
     */
    protected function adaptForAnthropic(array $data)
    {
        return [
            'model' => $this->getModelIdentifier($data['model_id'] ?? ''),
            'max_tokens' => $data['parameters']['max_tokens'] ?? 1024,
            'temperature' => $data['parameters']['temperature'] ?? 0.7,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $data['prompt']
                ]
            ],
            'system' => $data['context']['system'] ?? null,
        ];
    }

    /**
     * Groq format adaptation (similar to OpenAI)
     */
    protected function adaptForGroq(array $data)
    {
        return [
            'model' => $this->getModelIdentifier($data['model_id'] ?? ''),
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $data['prompt']
                ]
            ],
            'temperature' => $data['parameters']['temperature'] ?? 0.7,
            'max_tokens' => $data['parameters']['max_tokens'] ?? null,
            'stream' => $data['parameters']['stream'] ?? false,
        ];
    }

    /**
     * Google format adaptation
     */
    protected function adaptForGoogle(array $data)
    {
        return [
            'model' => $this->getModelIdentifier($data['model_id'] ?? ''),
            'contents' => [
                [
                    'parts' => [
                        [
                            'text' => $data['prompt']
                        ]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => $data['parameters']['temperature'] ?? 0.7,
                'maxOutputTokens' => $data['parameters']['max_tokens'] ?? null,
                'topP' => $data['parameters']['top_p'] ?? 0.9,
                'topK' => $data['parameters']['top_k'] ?? 40,
            ],
        ];
    }

    /**
     * Get model identifier - in a real implementation, this would map UUID to external ID
     */
    protected function getModelIdentifier($modelId)
    {
        // In a full implementation, we'd look up the model by UUID to get external_id
        // For now, we'll return the ID as-is assuming it's the external identifier
        return $modelId;
    }

    /**
     * Adapt OpenAI response to generic format
     */
    protected function adaptOpenAIResponse($response)
    {
        if (isset($response['choices'][0]['message']['content'])) {
            $content = $response['choices'][0]['message']['content'];
        } elseif (isset($response['choices'][0]['text'])) {
            $content = $response['choices'][0]['text'];
        } else {
            $content = '';
        }

        return [
            'content' => $content,
            'usage' => [
                'input_tokens' => $response['usage']['prompt_tokens'] ?? 0,
                'output_tokens' => $response['usage']['completion_tokens'] ?? 0,
                'total_tokens' => $response['usage']['total_tokens'] ?? 0,
            ],
            'model' => $response['model'] ?? null,
        ];
    }

    /**
     * Adapt Anthropic response to generic format
     */
    protected function adaptAnthropicResponse($response)
    {
        $content = '';
        if (isset($response['content'][0]['text'])) {
            $content = $response['content'][0]['text'];
        }

        return [
            'content' => $content,
            'usage' => [
                'input_tokens' => $response['usage']['input_tokens'] ?? 0,
                'output_tokens' => $response['usage']['output_tokens'] ?? 0,
                'total_tokens' => ($response['usage']['input_tokens'] ?? 0) + ($response['usage']['output_tokens'] ?? 0),
            ],
            'model' => $response['model'] ?? null,
        ];
    }

    /**
     * Adapt Groq response to generic format (similar to OpenAI)
     */
    protected function adaptGroqResponse($response)
    {
        return $this->adaptOpenAIResponse($response);
    }

    /**
     * Adapt Google response to generic format
     */
    protected function adaptGoogleResponse($response)
    {
        $content = '';
        if (isset($response['candidates'][0]['content']['parts'][0]['text'])) {
            $content = $response['candidates'][0]['content']['parts'][0]['text'];
        }

        return [
            'content' => $content,
            'usage' => [
                'input_tokens' => $response['usageMetadata']['promptTokenCount'] ?? 0,
                'output_tokens' => $response['usageMetadata']['candidatesTokenCount'] ?? 0,
                'total_tokens' => $response['usageMetadata']['totalTokenCount'] ?? 0,
            ],
            'model' => $response['model'] ?? null,
        ];
    }
}