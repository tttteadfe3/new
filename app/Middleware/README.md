# Middleware System

This directory contains the middleware classes for the MVC application. Middleware provides a convenient mechanism for filtering HTTP requests entering your application.

## Available Middleware

### AuthMiddleware
- **Purpose**: Ensures the user is authenticated
- **Usage**: `'auth'`
- **Behavior**: 
  - Redirects to `/login` for web requests if not authenticated
  - Returns 401 JSON response for API requests if not authenticated

### PermissionMiddleware
- **Purpose**: Ensures the user has a specific permission
- **Usage**: `'permission:permission_name'`
- **Parameters**: Requires a permission name (e.g., `employee_admin`, `leave_view`)
- **Behavior**:
  - Returns 403 error for web/API requests if user lacks permission
  - Assumes user is already authenticated (should be used after AuthMiddleware)

## Usage in Routes

### Basic Usage
```php
// Route with authentication only
'dashboard' => [
    'action' => 'DashboardController@index',
    'middleware' => ['auth']
],

// Route with authentication and permission
'employees' => [
    'action' => 'EmployeeController@index',
    'middleware' => ['auth', 'permission:employee_admin']
],
```

### Route Groups (Future Enhancement)
```php
// Group routes with common middleware
$router->group(['middleware' => ['auth']], function($router) {
    $router->web('dashboard', 'DashboardController@index');
    $router->web('profile', 'ProfileController@index');
});

$router->group(['middleware' => ['auth', 'permission:admin']], function($router) {
    $router->web('admin/users', 'AdminController@users');
    $router->web('admin/roles', 'AdminController@roles');
});
```

## Creating Custom Middleware

1. Create a new class extending `BaseMiddleware`
2. Implement the `handle($parameter = null)` method
3. Use the provided helper methods for responses

```php
<?php

namespace App\Middleware;

class CustomMiddleware extends BaseMiddleware
{
    public function handle($parameter = null): void
    {
        // Your middleware logic here
        
        if ($someCondition) {
            if ($this->isApiRequest()) {
                $this->jsonResponse(['error' => 'Custom error'], 400);
            } else {
                $this->htmlError(400, 'Bad Request', 'Custom error message');
            }
        }
    }
}
```

## Migration from BaseController::requireAuth()

The old `requireAuth()` method in BaseController is now deprecated. Instead of:

```php
// Old way (deprecated)
public function index()
{
    $this->requireAuth('employee_admin');
    // ... controller logic
}
```

Use middleware in routes:

```php
// New way (recommended)
'employees' => [
    'action' => 'EmployeeController@index',
    'middleware' => ['auth', 'permission:employee_admin']
],
```

## Error Handling

- **Web requests**: Redirects to login or shows HTML error pages
- **API requests**: Returns JSON responses with appropriate HTTP status codes
- **Authentication errors**: 401 Unauthorized
- **Permission errors**: 403 Forbidden

## Response Formats

### API Error Responses
```json
{
    "success": false,
    "message": "Error message",
    "errors": {
        "field": "Specific error details"
    }
}
```

### Web Error Responses
- Redirects to `/login` for authentication errors
- HTML error pages for permission errors