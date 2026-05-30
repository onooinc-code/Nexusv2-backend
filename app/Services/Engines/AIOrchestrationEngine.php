<?php

namespace App\Services\Engines;

use App\Services\AI\ModelSelector;
use App\Services\AI\FallbackChainService;
use App\Services\AI\CostOptimizer;
use App\Services\AI\QualityRouter;
use App\Services\AI\SpeedRouter;
use App\Services\Pipelines\ContextAssemblyPipeline;
use App\Services\Pipelines\MemoryExtractionPipeline;
use App\Services\Pipelines\ResponseDeliveryPipeline;
use App\Services\Pipelines\PipelineMonitor;
use Illuminate\Support\Facades\Log;

class AIOrchestrationEngine
{
    protected ModelSelector $modelSelector;
    protected FallbackChainService $fallbackChain;
    protected CostOptimizer $costOptimizer;
    protected QualityRouter $qualityRouter;
    protected SpeedRouter $speedRouter;
    protected ContextAssemblyPipeline $contextPipeline;
    protected MemoryExtractionPipeline $memoryPipeline;
    protected ResponseDeliveryPipeline $deliveryPipeline;
    protected PipelineMonitor $monitor;
    protected array $cache = [];
    protected int $cacheTtlSeconds = 300;

    public function __construct(
        ModelSelector $modelSelector,
        FallbackChainService $fallbackChain,
        CostOptimizer $costOptimizer,
        QualityRouter $qualityRouter,
        SpeedRouter $speedRouter,
        ContextAssemblyPipeline $contextPipeline,
        MemoryExtractionPipeline $memoryPipeline,
        ResponseDeliveryPipeline $deliveryPipeline,
        PipelineMonitor $monitor,
    ) {
        $this->modelSelector = $modelSelector;
        $this->fallbackChain = $fallbackChain;
        $this->costOptimizer = $costOptimizer;
        $this->qualityRouter = $qualityRouter;
        $this->speedRouter = $speedRouter;
        $this->contextPipeline = $contextPipeline;
        $this->memoryPipeline = $memoryPipeline;
        $this->deliveryPipeline = $deliveryPipeline;
        $this->monitor = $monitor;
    }

    public function orchestrate(array $request): array
    {
        $startTime = microtime(true);
        $conversationId = $request['conversation_id'] ?? null;
        $contactId = $request['contact_id'] ?? null;
        $content = $request['content'] ?? $request['prompt'] ?? '';
        $routingMode = $request['routing_mode'] ?? 'auto';
        $qualityTier = $request['quality_tier'] ?? null;
        $speedTier = $request['speed_tier'] ?? null;
        $maxCost = $request['max_cost'] ?? null;
        $useFallback = $request['use_fallback'] ?? true;
        $options = $request['options'] ?? [];

        $contextResult = $this->contextPipeline->assemble([
            'conversation_id' => $conversationId,
            'contact_id' => $contactId,
            'include_memories' => true,
            'include_history' => true,
        ]);

        if (!$contextResult['success']) {
            return [
                'success' => false,
                'error' => 'Failed to assemble context',
                'context_result' => $contextResult,
            ];
        }

        $context = $contextResult['context'];
        $fullPrompt = $this->contextPipeline->buildPrompt($context, $content);

        $execRequest = [
            'prompt' => $fullPrompt,
            'options' => $options,
        ];

        $selectionInfo = [];
        switch ($routingMode) {
            case 'quality':
                $tier = $qualityTier ?? 'standard';
                $result = $this->qualityRouter->route($tier, $execRequest);
                $selectionInfo = ['mode' => 'quality', 'tier' => $tier];
                break;
            case 'speed':
                $tier = $speedTier ?? 'normal';
                $result = $this->speedRouter->route($tier, $execRequest);
                $selectionInfo = ['mode' => 'speed', 'tier' => $tier];
                break;
            case 'cost':
                $optimization = $this->costOptimizer->optimize($execRequest, $maxCost);
                if (!$optimization['success']) {
                    return $optimization;
                }
                $result = $optimization['provider']->execute($execRequest);
                $selectionInfo = ['mode' => 'cost', 'estimated_cost' => $optimization['estimated_cost']];
                break;
            case 'fallback':
                if ($useFallback) {
                    $result = $this->fallbackChain->executeWithFallback($execRequest);
                    $selectionInfo = ['mode' => 'fallback'];
                } else {
                    $result = $this->qualityRouter->route('standard', $execRequest);
                    $selectionInfo = ['mode' => 'quality', 'tier' => 'standard'];
                }
                break;
            case 'auto':
            default:
                $criteria = [];
                if ($maxCost) $criteria['max_cost_per_1k'] = $maxCost;
                $selection = $this->modelSelector->select($criteria);
                if ($selection) {
                    $result = $selection['provider']->execute($execRequest);
                    $selectionInfo = ['mode' => 'auto', 'score' => $selection['score']];
                } else {
                    $result = $this->qualityRouter->route('standard', $execRequest);
                    $selectionInfo = ['mode' => 'quality', 'tier' => 'standard'];
                }
                break;
        }

        $durationMs = round((microtime(true) - $startTime) * 1000, 2);

        if ($result['success'] ?? false) {
            $delivery = $this->deliveryPipeline->deliver([
                'content' => $result['content'] ?? '',
                'conversation_id' => $conversationId,
                'channel' => $options['channel'] ?? 'default',
                'format' => $options['format'] ?? 'text',
                'metadata' => array_merge($result, $selectionInfo, [
                    'orchestration_duration_ms' => $durationMs,
                ]),
            ]);

            $this->monitor->recordStageExecution('orchestration', 'full', $durationMs, true);

            return array_merge($result, [
                'success' => true,
                'orchestration_duration_ms' => $durationMs,
                'selection_info' => $selectionInfo,
                'context' => [
                    'memory_count' => $contextResult['memory_count'],
                    'history_count' => $contextResult['history_count'],
                ],
                'delivery' => $delivery,
            ]);
        }

        $this->monitor->recordStageExecution('orchestration', 'full', $durationMs, false);

        return array_merge($result, [
            'orchestration_duration_ms' => $durationMs,
            'selection_info' => $selectionInfo,
        ]);
    }

    public function getAvailableRoutingModes(): array
    {
        return [
            'auto' => 'Automatic model selection',
            'quality' => 'Quality-based routing',
            'speed' => 'Speed-based routing',
            'cost' => 'Cost-optimized routing',
            'fallback' => 'Fallback chain routing',
        ];
    }
}
