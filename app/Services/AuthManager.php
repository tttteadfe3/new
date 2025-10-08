<?php
// app/Services/AuthManager.php
namespace App\Services;

use App\Core\SessionManager;
use App\Repositories\UserRepository;
use App\Repositories\LogRepository;
use Exception;

class AuthManager {
    /**
     * 사용자의 권한을 세션에 갱신(캐시)합니다.
     */
    public static function refreshPermissions() {
        if (!SessionManager::has('user')) {
            SessionManager::set('user_permissions', []);
            return;
        }
        $user_id = SessionManager::get('user')['id'];
        $permissions = array_column(UserRepository::getPermissions($user_id), 'key');
        SessionManager::set('user_permissions', $permissions);
        SessionManager::set('permissions_cached_at', time());
    }

    /**
     * 현재 로그인한 사용자가 특정 퍼미션을 가지고 있는지 확인합니다.
     * 실시간 동기화를 지원하는 세션 캐시를 사용합니다.
     */
    public static function check(string $permission_key): bool {
        if (!SessionManager::has('user')) {
            return false;
        }

        // 전역 권한 마지막 수정 시간 확인 (파일 기반 타임스탬프)
        $permissions_last_updated_file = ROOT_PATH . '/storage/permissions_last_updated.txt';
        $global_permissions_last_updated = file_exists($permissions_last_updated_file) ? (int)file_get_contents($permissions_last_updated_file) : 0;
        $user_permissions_cached_at = SessionManager::get('permissions_cached_at', 0);

        // 세션 캐시가 없거나, 캐시가 오래되었는지(권한 변경이 있었는지) 확인
        if (!SessionManager::has('user_permissions') || $user_permissions_cached_at < $global_permissions_last_updated) {
            self::refreshPermissions();
        }

        $permissions = SessionManager::get('user_permissions');

        return in_array($permission_key, $permissions ?? []);
    }

    public function processKakaoLogin(array $kakaoProfile): array {
        $user = UserRepository::findByKakaoId($kakaoProfile['id']);

        if ($user) {
            UserRepository::update($user['id'], $kakaoProfile);
            $userId = $user['id'];
        } else {
            $userId = UserRepository::create($kakaoProfile);
        }
        return UserRepository::findById($userId);
    }

    public function establishSession(array $user) {
        if ($user['status'] === 'blocked') {
            throw new Exception("차단된 계정은 세션을 생성할 수 없습니다.");
        }
        
        SessionManager::set('user', [
            'id' => $user['id'],
            'nickname' => $user['nickname'],
            'profile_image_url' => $user['profile_image_url'],
        ]);

        // 세션 생성 후 바로 권한 캐시
        self::refreshPermissions();

        LogRepository::insert([
            ':user_id' => $user['id'],
            ':user_name' => $user['nickname'],
            ':action' => '로그인 성공',
            ':details' => null,
            ':ip_address' => $_SERVER['REMOTE_ADDR']
        ]);
    }

    public function checkAccess() {
        $realtime_status = $this->checkStatus();
        if ($realtime_status !== 'active') {
            
            if (basename($_SERVER['PHP_SELF']) === 'status.php' || strpos($_SERVER['REQUEST_URI'], '/status') !== false) {
                return;
            }

            switch ($realtime_status) {
                case 'pending':
                    header('Location: ' . BASE_URL . '/status');
                    exit();
                    break;
                
                case 'blocked':
                default:
                    $this->logout();
                    break;
            }
        }
    }

    public function checkStatus(): string {
        if (!SessionManager::has('user')) {
            header('Location: ' . BASE_URL . '/index.php');
            exit();
        }

        $userId = SessionManager::get('user')['id'];
        if (!$userId) {
            $this->logout();
        }

        $currentUser = UserRepository::findById($userId);

        if (!$currentUser || !isset($currentUser['status'])) {
            $this->logout();
        }

        $realtime_status = $currentUser['status'];

        return $realtime_status;
    }
    
    public function logout() {
        if (SessionManager::has('user')) {
            $user = SessionManager::get('user');
            // UserRepository를 통해 최신 닉네임을 가져와 로그 기록
            $latestUser = UserRepository::findById($user['id']);
            $nickname = $latestUser['nickname'] ?? $user['nickname'];

            LogRepository::insert([
                ':user_id' => $user['id'],
                ':user_name' => $nickname,
                ':action' => '로그아웃',
                ':details' => null,
                ':ip_address' => $_SERVER['REMOTE_ADDR']
            ]);
        }
        SessionManager::destroy();
        header('Location: ' . BASE_URL . '/index.php');
        exit();
    }
}