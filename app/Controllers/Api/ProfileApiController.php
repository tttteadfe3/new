<?php

namespace App\Controllers\Api;

use App\Services\ProfileService;
use Exception;

class ProfileApiController extends BaseApiController
{
    private ProfileService $profileService;

    public function __construct()
    {
        parent::__construct();
        $this->profileService = new ProfileService();
    }

    /**
     * Get current user's profile data.
     * Corresponds to GET /api/profile
     */
    public function index(): void
    {
        $this->requireAuth();
        
        try {
            $userId = $this->user()['id'];
            $profile = $this->profileService->getUserProfile($userId);
            $this->apiSuccess($profile);
        } catch (Exception $e) {
            $this->apiError('프로필 정보를 불러오는 중 오류가 발생했습니다.', 'SERVER_ERROR', 500);
        }
    }

    /**
     * Request an update to the user's profile.
     * Corresponds to PUT /api/profile
     */
    public function update(): void
    {
        $this->requireAuth();
        
        try {
            $userId = $this->user()['id'];
            $input = $this->getJsonInput();

            $result = $this->profileService->requestProfileUpdate($userId, $input);

            if ($result) {
                $this->apiSuccess(null, '프로필 수정 요청이 완료되었습니다. 관리자 승인 후 반영됩니다.');
            } else {
                $this->apiError('프로필 수정 요청에 실패했습니다.', 'OPERATION_FAILED');
            }
        } catch (Exception $e) {
            $this->apiError($e->getMessage(), 'SERVER_ERROR', 500);
        }
    }
}