<?php

namespace App\Services;

use App\Repositories\UserRepository;

class UserManagementService
{
    /**
     * 모든 사용자 목록 조회
     */
    public function getAllUsers(): array
    {
        return UserRepository::getAll();
    }

    /**
     * 사용자 조회
     */
    public function getUser(int $id): ?array
    {
        return UserRepository::findById($id);
    }

    /**
     * 사용자 생성
     */
    public function createUser(array $userData): string
    {
        return UserRepository::create($userData);
    }

    /**
     * 사용자 수정
     */
    public function updateUser(int $id, array $userData): bool
    {
        return UserRepository::update($id, $userData);
    }

    /**
     * 사용자 삭제
     */
    public function deleteUser(int $id): bool
    {
        return UserRepository::delete($id);
    }

    /**
     * 사용자 역할 업데이트
     */
    public function updateUserRoles(int $userId, array $roleIds): bool
    {
        return UserRepository::updateUserRoles($userId, $roleIds);
    }

    /**
     * 사용자의 역할 목록 조회
     */
    public function getUserRoles(int $userId): array
    {
        return UserRepository::getUserRoles($userId);
    }

    /**
     * 사용자 상태 변경 (활성/비활성)
     */
    public function toggleUserStatus(int $userId): bool
    {
        return UserRepository::toggleStatus($userId);
    }
}