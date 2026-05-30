<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RoutingMiddleware
{
    protected array $routeTags = [];

    public function handle(Request $request, Closure $next)
    {
        $route = $request->route();
        $routeName = $route?->getName() ?? 'unknown';
        $controller = $route?->getActionName() ?? 'unknown';

        $request->attributes->set('routing_metadata', [
            'route_name' => $routeName,
            'controller' => $controller,
            'method' => $request->getMethod(),
            'path' => $request->path(),
            'timestamp' => now()->toISOString(),
        ]);

        $response = $next($request);

        $response->headers->set('X-Route-Name', $routeName);
        $response->headers->set('X-Controller', $controller);

        return $response;
    }

    public function addRouteTag(string $pattern, array $tags): void
    {
        $this->routeTags[$pattern] = $tags;
    }

    public function getTagsForRequest(Request $request): array
    {
        $path = $request->path();
        $tags = [];

        foreach ($this->routeTags as $pattern => $patternTags) {
            if (str_contains($path, $pattern)) {
                $tags = array_merge($tags, $patternTags);
            }
        }

        return array_unique($tags);
    }
}
