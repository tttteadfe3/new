# BaseController Usage Guide

## Overview

The `BaseController` provides common functionality for all controllers in the MVC structure. All controllers should extend from either `BaseController` (for web controllers) or `BaseApiController` (for API controllers).

## Key Features

### 1. Authentication and Authorization

```php
class MyController extends BaseController
{
    public function index()
    {
        // Require authentication only
        $this->requireAuth();
        
        // Require specific permission
        $this->requireAuth('admin_access');
        
        // Check if user is authenticated
        if ($this->isAuthenticated()) {
            // User is logged in
        }
        
        // Get current user
        $user = $this->user();
    }
}
```

### 2. View Rendering

```php
public function show()
{
    $data = ['title' => 'Page Title', 'content' => 'Page content'];
    return $this->render('pages/show', $data);
}
```

### 3. JSON Responses

```php
public function apiEndpoint()
{
    // Success response
    $this->json([
        'data' => $someData,
        'message' => 'Success'
    ]);
    
    // Error response
    $this->json([
        'message' => 'Error occurred',
        'errors' => ['field' => 'Error message']
    ], 400);
}
```

### 4. Redirects

```php
public function store()
{
    // Process data...
    $this->redirect('/success-page');
}
```

### 5. Request Input Handling

```php
public function process()
{
    // Get all input
    $allData = $this->request->all();
    
    // Get specific input
    $name = $this->request->input('name', 'default');
    
    // Get multiple inputs
    $data = $this->request->only(['name', 'email']);
    
    // Validate input
    $errors = $this->request->validate([
        'name' => 'required|min:2',
        'email' => 'required|email'
    ]);
    
    if (!empty($errors)) {
        // Handle validation errors
    }
}
```

## API Controllers

For API endpoints, extend `BaseApiController` instead:

```php
class MyApiController extends BaseApiController
{
    public function index()
    {
        $this->requireAuth('api_access');
        
        $data = $this->someService->getData();
        $this->success($data, 'Data retrieved successfully');
    }
    
    public function store()
    {
        $errors = $this->request->validate([
            'name' => 'required'
        ]);
        
        if (!empty($errors)) {
            $this->validationError($errors);
            return;
        }
        
        // Process and save data...
        $this->success($result, 'Data saved successfully', 201);
    }
}
```

## Migration from Legacy Controllers

When converting legacy controllers:

1. Extend from `BaseController`
2. Replace direct `AuthManager` calls with `$this->requireAuth()`
3. Replace `View::render()` with `$this->render()`
4. Replace `header('Location: ...')` with `$this->redirect()`
5. Use `$this->request->input()` instead of `$_GET`/`$_POST`