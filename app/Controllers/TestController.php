<?php

namespace App\Controllers;

/**
 * Test Controller to demonstrate BaseController functionality.
 * This can be removed after testing is complete.
 */
class TestController extends BaseController
{
    /**
     * Test basic authentication requirement.
     */
    public function testAuth()
    {
        $this->requireAuth();
        
        return $this->render('test/auth', [
            'user' => $this->user(),
            'message' => 'Authentication successful!'
        ]);
    }

    /**
     * Test permission-based authentication.
     */
    public function testPermission()
    {
        $this->requireAuth('admin_access');
        
        return $this->render('test/permission', [
            'user' => $this->user(),
            'message' => 'Permission check successful!'
        ]);
    }

    /**
     * Test JSON response.
     */
    public function testJson()
    {
        $this->requireAuth();
        
        $data = [
            'user' => $this->user(),
            'timestamp' => date('Y-m-d H:i:s'),
            'request_data' => $this->request->all()
        ];
        
        $this->json([
            'data' => $data,
            'message' => 'JSON response test successful'
        ]);
    }

    /**
     * Test request input handling.
     */
    public function testInput()
    {
        $this->requireAuth();
        
        // Test validation
        $errors = $this->request->validate([
            'name' => 'required|min:2',
            'email' => 'required|email'
        ]);
        
        if (!empty($errors)) {
            $this->json([
                'message' => 'Validation failed',
                'errors' => $errors
            ], 422);
            return;
        }
        
        $this->json([
            'data' => [
                'all_input' => $this->request->all(),
                'name' => $this->request->input('name'),
                'email' => $this->request->input('email'),
                'method' => $this->request->method(),
                'is_ajax' => $this->request->isAjax(),
                'expects_json' => $this->request->expectsJson()
            ],
            'message' => 'Input handling test successful'
        ]);
    }
}