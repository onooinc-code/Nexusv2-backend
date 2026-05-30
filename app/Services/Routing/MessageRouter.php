<?php

namespace App\Services\Routing;

use App\Models\Message;
use App\Models\Conversation;
use Illuminate\Support\Facades\Log;

class MessageRouter
{
    protected array $routes = [];
    protected array $middleware = [];

    public function __construct(array $routes = [])
    {
        $this->routes = $routes;
    }

    public function registerRoute(string $pattern, array $handler, array $options = []): void
    {
        $this->routes[] = [
            'pattern' => $pattern,
            'handler' => $handler,
            'options' => $options,
        ];
    }

    public function route(Message $message, Conversation $conversation): array
    {
        $content = strtolower($message->content ?? '');
        $metadata = $message->metadata ?? [];

        $context = [
            'message' => $message,
            'conversation' => $conversation,
            'content' => $content,
            'metadata' => $metadata,
            'sender_type' => $message->sender_type,
            'direction' => $message->direction,
        ];

        foreach ($this->routes as $route) {
            if ($this->matches($route['pattern'], $content, $metadata)) {
                $context['route'] = $route;
                Log::info('Message routed', [
                    'message_id' => $message->id,
                    'pattern' => $route['pattern'],
                    'handler' => $route['handler']['type'] ?? 'unknown',
                ]);

                return [
                    'success' => true,
                    'route' => $route,
                    'context' => $context,
                    'handler' => $route['handler'],
                ];
            }
        }

        $defaultRoute = $this->findDefaultRoute();
        if ($defaultRoute) {
            $context['route'] = $defaultRoute;
            return [
                'success' => true,
                'route' => $defaultRoute,
                'context' => $context,
                'handler' => $defaultRoute['handler'],
            ];
        }

        return [
            'success' => false,
            'error' => 'No matching route found',
            'context' => $context,
        ];
    }

    protected function matches(string $pattern, string $content, array $metadata): bool
    {
        if (str_contains($pattern, ':')) {
            [$type, $value] = explode(':', $pattern, 2);
            return match ($type) {
                'intent' => isset($metadata['intent']) && str_contains($metadata['intent'], $value),
                'sender' => isset($metadata['sender_type']) && $metadata['sender_type'] === $value,
                'direction' => isset($metadata['direction']) && $metadata['direction'] === $value,
                'type' => isset($metadata['type']) && $metadata['type'] === $value,
                'regex' => preg_match("/{$value}/i", $content) === 1,
                default => str_contains($content, $pattern),
            };
        }

        return str_contains($content, $pattern);
    }

    protected function findDefaultRoute(): ?array
    {
        foreach ($this->routes as $route) {
            if (isset($route['options']['default']) && $route['options']['default']) {
                return $route;
            }
        }
        return $this->routes[0] ?? null;
    }

    public function getRoutes(): array
    {
        return $this->routes;
    }

    public function addMiddleware(callable $middleware): void
    {
        $this->middleware[] = $middleware;
    }

    public function applyMiddleware(array $context): array
    {
        foreach ($this->middleware as $middleware) {
            $context = $middleware($context);
            if (!($context['proceed'] ?? true)) {
                return $context;
            }
        }
        return $context;
    }
}
