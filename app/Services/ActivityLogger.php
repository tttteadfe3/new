<?php

namespace App\Services;

use App\Core\SessionManager;
use App\Repositories\LogRepository;
use App\Repositories\UserRepository;

class ActivityLogger
{
    /**
     * 사용자의 메뉴 페이지 접근 활동을 기록합니다.
     * @param string $pageTitle 접근한 페이지의 제목
     */
    public static function logMenuAccess(string $pageTitle): void
    {
        if (SessionManager::has('user')) {
            $user = SessionManager::get('user');
            
            // UserRepository를 통해 최신 닉네임 조회 (선택사항이지만 더 정확함)
            $currentUser = UserRepository::findById($user['id']);
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
}