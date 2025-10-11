<?php

namespace App\Controllers\Web;

use App\Services\KakaoAuthService;
use App\Core\AuthManager;
use App\Core\View;
use App\Repositories\UserRepository;

class AuthController extends BaseController
    protected \App\Repositories\UserRepository $userRepository;
{
    public function login()
    {
        // If the user is already logged in, redirect to the dashboard.
        if ($this->isAuthenticated()) {
            $this->redirect('/dashboard');
        }

        $kakaoAuthService = new KakaoAuthService();
        $data = [
            'kakaoLoginUrl' => $kakaoAuthService->getAuthorizationUrl()
        ];

        // The 'login' view will be created later.
        return $this->render('auth/login', $data);
    }

    public function kakaoCallback()
    {
        $code = $this->request->input('code');
        if (!$code) {
            // Handle error - no code provided
            $this->redirect('/login?error=auth_failed');
        }

        $kakaoAuthService = new KakaoAuthService();
        try {
            $token = $kakaoAuthService->getAccessToken($code);
            $kakaoUserInfo = $kakaoAuthService->getUserProfile($token['access_token']);

            // Find or create the user in the database using the repository
            $user = $this->userRepository->findOrCreateFromKakao($kakaoUserInfo);

            // Log the user in using the AuthService instance
            $this->authService->login($user);

            // Redirect based on user status
            if ($user['status'] === 'pending') {
                $this->redirect('/status'); // A page that tells them their account is pending approval
            }

            $this->redirect('/dashboard');
        } catch (\Exception $e) {
            // Handle error - token or user profile fetch failed
            $this->redirect('/login?error=' . urlencode($e->getMessage()));
        }
    }

    public function logout()
    {
        // The logout method in AuthService now handles session destruction and redirection.
        $this->authService->logout();
    }
}