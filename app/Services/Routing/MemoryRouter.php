<?php

namespace App\Services\Routing;

use App\Models\Memory;
use Illuminate\Support\Facades\Log;

class MemoryRouter
{
    protected array $typeRoutes = [];
    protected ?array $defaultRoute = null;

    public function registerTypeRoute(string $memoryType, array $handler): void
    {
        $this->typeRoutes[$memoryType] = $handler;
    }

    public function setDefaultRoute(array $handler): void
    {
        $this->defaultRoute = $handler;
    }

    public function routeRead(array $context): array
    {
        $memoryType = $context['memory_type'] ?? 'general';
        $contactId = $context['contact_id'] ?? null;
        $userId = $context['user_id'] ?? null;

        $handler = $this->typeRoutes[$memoryType] ?? $this->defaultRoute;

        if (!$handler) {
            return [
                'success' => false,
                'error' => "No read route for memory type: {$memoryType}",
            ];
        }

        $query = Memory::query();
        if ($contactId) {
            $query->forContact($contactId);
        }
        if ($userId) {
            $query->forUser($userId);
        }
        if (isset($context['memory_type'])) {
            $query->where('type', $context['memory_type']);
        }

        $limit = $context['limit'] ?? 10;
        $memories = $query->orderBy('created_at', 'desc')->limit($limit)->get();

        return [
            'success' => true,
            'handler' => $handler,
            'memory_type' => $memoryType,
            'memories' => $memories,
            'count' => $memories->count(),
        ];
    }

    public function routeWrite(array $context): array
    {
        $memoryType = $context['memory_type'] ?? 'general';
        $handler = $this->typeRoutes[$memoryType] ?? $this->defaultRoute;

        if (!$handler) {
            return [
                'success' => false,
                'error' => "No write route for memory type: {$memoryType}",
            ];
        }

        $requiredFields = $handler['required_fields'] ?? ['content'];
        foreach ($requiredFields as $field) {
            if (empty($context[$field])) {
                return [
                    'success' => false,
                    'error' => "Missing required field: {$field}",
                ];
            }
        }

        return [
            'success' => true,
            'handler' => $handler,
            'memory_type' => $memoryType,
            'action' => 'write',
        ];
    }

    public function getTypeRoutes(): array
    {
        return $this->typeRoutes;
    }

    public function getMemoryTypes(): array
    {
        return array_keys($this->typeRoutes);
    }
}
