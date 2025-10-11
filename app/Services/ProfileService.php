<?php

namespace App\Services;

use App\Repositories\UserRepository;
use App\Repositories\EmployeeRepository;
use Exception;

class ProfileService
{
    /**
     * Get user profile with employee information
     */
    public function getUserProfile(int $userId): array
    {
        // Get basic user information
        $user = $this->userRepository->findById($userId);
        if (!$user) {
            throw new Exception('User not found.');
        }

        // Get employee information if linked
        $employee = null;
        if ($user['employee_id']) {
            $employee = $this->employeeRepository->findById($user['employee_id']);
        }

        return [
            'user' => $user,
            'employee' => $employee
        ];
    }

    /**
     * Request profile update
     */
    public function requestProfileUpdate(int $userId, array $data): bool
    {
        $employee = $this->employeeRepository->findByUserId($userId);

        if (!$employee) {
            throw new Exception('수정할 직원 정보가 없습니다.');
        }

        if ($employee['profile_update_status'] === 'pending') {
            throw new Exception('이미 프로필 변경 요청이 승인 대기 중입니다.');
        }

        return $this->employeeRepository->requestProfileUpdate($userId, $data);
    }
}