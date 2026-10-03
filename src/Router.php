<?php

declare(strict_types=1);

namespace App;

// Maps "METHOD /path" to a handler.
//
//   $router->add('GET', '/capsules/{id}', [$controller, 'show']);
//
// "{id}" matches a number and is passed to the handler as an int argument.
class Router
{
    /** @var array<int, array{method: string, regex: string, handler: callable}> */
    private array $routes = [];

    public function add(string $method, string $pattern, callable $handler): void
    {
        // "/capsules/{id}/file" -> "#^/capsules/(\d+)/file$#"
        $regex = '#^' . str_replace('{id}', '(\d+)', $pattern) . '$#';

        $this->routes[] = ['method' => $method, 'regex' => $regex, 'handler' => $handler];
    }

    // Find the route for a request.
    // Returns [handler, arguments], or null when no route matches (-> 404).
    public function match(string $method, string $path): ?array
    {
        foreach ($this->routes as $route) {
            if ($route['method'] === $method && preg_match($route['regex'], $path, $matches)) {
                $arguments = array_map('intval', array_slice($matches, 1));
                return [$route['handler'], $arguments];
            }
        }

        return null;
    }
}
