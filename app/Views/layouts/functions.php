<?php
// layouts/functions.php
use App\Core\SessionManager;
use App\Repositories\LogRepository; // LogRepository 사용을 위해 추가

/**
 * HTML 출력 시 XSS 방지를 위한 유틸리티 함수
 */
function e(?string $string): string {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

use App\Repositories\UserRepository;

/**
 * 사용자의 메뉴 페이지 접근 활동을 기록하는 함수
 * @param string $pageTitle 접근한 페이지의 제목
 */
function log_menu_access(string $pageTitle) {
    if (SessionManager::has('user')) {
        $user = SessionManager::get('user');
        
        // UserRepository를 통해 최신 닉네임 조회 (선택사항이지만 더 정확함)
        $currentUser = App\Repositories\UserRepository::findById($user['id']);
        $userName = $currentUser['nickname'] ?? $user['nickname'];

        LogRepository::insert([
            ':user_id' => $user['id'],
            ':user_name' => $userName,
            ':action' => '메뉴 접근',
            ':details' => "페이지: " . $pageTitle,
            ':ip_address' => $_SERVER['REMOTE_ADDR']
        ]);
    }
}