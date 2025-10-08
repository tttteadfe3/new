<?php

namespace App\Core;

class Router
{
    protected array $webRoutes = [];
    protected array $apiRoutes = [];
    protected array $currentGroup = [];
    protected array $middleware = [];

    public static function load(string $webFile, string $apiFile = null): self
    {
        $router = new static;
        
        // Load web routes
        $router->webRoutes = require $webFile;
        
        // Load API routes if provided
        if ($apiFile && file_exists($apiFile)) {
            $router->apiRoutes = require $apiFile;
        }
        
        return $router;
    }

    public function web(string $uri, string $action): self
    {
        $this->webRoutes[$uri] = [
            'action' => $action,
            'middleware' => $this->currentGroup['middleware'] ?? []
        ];
        return $this;
    }

    public function api(string $uri, string $action): self
    {
        $this->apiRoutes[$uri] = [
            'action' => $action,
            'middleware' => $this->currentGroup['middleware'] ?? []
        ];
        return $this;
    }

    public function group(array $attributes, callable $callback): self
    {
        $previousGroup = $this->currentGroup;
        $this->currentGroup = array_merge($this->currentGroup, $attributes);
        
        $callback($this);
        
        $this->currentGroup = $previousGroup;
        return $this;
    }

    public function middleware(string $middleware): self
    {
        $this->currentGroup['middleware'][] = $middleware;
        return $this;
    }

    public function direct(string $uri)
    {
        // Remove query string from URI
        if (false !== $pos = strpos($uri, '?')) {
            $uri = substr($uri, 0, $pos);
        }
        $uri = trim($uri, '/');

        // Check API routes first
        if (str_starts_with($uri, 'api/')) {
            return $this->handleApiRoute($uri);
        }

        // Check web routes
        return $this->handleWebRoute($uri);
    }

    protected function handleWebRoute(string $uri)
    {
        if (array_key_exists($uri, $this->webRoutes)) {
            $route = $this->webRoutes[$uri];
            
            // Handle legacy format (string) or new format (array)
            if (is_string($route)) {
                return $this->callAction(...explode('@', $route));
            }
            
            // Apply middleware if defined
            if (!empty($route['middleware'])) {
                $this->applyMiddleware($route['middleware']);
            }
            
            return $this->callAction(...explode('@', $route['action']));
        }

        throw new \Exception("No web route defined for this URI: {$uri}");
    }

    protected function handleApiRoute(string $uri)
    {
        // Remove 'api/' prefix for matching
        $apiUri = substr($uri, 4);
        
        if (array_key_exists($apiUri, $this->apiRoutes)) {
            $route = $this->apiRoutes[$apiUri];
            
            // Handle legacy format (string) or new format (array)
            if (is_string($route)) {
                return $this->callApiAction(...explode('@', $route));
            }
            
            // Apply middleware if defined
            if (!empty($route['middleware'])) {
                $this->applyMiddleware($route['middleware']);
            }
            
            return $this->callApiAction(...explode('@', $route['action']));
        }

        throw new \Exception("No API route defined for this URI: {$uri}");
    }

    protected function callAction(string $controller, string $action)
    {
        $controller = "App\\Controllers\\{$controller}";
        $controllerInstance = new $controller;

        if (!method_exists($controllerInstance, $action)) {
            throw new \Exception(
                "{$controller} does not respond to the {$action} action."
            );
        }

        return $controllerInstance->$action();
    }

    protected function callApiAction(string $controller, string $action)
    {
        $controller = "App\\Controllers\\Api\\{$controller}";
        $controllerInstance = new $controller;

        if (!method_exists($controllerInstance, $action)) {
            throw new \Exception(
                "{$controller} does not respond to the {$action} action."
            );
        }

        return $controllerInstance->$action();
    }

    protected function applyMiddleware(array $middleware): void
    {
        foreach ($middleware as $middlewareDefinition) {
            // Handle middleware with parameters: "auth:permission_name" or just "auth"
            if (is_string($middlewareDefinition)) {
                $parts = explode(':', $middlewareDefinition, 2);
                $middlewareName = $parts[0];
                $parameter = $parts[1] ?? null;
            } else {
                $middlewareName = $middlewareDefinition;
                $parameter = null;
            }
            
            $middlewareClass = "App\\Middleware\\{$middlewareName}Middleware";
            
            if (class_exists($middlewareClass)) {
                $middlewareInstance = new $middlewareClass;
                
                // Handle different middleware types
                if ($parameter && method_exists($middlewareInstance, 'handle')) {
                    $middlewareInstance->handle($parameter);
                } else {
                    $middlewareInstance->handle();
                }
            }
        }
    }
}