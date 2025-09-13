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
     * Add PUT route
     */
    public function put(string $path, callable|array $handler): void
    {
        $this->addRoute('PUT', $path, $handler);
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
                // Extract URL parameters
                $this->extractUrlParameters($route['path'], $path, $request);
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

        // Handle parameterized routes (e.g., /api/locations/:id)
        if (strpos($routePath, ':') !== false) {
            $routeParts = explode('/', trim($routePath, '/'));
            $requestParts = explode('/', trim($requestPath, '/'));

            if (count($routeParts) !== count($requestParts)) {
                return false;
            }

            for ($i = 0; $i < count($routeParts); $i++) {
                if (strpos($routeParts[$i], ':') === 0) {
                    // This is a parameter, skip validation
                    continue;
                }
                if ($routeParts[$i] !== $requestParts[$i]) {
                    return false;
                }
            }
            return true;
        }

        // Simple exact match
        return $routePath === $requestPath;
    }

    /**
     * Extract URL parameters from route and request path
     */
    private function extractUrlParameters(string $routePath, string $requestPath, Request $request): void
    {
        if (strpos($routePath, ':') === false) {
            return; // No parameters to extract
        }

        $routeParts = explode('/', trim($routePath, '/'));
        $requestParts = explode('/', trim($requestPath, '/'));

        for ($i = 0; $i < count($routeParts); $i++) {
            if (strpos($routeParts[$i], ':') === 0) {
                $paramName = substr($routeParts[$i], 1); // Remove the ':'
                $paramValue = $requestParts[$i] ?? null;
                $request->setParam($paramName, $paramValue);
            }
        }
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
