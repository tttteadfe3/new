<?php

namespace App\Controllers;

/**
 * Base API Controller for handling API requests.
 * Extends BaseController with API-specific functionality.
 */
abstract class BaseApiController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        
        // Set JSON content type for all API responses
        header('Content-Type: application/json');
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
            $user = $this->user();
            $userRole = $user['role'] ?? 'guest';
            
            if (!\App\Models\Permission::hasPermission($userRole, $permission)) {
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