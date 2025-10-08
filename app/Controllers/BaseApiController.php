<?php

namespace App\Controllers;

use App\Core\Request;
use App\Services\AuthService;

/**
 * Base API Controller for handling API requests.
 */
abstract class BaseApiController
{
    protected Request $request;
    protected AuthService $authService;

    public function __construct()
    {
        $this->request = new Request();
        $this->authService = new AuthService();
        
        // Set JSON content type for all API responses
        header('Content-Type: application/json');
    }

    /**
     * Return a JSON response.
     *
     * @param array $data The data to return
     * @param int $status HTTP status code
     */
    protected function json(array $data, int $status = 200): void
    {
        http_response_code($status);

        // Ensure consistent JSON response format
        $response = [
            'success' => $status >= 200 && $status < 300,
            'data' => $data['data'] ?? null,
            'message' => $data['message'] ?? '',
            'errors' => $data['errors'] ?? []
        ];

        // If data is passed directly without the standard format, use it as data
        if (!isset($data['success']) && !isset($data['message']) && !isset($data['errors'])) {
            $response['data'] = $data;
        } else {
            // Merge with provided structure
            $response = array_merge($response, $data);
        }

        echo json_encode($response);
    }

    /**
     * Get the current authenticated user.
     *
     * @return array|null The user data or null if not authenticated
     */
    protected function user(): ?array
    {
        return $this->authService->user();
    }

    /**
     * Check if the current user is authenticated.
     *
     * @return bool True if authenticated, false otherwise
     */
    protected function isAuthenticated(): bool
    {
        return $this->authService->isLoggedIn();
    }

    /**
     * Handle API authentication with JSON error response.
     * 
     * @param string|null $permission The permission to check for
     */
    protected function requireAuth(string $permission = null): void
    {
        if (!$this->isAuthenticated()) {
            $this->json([
                'success' => false,
                'message' => 'Authentication required.',
                'errors' => ['auth' => 'You must be logged in to access this resource.']
            ], 401);
            exit;
        }

        if ($permission !== null) {
            // Use the new centralized permission checker from the AuthService instance.
            if (!$this->authService->check($permission)) {
                $this->json([
                    'success' => false,
                    'message' => 'Access denied. Insufficient permissions.',
                    'errors' => ['permission' => 'You do not have permission to access this resource.']
                ], 403);
                exit;
            }
        }
    }

    /**
     * Return a success JSON response.
     * 
     * @param mixed $data The data to return
     * @param string $message Success message
     * @param int $status HTTP status code
     */
    protected function success(mixed $data = null, string $message = 'Success', int $status = 200): void
    {
        $this->json([
            'success' => true,
            'data' => $data,
            'message' => $message,
            'errors' => []
        ], $status);
    }

    /**
     * Return an error JSON response.
     * 
     * @param string $message Error message
     * @param array $errors Detailed errors
     * @param int $status HTTP status code
     */
    protected function error(string $message = 'An error occurred', array $errors = [], int $status = 400): void
    {
        $this->json([
            'success' => false,
            'data' => null,
            'message' => $message,
            'errors' => $errors
        ], $status);
    }

    /**
     * Return a validation error response.
     * 
     * @param array $errors Validation errors
     * @param string $message Error message
     */
    protected function validationError(array $errors, string $message = 'Validation failed'): void
    {
        $this->error($message, $errors, 422);
    }

    /**
     * Return a not found error response.
     * 
     * @param string $message Error message
     */
    protected function notFound(string $message = 'Resource not found'): void
    {
        $this->error($message, [], 404);
    }
}