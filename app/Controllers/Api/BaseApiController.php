<?php

namespace App\Controllers\Api;

use App\Controllers\Web\BaseController;
use App\Core\JsonResponse;
use App\Services\AuthService;

abstract class BaseApiController extends BaseController
{
    protected AuthService $authService;

    public function __construct()
    {
        parent::__construct();
        $this->authService = new AuthService();
        
        // Ensure all API requests are AJAX requests
        if (!$this->isAjaxRequest()) {
            $this->apiError('API endpoints only accept AJAX requests', 'INVALID_REQUEST', 400);
        }
        
        // Set JSON content type for all API responses
        header('Content-Type: application/json; charset=utf-8');
    }

    /**
     * Check if the request is an AJAX request
     */
    protected function isAjaxRequest(): bool
    {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    /**
     * Require authentication for API endpoints
     * Override parent method to use API-specific error responses
     */
    protected function requireAuth(string $permission = null): void
    {
        if (!$this->authService->isLoggedIn()) {
            $this->apiError('Authentication required', 'UNAUTHORIZED', 401);
        }

        if ($permission !== null) {
            if (!$this->authService->check($permission)) {
                $this->apiError('Access denied. Insufficient permissions.', 'FORBIDDEN', 403);
            }
        }
    }

    /**
     * Send a successful API response
     */
    protected function apiSuccess($data = null, string $message = 'Success'): void
    {
        JsonResponse::success($data, $message);
    }

    /**
     * Send an error API response
     */
    protected function apiError(string $message, string $errorCode = null, int $httpStatus = 400): void
    {
        JsonResponse::error($message, $errorCode, $httpStatus);
    }

    /**
     * Send a not found API response
     */
    protected function apiNotFound(string $message = 'Resource not found'): void
    {
        JsonResponse::notFound($message);
    }

    /**
     * Send a forbidden API response
     */
    protected function apiForbidden(string $message = 'Access forbidden'): void
    {
        JsonResponse::forbidden($message);
    }

    /**
     * Send a bad request API response
     */
    protected function apiBadRequest(string $message = 'Bad request'): void
    {
        JsonResponse::badRequest($message);
    }

    /**
     * Get JSON input data from request body
     */
    protected function getJsonInput(): array
    {
        $input = json_decode(file_get_contents('php://input'), true);
        return $input ?? [];
    }

    /**
     * Get action parameter from request
     */
    protected function getAction(): string
    {
        return $_GET['action'] ?? $_POST['action'] ?? '';
    }

    /**
     * Validate required fields in input data
     */
    protected function validateRequired(array $data, array $requiredFields): bool
    {
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                $this->apiBadRequest("Required field '{$field}' is missing or empty");
                return false;
            }
        }
        return true;
    }

    /**
     * Get current user's employee ID
     */
    protected function getCurrentEmployeeId(): ?int
    {
        $user = $this->authService->user();
        if (!$user) {
            return null;
        }

        // Try to get employee_id from user data
        if (isset($user['employee_id'])) {
            return (int)$user['employee_id'];
        }

        // If not available, try to find through EmployeeRepository
        try {
            $employee = \App\Repositories\EmployeeRepository::findByUserId($user['id']);
            return $employee ? (int)$employee['id'] : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Handle common API exceptions
     */
    protected function handleException(\Exception $e): void
    {
        $code = is_int($e->getCode()) && $e->getCode() >= 400 ? $e->getCode() : 500;
        $this->apiError($e->getMessage(), 'SERVER_ERROR', $code);
    }
}