<?php

namespace App\Controllers;

use App\Core\AuthManager;
use App\Core\DB;

class TestLoginController extends BaseController
{
    public function login()
    {
        // This is a temporary controller for testing purposes only.
        // It logs in a predefined test user.

        $kakaoId = 'test-user-12345';
        $pdo = DB::getInstance();

        // Find the test user by Kakao ID
        $stmt = $pdo->prepare("SELECT * FROM sys_users WHERE kakao_id = ?");
        $stmt->execute([$kakaoId]);
        $user = $stmt->fetch();

        if ($user) {
            // Log the user in using AuthManager
            AuthManager::login($user);
            // Redirect to the holidays page for testing
            $this->redirect('/holidays');
        } else {
            // Handle case where test user is not found
            echo "Test user with Kakao ID '{$kakaoId}' not found. Please run the setup script.";
            exit;
        }
    }
}