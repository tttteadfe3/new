<?php

namespace App\Services;

use App\Core\SessionManager;
use App\Repositories\LogRepository;
use App\Repositories\UserRepository;

class ActivityLogger
{
    private SessionManager $sessionManager;
    private LogRepository $logRepository;
    private UserRepository $userRepository;

    public function __construct(
        SessionManager $sessionManager,
        LogRepository $logRepository,
        UserRepository $userRepository
    ) {
        $this->sessionManager = $sessionManager;
        $this->logRepository = $logRepository;
        $this->userRepository = $userRepository;
    }

    /**
     * 사용자의 메뉴 페이지 접근 활동을 기록합니다.
     * @param string $pageTitle 접근한 페이지의 제목
     */
    public function logMenuAccess(string $pageTitle): void
    {
        if ($this->sessionManager->has('user')) {
            $user = $this->sessionManager->get('user');
            
            // UserRepository를 통해 최신 닉네임 조회
            $currentUser = $this->userRepository->findById($user['id']);
            $userName = $currentUser['nickname'] ?? $user['nickname'];

            $this->logRepository->insert([
                ':user_id' => $user['id'],
                ':user_name' => $userName,
                ':action' => '메뉴 접근',
                ':details' => "페이지: " . $pageTitle,
                ':ip_address' => $_SERVER['REMOTE_ADDR']
            ]);
        }
    }
}
