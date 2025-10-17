<?php

namespace App\Controllers\Web;

use App\Services\KakaoAuthService;
use App\Core\View;
use App\Repositories\UserRepository;
use App\Core\Request;
use App\Services\AuthService;
use App\Services\ViewDataService;
use App\Services\ActivityLogger;

class AuthController extends BaseController
{
    protected UserRepository $userRepository;
    protected KakaoAuthService $kakaoAuthService;

    public function __construct(
        Request $request,
        AuthService $authService,
        ViewDataService $viewDataService,
        ActivityLogger $activityLogger,
        UserRepository $userRepository,
        KakaoAuthService $kakaoAuthService
    ) {
        parent::__construct($request, $authService, $viewDataService, $activityLogger);
        $this->userRepository = $userRepository;
        $this->kakaoAuthService = $kakaoAuthService;
    }

    public function login()
    {
        // If the user is already logged in, redirect to the dashboard.
        if ($this->isAuthenticated()) {
            $this->redirect('/dashboard');
        }

        $data = [
            'kakaoLoginUrl' => $this->kakaoAuthService->getAuthorizationUrl()
        ];

        // The 'login' view will be created later.
        return $this->render('auth/login', $data);
    }

    public function kakaoCallback()
    {
        $code = $this->request->input('code');
        $state = $this->request->input('state');
        $sessionState = $this->authService->getSessionManager()->get('oauth2state');

        if (!$code || !$state || !$sessionState || $state !== $sessionState) {
            // CSRF attack detected or state mismatch.
            $this->redirect('/login?error=invalid_state');
        }

        // Clear the state from the session once it's been used.
        $this->authService->getSessionManager()->remove('oauth2state');

        try {
            $token = $this->kakaoAuthService->getAccessToken($code);
            $kakaoUserInfo = $this->kakaoAuthService->getUserProfile($token['access_token']);

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
