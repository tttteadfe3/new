<?php

namespace App\Core;

class Router
{
    protected array $webRoutes = [];
    protected array $apiRoutes = [];

    public static function load(string $webFile, string $apiFile = null): self
    {
        $router = new static;
        
        if (file_exists($webFile)) {
            $router->webRoutes = require $webFile;
        }
        
        if ($apiFile && file_exists($apiFile)) {
            $router->apiRoutes = require $apiFile;
        }
        
        return $router;
    }

    public function direct(string $uri, string $method)
    {
        // Remove query string from URI
        if (false !== $pos = strpos($uri, '?')) {
            $uri = substr($uri, 0, $pos);
        }
        $uri = trim($uri, '/');

        // Check API routes first
        if (str_starts_with($uri, 'api/')) {
            $apiUri = substr($uri, 4);
            return $this->findAndCallRoute($this->apiRoutes, $apiUri, $method, 'Api');
        }

        // Check web routes
        return $this->findAndCallRoute($this->webRoutes, $uri, $method, '');
    }

    protected function findAndCallRoute(array $routes, string $uri, string $method, string $namespacePrefix)
    {
        foreach ($routes as $routeDefinition => $action) {
            // Split "METHOD /path"
            $parts = explode(' ', $routeDefinition, 2);
            if (count($parts) !== 2) {
                continue;
            }
            [$routeMethod, $routePath] = $parts;

            if ($routeMethod !== $method) {
                continue;
            }

            // Convert route path to regex: /users/{id} -> #^/users/([^/]+)$#
            $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '([^/]+)', $routePath);
            $regex = "#^" . $pattern . "$#";

            $matches = [];
            if (preg_match($regex, $uri, $matches)) {
                // Remove the full match from the beginning of the array
                array_shift($matches);
                $params = $matches;

                return $this->callAction($action, $params, $namespacePrefix);
            }
        }

        throw new \Exception("No route defined for this URI: {$uri} with method {$method}");
    }

    protected function callAction(string $action, array $params, string $namespacePrefix)
    {
        [$controller, $method] = explode('@', $action);

        $controllerNamespace = "App\\Controllers\\" . ($namespacePrefix ? "{$namespacePrefix}\\" : "");
        $controller = $controllerNamespace . $controller;

        if (!class_exists($controller)) {
            throw new \Exception("Controller class {$controller} not found.");
        }

        $controllerInstance = new $controller;

        if (!method_exists($controllerInstance, $method)) {
            throw new \Exception(
                "{$controller} does not respond to the {$method} action."
            );
        }

        return $controllerInstance->$method(...$params);
    }
}