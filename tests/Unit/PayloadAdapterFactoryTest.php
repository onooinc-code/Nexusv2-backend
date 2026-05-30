<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\AiModelsHub\PayloadAdapterFactory;

class PayloadAdapterFactoryTest extends TestCase
{
    /** @test */
    public function it_adapts_payload_for_openai_format()
    {
        $factory = new PayloadAdapterFactory();
        
        $genericPayload = [
            'model' => 'gpt-4',
            'messages' => [
                ['role' => 'user', 'content' => 'Hello'],
                ['role' => 'assistant', 'content' => 'Hi there!'],
            ],
            'temperature' => 0.7,
            'max_tokens' => 100,
        ];
        
        $adapted = $factory->adaptPayload('openai', $genericPayload);
        
        $this->assertEquals('gpt-4', $adapted['model']);
        $this->assertEquals([
            ['role' => 'user', 'content' => 'Hello'],
            ['role' => 'assistant', 'content' => 'Hi there!'],
        ], $adapted['messages']);
        $this->assertEquals(0.7, $adapted['temperature']);
        $this->assertEquals(100, $adapted['max_tokens']);
    }
    
    /** @test */
    public function it_adapts_payload_for_anthropic_format()
    {
        $factory = new PayloadAdapterFactory();
        
        $genericPayload = [
            'model' => 'claude-3-opus',
            'messages' => [
                ['role' => 'user', 'content' => 'Hello'],
                ['role' => 'assistant', 'content' => 'Hi there!'],
            ],
            'temperature' => 0.7,
            'max_tokens' => 100,
        ];
        
        $adapted = $factory->adaptPayload('anthropic', $genericPayload);
        
        $this->assertEquals('claude-3-opus', $adapted['model']);
        $this->assertEquals('Hello\n\nHi there!', $adapted['prompt']);
        $this->assertEquals(0.7, $adapted['temperature']);
        $this->assertEquals(100, $adapted['max_tokens']);
    }
    
    /** @test */
    public function it_adapts_payload_for_groq_format()
    {
        $factory = new PayloadAdapterFactory();
        
        $genericPayload = [
            'model' => 'mixtral-8x7b',
            'messages' => [
                ['role' => 'user', 'content' => 'Hello'],
                ['role' => 'assistant', 'content' => 'Hi there!'],
            ],
            'temperature' => 0.7,
            'max_tokens' => 100,
        ];
        
        $adapted = $factory->adaptPayload('groq', $genericPayload);
        
        $this->assertEquals('mixtral-8x7b', $adapted['model']);
        $this->assertEquals([
            ['role' => 'user', 'content' => 'Hello'],
            ['role' => 'assistant', 'content' => 'Hi there!'],
        ], $adapted['messages']);
        $this->assertEquals(0.7, $adapted['temperature']);
        $this->assertEquals(100, $adapted['max_tokens']);
    }
    
    /** @test */
    public function it_adapts_payload_for_gemini_format()
    {
        $factory = new PayloadAdapterFactory();
        
        $genericPayload = [
            'model' => 'gemini-pro',
            'messages' => [
                ['role' => 'user', 'content' => 'Hello'],
                ['role' => 'assistant', 'content' => 'Hi there!'],
            ],
            'temperature' => 0.7,
            'max_tokens' => 100,
        ];
        
        $adapted = $factory->adaptPayload('gemini', $genericPayload);
        
        $this->assertArrayHasKey('contents', $adapted);
        $this->assertEquals([
            ['role' => 'user', 'parts' => [['text' => 'Hello']]],
            ['role' => 'model', 'parts' => [['text' => 'Hi there!']]],
        ], $adapted['contents']);
        $this->assertArrayHasKey('generationConfig', $adapted);
        $this->assertEquals(0.7, $adapted['generationConfig']['temperature']);
        $this->assertEquals(100, $adapted['generationConfig']['maxOutputTokens']);
    }
    
    /** @test */
    public function it_adapts_response_from_openai_format()
    {
        $factory = new PayloadAdapterFactory();
        
        $providerResponse = [
            'id' => 'chatcmpl-123',
            'object' => 'chat.completion',
            'created' => 1677858242,
            'model' => 'gpt-4',
            'choices' => [
                [
                    'index' => 0,
                    'message' => [
                        'role' => 'assistant',
                        'content' => 'Hello, how can I help you?',
                    ],
                    'finish_reason' => 'stop',
                ]
            ],
            'usage' => [
                'prompt_tokens' => 10,
                'completion_tokens' => 8,
                'total_tokens' => 18,
            ]
        ];
        
        $adapted = $factory->adaptResponse('openai', $providerResponse);
        
        $this->assertEquals('Hello, how can I help you?', $adapted['content']);
        $this->assertEquals(10, $adapted['usage']['prompt_tokens']);
        $this->assertEquals(8, $adapted['usage']['completion_tokens']);
        $this->assertEquals(18, $adapted['usage']['total_tokens']);
    }
    
    /** @test */
    public function it_adapts_response_from_anthropic_format()
    {
        $factory = new PayloadAdapterFactory();
        
        $providerResponse = [
            'id' => 'msg_123',
            'type' => 'message',
            'role' => 'assistant',
            'content' => 'Hello, how can I help you?',
            'model' => 'claude-3-opus',
            'stop_reason' => 'end_turn',
            'stop_sequence' => null,
            'usage' => [
                'input_tokens' => 10,
                'output_tokens' => 8,
            ]
        ];
        
        $adapted = $factory->adaptResponse('anthropic', $providerResponse);
        
        $this->assertEquals('Hello, how can I help you?', $adapted['content']);
        $this->assertEquals(10, $adapted['usage']['prompt_tokens']);
        $this->assertEquals(8, $adapted['usage']['completion_tokens']);
        $this->assertEquals(18, $adapted['usage']['total_tokens']);
    }
    
    /** @test */
    public function it_adapts_response_from_gemini_format()
    {
        $factory = new PayloadAdapterFactory();
        
        $providerResponse = [
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            [
                                'text' => 'Hello, how can I help you?',
                            ]
                        ]
                    ],
                    'finishReason' => 'STOP',
                    'index' => 0,
                    'safetyRatings' => []
                ]
            ],
            'usageMetadata' => [
                'promptTokenCount' => 10,
                'candidatesTokenCount' => 8,
                'totalTokenCount' => 18,
            ],
            'modelVersion' => 'gemini-pro'
        ];
        
        $adapted = $factory->adaptResponse('gemini', $providerResponse);
        
        $this->assertEquals('Hello, how can I help you?', $adapted['content']);
        $this->assertEquals(10, $adapted['usage']['prompt_tokens']);
        $this->assertEquals(8, $adapted['usage']['completion_tokens']);
        $this->assertEquals(18, $adapted['usage']['total_tokens']);
    }
}