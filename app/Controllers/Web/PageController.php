<?php

namespace App\Controllers\Web;

use App\Services\KakaoAuthService;

class PageController extends BaseController
{
    public function login()
    {
        if ($this->isAuthenticated()) {
            $this->redirect('/dashboard');
        }

        $kakaoAuthService = new KakaoAuthService();
        $data = [
            'kakaoLoginUrl' => $kakaoAuthService->getAuthorizationUrl()
        ];

        return $this->render('auth/login', $data);
    }

    public function kakaoCallback()
    {
        $code = $this->request->input('code');
        if (!$code) {
            $this->redirect('/login?error=auth_failed');
        }

        $kakaoAuthService = new KakaoAuthService();
        try {
            $token = $kakaoAuthService->getAccessToken($code);
            $kakaoUserInfo = $kakaoAuthService->getUserProfile($token['access_token']);

            $user = \App\Repositories\UserRepository::findOrCreateFromKakao($kakaoUserInfo);

            $this->authService->login($user);

            if ($user['status'] === 'pending') {
                $this->redirect('/status');
            }

            $this->redirect('/dashboard');
        } catch (\Exception $e) {
            $this->redirect('/login?error=' . urlencode($e->getMessage()));
        }
    }
}