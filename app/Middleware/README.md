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
- **Usage**: `'permission:resource.action'`
- **Parameters**: Requires a permission key (e.g., `employee.view`, `user.update`)
- **Behavior**:
  - Returns 403 error for web/API requests if user lacks permission
  - Assumes user is already authenticated (should be used after AuthMiddleware)

## Usage in Routes

### Fluent Usage (Recommended)
```php
// Route with authentication only
$router->get('/dashboard', [DashboardController::class, 'index'])->middleware('auth');

// Route with authentication and permission
$router->get('/employees', [EmployeeController::class, 'index'])
       ->middleware('auth')
       ->middleware('permission', 'employee.view');

$router->post('/employees', [EmployeeController::class, 'store'])
       ->middleware('auth')
       ->middleware('permission', 'employee.create');
```

## Creating Custom Middleware

1. Create a new class implementing the `handle` method.
2. Register it in `public/index.php` using `$router->addMiddleware('key', YourClass::class)`.
3. Use it in your routes via `->middleware('key')`.

```php
<?php

namespace App\Middleware;

class CustomMiddleware
{
    public function handle($parameter = null): void
    {
        // Your middleware logic here
        if ($someCondition) {
            // Handle error, e.g., by throwing an exception or redirecting
        }
    }
}
```

## Deprecation of `requireAuth()`

The old `requireAuth()` method in BaseController is now deprecated. Middleware defined fluently on routes is the standard.

## Error Handling

- **Web requests**: Redirects to login or shows HTML error pages.
- **API requests**: Returns JSON responses with appropriate HTTP status codes.
- **Authentication errors**: 401 Unauthorized
- **Permission errors**: 403 Forbidden
