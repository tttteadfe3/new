<?php

namespace App\Services;

use App\Repositories\UserRepository;
use App\Repositories\EmployeeRepository;
use Exception;

class ProfileService
{
    private UserRepository $userRepository;
    private EmployeeRepository $employeeRepository;

    public function __construct(UserRepository $userRepository, EmployeeRepository $employeeRepository)
    {
        $this->userRepository = $userRepository;
        $this->employeeRepository = $employeeRepository;
    }

    /**
     * 직원 정보와 함께 사용자 프로필 가져오기
     * @param int $userId
     * @return array
     * @throws Exception
     */
    public function getUserProfile(int $userId): array
    {
        // 기본 사용자 정보 가져오기
        $user = $this->userRepository->findById($userId);
        if (!$user) {
            throw new Exception('사용자를 찾을 수 없습니다.');
        }

        // 연결된 경우 직원 정보 가져오기
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
     * 프로필 업데이트 요청
     * @param int $userId
     * @param array $data
     * @return bool
     * @throws Exception
     */
    public function requestProfileUpdate(int $userId, array $data): bool
    {
        $employee = $this->employeeRepository->findByUserId($userId);

        if (!$employee) {
            throw new Exception('수정할 직원 정보가 없습니다.');
        }

        if ($employee['profile_update_status'] === '대기') {
            throw new Exception('이미 프로필 변경 요청이 승인 대기 중입니다.');
        }

        return $this->employeeRepository->requestProfileUpdate($userId, $data);
    }
}
