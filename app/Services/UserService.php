<?php

namespace App\Services;

use App\Repositories\UserRepository;
use App\Repositories\RoleRepository;

/**
 * A unified service for handling all business logic related to users.
 * This class merges the responsibilities of the old UserManager and UserManagementService.
 */
class UserService
{
    /**
     * Get all users with their roles.
     */
    public function getAllUsers(array $filters = []): array
    {
        return $this->userRepository->getAllWithRoles($filters);
    }

    /**
     * Get a single user by ID.
     */
    public function getUser(int $id): ?array
    {
        return $this->userRepository->findById($id);
    }

    /**
     * Updates a user's status and roles with critical business logic.
     * This logic is from the old UserManager, ensuring that non-active
     * users have their roles and employee links properly managed.
     */
    public function updateUser(int $userId, array $data): bool
    {
        if (!isset($data['status']) || !isset($data['roles'])) {
            throw new \InvalidArgumentException("Status and roles are required for updating a user.");
        }

        $newStatus = $data['status'];

        $this->userRepository->updateUserStatus($userId, $newStatus);

        if ($newStatus !== 'active') {
            // If user is made inactive, blocked, etc., unlink from employee and remove all roles.
            $this->userRepository->unlinkEmployee($userId);
            $this->userRepository->updateUserRoles($userId, []);
        } else {
            // Only update roles if user is active.
            $this->userRepository->updateUserRoles($userId, $data['roles']);
        }

        return true;
    }

    /**
     * Delete a user and their associated roles.
     */
    public function deleteUser(int $id): bool
    {
        return $this->userRepository->delete($id);
    }

    /**
     * Get a user's roles.
     */
    public function getUserRoles(int $userId): array
    {
        return $this->roleRepository->getUserRoles($userId);
    }

    /**
     * Get a user's role IDs.
     */
    public function getRoleIdsForUser(int $userId): array
    {
        return $this->userRepository->getRoleIdsForUser($userId);
    }

    /**
     * Get all roles available in the system.
     */
    public function getAllRoles(): array
    {
        return $this->roleRepository->getAllRoles();
    }

    /**
     * Link an employee record to a user account.
     */
    public function linkEmployee(int $userId, int $employeeId): bool
    {
        return $this->userRepository->linkEmployee($userId, $employeeId);
    }

    /**
     * Get a list of users who are not yet linked to an employee record.
     */
    public function getUsersWithoutEmployee(): array
    {
        return $this->userRepository->findUsersWithoutEmployeeRecord();
    }

    /**
     * Get a list of employees who are not yet linked to a user account.
     */
    public function getUnlinkedEmployees(?int $departmentId = null): array
    {
        return $this->userRepository->getUnlinkedEmployees($departmentId);
    }

    /**
     * Unlink an employee from a user account.
     */
    public function unlinkEmployee(int $userId): bool
    {
        return $this->userRepository->unlinkEmployee($userId);
    }
}