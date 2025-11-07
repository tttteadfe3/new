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

    /**
     * 로그인 페이지를 표시합니다.
     */
    public function login()
    {
        // 사용자가 이미 로그인되어 있으면 대시보드로 리디렉션합니다.
        if ($this->isAuthenticated()) {
            $this->redirect('/dashboard');
        }

        $data = [
            'kakaoLoginUrl' => $this->kakaoAuthService->getAuthorizationUrl()
        ];

        View::getInstance()->addCss(BASE_ASSETS_URL . "/assets/css/pages/login.css");
        echo $this->render('auth/login', $data, 'layouts/basic');
    }

    /**
     * 카카오 로그인 콜백을 처리합니다.
     */
    public function kakaoCallback()
    {
        error_log("AuthController::kakaoCallback - Starting callback process");
        
        $code = $this->request->input('code');
        $state = $this->request->input('state');
        $sessionState = $this->authService->getSessionManager()->get('oauth2state');

        // OAuth state 검증

        if (!$code || !$state || !$sessionState || $state !== $sessionState) {
            // CSRF 공격이 감지되었거나 상태가 일치하지 않습니다.
            $this->redirect('/login?error=invalid_state');
        }

        // 세션에서 상태를 사용한 후에는 지웁니다.
        $this->authService->getSessionManager()->remove('oauth2state');

        try {
            $token = $this->kakaoAuthService->getAccessToken($code);
            $kakaoUserInfo = $this->kakaoAuthService->getUserProfile($token['access_token']);

            // 리포지토리를 사용하여 데이터베이스에서 사용자를 찾거나 생성합니다.
            $user = $this->userRepository->findOrCreateFromKakao($kakaoUserInfo);

            // AuthService 인스턴스를 사용하여 사용자를 로그인시킵니다.
            $this->authService->login($user);

            // 사용자 상태에 따라 리디렉션합니다.
            if ($user['status'] === 'pending' || $user['status'] === '대기') {
                $this->redirect('/status'); // 계정이 승인 대기 중임을 알리는 페이지
            }
            $this->redirect('/dashboard');
        } catch (\Exception $e) {
            // 오류 처리 - 토큰 또는 사용자 프로필 가져오기 실패
            $this->redirect('/login?error=' . urlencode($e->getMessage()));
        }
    }

    /**
     * 사용자를 로그아웃시킵니다.
     */
    public function logout()
    {
        // AuthService의 로그아웃 메서드가 이제 세션 파기 및 리디렉션을 처리합니다.
        $this->authService->logout();
    }
}
