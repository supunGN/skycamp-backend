<?php

/**
 * Professional Router Class for SkyCamp Backend
 * Implements RESTful API routing as per Lecture 6 - Web Services
 * Handles HTTP methods: GET, POST, PUT, DELETE
 */

class Router
{
    private $routes = [];
    private $middleware = [];

    public function __construct()
    {
        // Enable CORS for frontend integration
        $this->enableCORS();
    }

    /**
     * Register GET route
     * @param string $path Route pattern
     * @param callable $handler Route handler
     */
    public function get($path, $handler)
    {
        $this->addRoute('GET', $path, $handler);
    }

    /**
     * Register POST route
     * @param string $path Route pattern
     * @param callable $handler Route handler
     */
    public function post($path, $handler)
    {
        $this->addRoute('POST', $path, $handler);
    }

    /**
     * Register PUT route
     * @param string $path Route pattern
     * @param callable $handler Route handler
     */
    public function put($path, $handler)
    {
        $this->addRoute('PUT', $path, $handler);
    }

    /**
     * Register DELETE route
     * @param string $path Route pattern
     * @param callable $handler Route handler
     */
    public function delete($path, $handler)
    {
        $this->addRoute('DELETE', $path, $handler);
    }

    /**
     * Add middleware for authentication/validation
     * @param callable $middleware Middleware function
     */
    public function addMiddleware($middleware)
    {
        $this->middleware[] = $middleware;
    }

    /**
     * Process the incoming request
     */
    public function dispatch()
    {
        try {
            $method = $_SERVER['REQUEST_METHOD'];
            $uri = $this->getCleanUri();

            // Apply middleware
            foreach ($this->middleware as $middleware) {
                $result = call_user_func($middleware);
                if ($result === false) {
                    $this->sendErrorResponse(401, 'Unauthorized');
                    return;
                }
            }

            // Find matching route
            $handler = $this->findRoute($method, $uri);

            if ($handler) {
                $this->executeHandler($handler);
            } else {
                $this->sendErrorResponse(404, 'Endpoint not found');
            }
        } catch (Exception $e) {
            error_log("Router error: " . $e->getMessage());
            $this->sendErrorResponse(500, 'Internal server error');
        }
    }

    /**
     * Add route to routes array
     */
    private function addRoute($method, $path, $handler)
    {
        $this->routes[] = [
            'method' => $method,
            'path' => $this->compilePath($path),
            'handler' => $handler,
            'original_path' => $path
        ];
    }

    /**
     * Compile path pattern to regex
     */
    private function compilePath($path)
    {
        // Convert path parameters like {id} to regex
        $pattern = preg_replace('/\{(\w+)\}/', '(?P<$1>[^/]+)', $path);
        return '#^' . $pattern . '$#';
    }

    /**
     * Get clean URI without query string
     */
    private function getCleanUri()
    {
        $uri = $_SERVER['REQUEST_URI'];
        $uri = parse_url($uri, PHP_URL_PATH);
        return rtrim($uri, '/') ?: '/';
    }

    /**
     * Find matching route
     */
    private function findRoute($method, $uri)
    {
        foreach ($this->routes as $route) {
            if ($route['method'] === $method && preg_match($route['path'], $uri, $matches)) {
                return [
                    'handler' => $route['handler'],
                    'params' => $this->extractParams($matches)
                ];
            }
        }
        return null;
    }

    /**
     * Extract parameters from route matches
     */
    private function extractParams($matches)
    {
        $params = [];
        foreach ($matches as $key => $value) {
            if (is_string($key)) {
                $params[$key] = $value;
            }
        }
        return $params;
    }

    /**
     * Execute the route handler
     */
    private function executeHandler($handler)
    {
        $callable = $handler['handler'];
        $params = $handler['params'];

        if (is_string($callable) && strpos($callable, '@') !== false) {
            // Controller@method format
            list($controller, $method) = explode('@', $callable);
            $controllerPath = __DIR__ . "/../controllers/{$controller}.php";

            if (file_exists($controllerPath)) {
                require_once $controllerPath;
                $instance = new $controller();

                // Call method with proper parameters based on HTTP method
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    $postData = json_decode(file_get_contents('php://input'), true) ?: $_POST;
                    $files = $_FILES ?: [];

                    if ($method === 'register') {
                        $result = $instance->$method($postData, $files);
                    } elseif ($method === 'login') {
                        $email = $postData['email'] ?? '';
                        $password = $postData['password'] ?? '';
                        $result = $instance->$method($email, $password);
                    } elseif ($method === 'requestReset') {
                        $email = $postData['email'] ?? '';
                        $result = $instance->$method($email);
                    } elseif ($method === 'verifyOTP') {
                        $token = $postData['token'] ?? '';
                        $otp = $postData['otp'] ?? '';
                        $result = $instance->$method($token, $otp);
                    } elseif ($method === 'resetPassword') {
                        $token = $postData['token'] ?? '';
                        $password = $postData['password'] ?? '';
                        $result = $instance->$method($token, $password);
                    } else {
                        $result = $instance->$method($postData);
                    }
                } else {
                    $result = $instance->$method($params);
                }

                // Send JSON response
                if (isset($result) && is_array($result)) {
                    self::sendJsonResponse($result);
                }
            } else {
                throw new Exception("Controller {$controller} not found");
            }
        } elseif (is_callable($callable)) {
            // Direct callable
            call_user_func($callable, $params);
        } else {
            throw new Exception("Invalid route handler");
        }
    }

    /**
     * Enable CORS for frontend integration
     */
    private function enableCORS()
    {
        // Allow specific origin or all origins for development
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '*';
        header("Access-Control-Allow-Origin: {$origin}");
        header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type, Authorization");
        header("Access-Control-Allow-Credentials: true");

        // Handle preflight requests
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit();
        }
    }

    /**
     * Send error response
     */
    private function sendErrorResponse($code, $message)
    {
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => $message,
            'error_code' => $code
        ]);
    }

    /**
     * Send JSON response
     */
    public static function sendJsonResponse($data, $status_code = 200)
    {
        http_response_code($status_code);
        header('Content-Type: application/json');
        echo json_encode($data);
    }
}
