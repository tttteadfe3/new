<?php

namespace App\Services;

use App\Repositories\UserRepository;
use App\Repositories\RoleRepository;

/**
 * 사용자와 관련된 모든 비즈니스 로직을 처리하기 위한 통합 서비스입니다.
 * 이 클래스는 이전 UserManager와 UserManagementService의 책임을 통합합니다.
 */
class UserService
{
    private UserRepository $userRepository;
    private RoleRepository $roleRepository;
    private OrganizationService $organizationService;

    public function __construct(
        UserRepository $userRepository,
        RoleRepository $roleRepository,
        OrganizationService $organizationService
    ) {
        $this->userRepository = $userRepository;
        $this->roleRepository = $roleRepository;
        $this->organizationService = $organizationService;
    }

    /**
     * 역할과 함께 모든 사용자를 가져옵니다.
     * @param array $filters
     * @return array
     */
    public function getAllUsers(array $filters = []): array
    {
        $visibleDeptIds = $this->organizationService->getVisibleDepartmentIdsForCurrentUser();
        return $this->userRepository->getAllWithRoles($filters, $visibleDeptIds);
    }

    /**
     * ID로 단일 사용자를 가져옵니다.
     * @param int $id
     * @return array|null
     */
    public function getUser(int $id): ?array
    {
        return $this->userRepository->findById($id);
    }

    /**
     * 중요한 비즈니스 로직으로 사용자의 상태와 역할을 업데이트합니다.
     * 이 로직은 이전 UserManager의 것으로, 비활성 사용자의 역할과 직원 링크가
     * 올바르게 관리되도록 보장합니다.
     * @param int $userId
     * @param array $data
     * @return bool
     * @throws \InvalidArgumentException
     */
    public function updateUser(int $userId, array $data): bool
    {
        if (!isset($data['status']) || !isset($data['roles'])) {
            throw new \InvalidArgumentException("사용자 업데이트에는 상태와 역할이 필요합니다.");
        }

        $newStatus = $data['status'];

        $this->userRepository->updateUserStatus($userId, $newStatus);

        if ($newStatus !== 'active') {
            // 사용자가 비활성, 차단 등으로 설정되면 직원과의 연결을 끊고 모든 역할을 제거합니다.
            $this->userRepository->unlinkEmployee($userId);
            $this->userRepository->updateUserRoles($userId, []);
        } else {
            // 사용자가 활성 상태인 경우에만 역할을 업데이트합니다.
            $this->userRepository->updateUserRoles($userId, $data['roles']);
        }

        return true;
    }

    /**
     * 사용자와 관련 역할을 삭제합니다.
     * @param int $id
     * @return bool
     */
    public function deleteUser(int $id): bool
    {
        return $this->userRepository->delete($id);
    }

    /**
     * 사용자의 역할을 가져옵니다.
     * @param int $userId
     * @return array
     */
    public function getUserRoles(int $userId): array
    {
        return $this->roleRepository->getUserRoles($userId);
    }

    /**
     * 사용자의 역할 ID를 가져옵니다.
     * @param int $userId
     * @return array
     */
    public function getRoleIdsForUser(int $userId): array
    {
        return $this->userRepository->getRoleIdsForUser($userId);
    }

    /**
     * 시스템에서 사용 가능한 모든 역할을 가져옵니다.
     * @return array
     */
    public function getAllRoles(): array
    {
        return $this->roleRepository->getAllRoles();
    }

    /**
     * 직원 기록을 사용자 계정에 연결합니다.
     * @param int $userId
     * @param int $employeeId
     * @return bool
     */
    public function linkEmployee(int $userId, int $employeeId): bool
    {
        return $this->userRepository->linkEmployee($userId, $employeeId);
    }

    /**
     * 아직 직원 기록에 연결되지 않은 사용자 목록을 가져옵니다.
     * @return array
     */
    public function getUsersWithoutEmployee(): array
    {
        return $this->userRepository->findUsersWithoutEmployeeRecord();
    }

    /**
     * 아직 사용자 계정에 연결되지 않은 직원 목록을 가져옵니다.
     * @return array
     */
    public function getUnlinkedEmployees(): array
    {
        $visibleDeptIds = $this->organizationService->getVisibleDepartmentIdsForCurrentUser();
        return $this->userRepository->getUnlinkedEmployees($visibleDeptIds);
    }

    /**
     * 사용자 계정에서 직원 연결을 해제합니다.
     * @param int $userId
     * @return bool
     */
    public function unlinkEmployee(int $userId): bool
    {
        return $this->userRepository->unlinkEmployee($userId);
    }
}
