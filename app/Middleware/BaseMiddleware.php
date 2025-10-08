<?php

namespace App\Middleware;

abstract class BaseMiddleware
{
    /**
     * Handle the middleware logic.
     * 
     * @param mixed $parameter Optional parameter for the middleware
     */
    abstract public function handle($parameter = null): void;

    /**
     * Check if the current request is an API request.
     */
    protected function isApiRequest(): bool
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        return str_starts_with(trim($uri, '/'), 'api/');
    }

    /**
     * Send JSON response for API requests.
     */
    protected function jsonResponse(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
    }

    /**
     * Redirect to a URL (for web requests).
     */
    protected function redirect(string $url): void
    {
        header("Location: {$url}");
        exit;
    }

    /**
     * Send HTML error response (for web requests).
     */
    protected function htmlError(int $status, string $title, string $message): void
    {
        http_response_code($status);
        echo "<h1>{$status} {$title}</h1>";
        echo "<p>{$message}</p>";
        exit;
    }
}