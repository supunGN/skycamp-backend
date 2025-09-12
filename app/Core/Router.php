<?php

/**
 * Simple Router Class
 * Handles GET/POST/OPTIONS routing
 */

class Router
{
    private array $routes = [];
    private array $middlewares = [];

    /**
     * Add GET route
     */
    public function get(string $path, callable|array $handler): void
    {
        $this->addRoute('GET', $path, $handler);
    }

    /**
     * Add POST route
     */
    public function post(string $path, callable|array $handler): void
    {
        $this->addRoute('POST', $path, $handler);
    }

    /**
     * Add OPTIONS route
     */
    public function options(string $path, callable|array $handler): void
    {
        $this->addRoute('OPTIONS', $path, $handler);
    }

    /**
     * Add route to collection
     */
    private function addRoute(string $method, string $path, callable|array $handler): void
    {
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'handler' => $handler
        ];
    }

    /**
     * Dispatch request to appropriate handler
     */
    public function dispatch(Request $request, Response $response): void
    {
        $method = $request->getMethod();
        $path = $request->getPath();

        // Handle OPTIONS requests for CORS
        if ($method === 'OPTIONS') {
            $response->setStatusCode(200);
            $response->send();
            return;
        }

        // Find matching route
        foreach ($this->routes as $route) {
            if ($route['method'] === $method && $this->matchPath($route['path'], $path)) {
                $this->executeHandler($route['handler'], $request, $response);
                return;
            }
        }

        // No route found
        $response->setStatusCode(404);
        $response->json([
            'success' => false,
            'message' => 'Route not found'
        ]);
    }

    /**
     * Check if route path matches request path
     */
    private function matchPath(string $routePath, string $requestPath): bool
    {
        // Handle wildcard routes
        if (str_ends_with($routePath, '/*')) {
            $prefix = substr($routePath, 0, -2);
            return str_starts_with($requestPath, $prefix);
        }

        // Simple exact match
        return $routePath === $requestPath;
    }

    /**
     * Execute route handler
     */
    private function executeHandler(callable|array $handler, Request $request, Response $response): void
    {
        if (is_array($handler)) {
            [$controllerClass, $method] = $handler;

            if (!class_exists($controllerClass)) {
                throw new Exception("Controller class {$controllerClass} not found");
            }

            $controller = new $controllerClass();

            if (!method_exists($controller, $method)) {
                throw new Exception("Method {$method} not found in {$controllerClass}");
            }

            $controller->$method($request, $response);
        } else {
            $handler($request, $response);
        }
    }
}
