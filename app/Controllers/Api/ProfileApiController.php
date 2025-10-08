<?php

namespace App\Controllers\Api;

use App\Repositories\UserRepository;
use App\Repositories\EmployeeRepository;

class ProfileApiController extends BaseApiController
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Handle all profile API requests based on action parameter
     */
    public function index(): void
    {
        $action = $this->getAction();
        
        try {
            switch ($action) {
                case 'get_my_profile':
                    $this->getMyProfile();
                    break;
                case 'save_my_profile':
                    $this->saveMyProfile();
                    break;
                default:
                    $this->apiBadRequest('Invalid action');
            }
        } catch (\Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * Get current user's profile information
     */
    private function getMyProfile(): void
    {
        $user = $this->user();
        if (!$user) {
            $this->apiForbidden();
            return;
        }
        
        $userId = $user['id'];
        
        // 1. 기본 사용자 정보는 항상 조회
        $userDetails = UserRepository::findById($userId);
        if (!$userDetails) {
            $this->apiNotFound('User not found.');
            return;
        }
        
        // 2. 직원 정보가 연결되어 있다면 함께 조회
        $employee = null;
        if ($userDetails['employee_id']) {
            $employee = EmployeeRepository::findById($userDetails['employee_id']);
        }
        
        // 3. 사용자 정보와 직원 정보를 합쳐서 반환
        $this->apiSuccess([
            'user' => $userDetails,
            'employee' => $employee
        ]);
    }

    /**
     * Save current user's profile information
     */
    private function saveMyProfile(): void
    {
        $user = $this->user();
        if (!$user) {
            $this->apiForbidden();
            return;
        }
        
        $userId = $user['id'];
        $input = $this->getJsonInput();
        
        if (empty($input)) {
            $this->apiBadRequest('Profile data is required');
            return;
        }
        
        $employee = EmployeeRepository::findByUserId($userId);
        
        if (!$employee) {
            $this->apiError('수정할 직원 정보가 없습니다.');
            return;
        }
        
        if ($employee['profile_update_status'] === 'pending') {
            $this->apiError('이미 프로필 변경 요청이 승인 대기 중입니다.');
            return;
        }
        
        // EmployeeRepository의 requestProfileUpdate 메소드를 호출
        if (EmployeeRepository::requestProfileUpdate($userId, $input)) {
            $this->apiSuccess(null, '프로필 수정 요청이 완료되었습니다. 관리자 승인 후 반영됩니다.');
        } else {
            $this->apiError('프로필 수정 요청에 실패했습니다.');
        }
    }
}