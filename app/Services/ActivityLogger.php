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
     * 공통 로그 기록 메서드
     * @param string $action
     * @param string $details
     */
    private function _log(string $action, string $details): void
    {
        $user = $this->sessionManager->get('user');
        $userId = $user['id'] ?? null;
        $userName = 'Unauthenticated';

        if ($userId) {
            $currentUser = $this->userRepository->findById($userId);
            $userName = $currentUser['nickname'] ?? ($user['nickname'] ?? 'Unknown');
        }

        $this->logRepository->insert([
            'user_id' => $userId,
            'user_name' => $userName,
            'action' => $action,
            'details' => $details,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'N/A'
        ]);
    }

    /**
     * 일반 비즈니스 활동을 기록합니다.
     * @param string $action 수행된 작업 유형 (e.g., 'supply_plan_create')
     * @param string $message 로그 메시지
     * @param array|null $context 추가 데이터
     */
    public function log(string $action, string $message, ?array $context = null): void
    {
        $details = $message;
        if (!empty($context)) {
            $details .= "\nContext: " . json_encode($context, JSON_UNESCAPED_UNICODE);
        }
        $this->_log($action, $details);
    }

    /**
     * 사용자의 웹 페이지 접근 활동을 기록합니다.
     * @param string $uri 접근한 URI
     */
    public function logPageAccess(string $uri): void
    {
        $this->_log('페이지 접근', "URI: {$uri}");
    }

    /**
     * 사용자의 API 호출 활동을 기록합니다.
     * @param string $method HTTP 메소드
     * @param string $uri 호출한 URI
     * @param string|null $body 요청 본문
     */
    public function logApiCall(string $method, string $uri, ?string $body): void
    {
        $details = "Method: {$method}, URI: {$uri}";
        if ($body && in_array(strtoupper($method), ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            $details .= "\nBody: " . $body;
        }
        $this->_log('API 호출', $details);
    }
}
