<?php
namespace App\Core;

class Router {
    private array $routes = [];

    public function get(string $path, callable|array $handler): void {
        $this->add('GET', $path, $handler);
    }
    public function post(string $path, callable|array $handler): void {
        $this->add('POST', $path, $handler);
    }
    public function put(string $path, callable|array $handler): void {
        $this->add('PUT', $path, $handler);
    }
    public function delete(string $path, callable|array $handler): void {
        $this->add('DELETE', $path, $handler);
    }

    private function add(string $method, string $path, callable|array $handler): void {
        // Normalize path: /api/products/{id} -> regex
        $pattern = preg_replace('#\{(\w+)\}#', '(?P<$1>[^/]+)', $path);
        $pattern = "#^$pattern$#";
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'pattern' => $pattern,
            'handler' => $handler
        ];
    }

    public function dispatch(): void {
        $requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        
        // Remove base path if running from public folder
        // Handle /backend/public prefix if needed
        $requestUri = str_replace('/backend/public', '', $requestUri);
        if ($requestUri === '') $requestUri = '/';

        // Handle preflight
        if ($requestMethod === 'OPTIONS') {
            http_response_code(200);
            exit;
        }

        foreach ($this->routes as $route) {
            if ($route['method'] !== $requestMethod) continue;
            
            if (preg_match($route['pattern'], $requestUri, $matches)) {
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                
                $handler = $route['handler'];
                if (is_array($handler)) {
                    [$class, $method] = $handler;
                    $instance = new $class();
                    call_user_func_array([$instance, $method], [$params]);
                } else {
                    call_user_func_array($handler, [$params]);
                }
                return;
            }
        }

        Response::error("Route not found: $requestMethod $requestUri", 404);
    }
}
