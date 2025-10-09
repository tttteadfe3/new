<?php

namespace App\Controllers\Web;

class TestLoginController extends BaseController
{
    /**
     * Test login page.
     * This is intended to bypass Kakao OAuth for testing purposes.
     */
    public function login()
    {
        // This is a placeholder for a test login functionality.
        // In a real scenario, you might log in a default test user.
        echo "<h1>Test Login Page</h1><p>This route is for testing purposes.</p>";
    }
}