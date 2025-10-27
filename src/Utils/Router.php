<?php

namespace App\Utils;

class Router
{
    private array $routes = [];
    private array $middlewares = [];
    
    public function addMiddleware(callable $middleware): void
    {
        $this->middlewares[] = $middleware;
    }
    
    public function get(string $path, $handler, array $middlewares = []): void
    {
        $this->addRoute('GET', $path, $handler, $middlewares);
    }
    
    public function post(string $path, $handler, array $middlewares = []): void
    {
        $this->addRoute('POST', $path, $handler, $middlewares);
    }
    
    public function put(string $path, $handler, array $middlewares = []): void
    {
        $this->addRoute('PUT', $path, $handler, $middlewares);
    }
    
    public function delete(string $path, $handler, array $middlewares = []): void
    {
        $this->addRoute('DELETE', $path, $handler, $middlewares);
    }
    
    private function addRoute(string $method, string $path, $handler, array $middlewares): void
    {
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'handler' => $handler,
            'middlewares' => $middlewares,
        ];
    }
    
    public function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        
        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }
            
            $pattern = $this->convertPathToRegex($route['path']);
            if (preg_match($pattern, $uri, $matches)) {
                array_shift($matches); // Remove full match
                
                // Filtrar apenas argumentos numéricos (remover named captures)
                $params = array_filter($matches, 'is_int', ARRAY_FILTER_USE_KEY);
                
                // Execute global middlewares
                foreach ($this->middlewares as $middleware) {
                    $result = $middleware();
                    if ($result === false) {
                        return;
                    }
                }
                
                // Execute route-specific middlewares
                foreach ($route['middlewares'] as $middleware) {
                    $result = $middleware();
                    if ($result === false) {
                        return;
                    }
                }
                
                // Execute handler
                call_user_func_array($route['handler'], $params);
                return;
            }
        }
        
        Response::notFound('Endpoint not found');
    }
    
    private function convertPathToRegex(string $path): string
    {
        // Convert {param} to named capture groups
        $pattern = preg_replace('/\{(\w+)\}/', '(?P<$1>[^/]+)', $path);
        return '#^' . $pattern . '$#';
    }
    
    public static function getJsonInput(): ?array
    {
        $input = file_get_contents('php://input');
        if (empty($input)) {
            return null;
        }
        
        $data = json_decode($input, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            Response::error('Invalid JSON in request body', 400);
        }
        
        return $data;
    }
    
    public static function getQueryParams(): array
    {
        return $_GET;
    }
}

