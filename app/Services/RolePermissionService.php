<?php

namespace App\Services;

use App\Repositories\RoleRepository;

class RolePermissionService
{
    /**
     * 모든 역할 목록 조회 (사용자 수 포함)
     */
    public function getAllRolesWithUserCount(): array
    {
        return $this->roleRepository->getAllRolesWithUserCount();
    }

    /**
     * 모든 권한 목록 조회
     */
    public function getAllPermissions(): array
    {
        return $this->roleRepository->getAllPermissions();
    }

    /**
     * 특정 역할의 권한 목록 조회
     */
    public function getRolePermissions(int $roleId): array
    {
        return $this->roleRepository->getRolePermissions($roleId);
    }

    /**
     * 역할 권한 업데이트
     */
    public function updateRolePermissions(int $roleId, array $permissionIds): void
    {
        $this->roleRepository->updateRolePermissions($roleId, $permissionIds);
    }

    /**
     * 역할 조회
     */
    public function getRole(int $roleId): ?array
    {
        return $this->roleRepository->findById($roleId);
    }

    /**
     * 역할 생성
     */
    public function createRole(string $name, string $description): string
    {
        return $this->roleRepository->create($name, $description);
    }

    /**
     * 역할 수정
     */
    public function updateRole(int $roleId, string $name, string $description): bool
    {
        return $this->roleRepository->update($roleId, $name, $description);
    }

    /**
     * 역할 삭제
     */
    public function deleteRole(int $roleId): bool
    {
        return $this->roleRepository->delete($roleId);
    }

    /**
     * 특정 역할에 할당된 사용자 목록 조회
     */
    public function getUsersAssignedToRole(int $roleId): array
    {
        return $this->roleRepository->getUsersAssignedToRole($roleId);
    }

    /**
     * 역할에 사용자가 할당되어 있는지 확인
     */
    public function isUserAssignedToRole(int $roleId): bool
    {
        return $this->roleRepository->isUserAssigned($roleId);
    }
}