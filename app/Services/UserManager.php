<?php
// app/Services/UserManager.php
namespace App\Services;

use App\Repositories\UserRepository;

class UserManager {
    public function updateUser(int $userId, array $data): bool {
        // status와 roles 정보가 있는지 확인
        if (!isset($data['status']) || !isset($data['roles'])) {
            return false;
        }

        $newStatus = $data['status'];

        // status 업데이트
        UserRepository::updateUserStatus($userId, $newStatus);
        
        // 만약 status가 'active'가 아니게 되면, 직원 연결 해제 및 역할 모두 제거
        if ($newStatus !== 'active') {
            UserRepository::unlinkEmployee($userId);
            UserRepository::updateUserRoles($userId, []); // 빈 배열로 역할 모두 제거
        } else {
            // 'active' 상태일 경우에만 역할 업데이트
            UserRepository::updateUserRoles($userId, $data['roles']);
        }
        
        return true;
    }
}