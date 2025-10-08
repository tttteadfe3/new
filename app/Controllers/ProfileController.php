<?php

namespace App\Controllers;

use App\Services\ProfileService;
use App\Core\JsonResponse;
use Exception;

class ProfileController extends BaseController
{
    private ProfileService $profileService;

    public function __construct()
    {
        parent::__construct();
        $this->profileService = new ProfileService();
    }

    /**
     * Display the profile page
     */
    public function index(): string
    {
        $this->requireAuth();
        
        $pageTitle = "내 프로필";
        log_menu_access($pageTitle);

        return $this->render('pages/profile/index', [
            'pageTitle' => $pageTitle
        ]);
    }

    /**
     * Get current user's profile data (API endpoint)
     */
    public function getProfile(): void
    {
        $this->requireAuth();
        
        try {
            $userId = $this->user()['id'];
            $profile = $this->profileService->getUserProfile($userId);
            
            $this->json([
                'success' => true,
                'data' => $profile
            ]);
        } catch (Exception $e) {
            $this->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update user profile (API endpoint)
     */
    public function updateProfile(): void
    {
        $this->requireAuth();
        
        try {
            $userId = $this->user()['id'];
            $input = json_decode(file_get_contents('php://input'), true);
            
            $result = $this->profileService->requestProfileUpdate($userId, $input);
            
            if ($result) {
                $this->json([
                    'success' => true,
                    'message' => '프로필 수정 요청이 완료되었습니다. 관리자 승인 후 반영됩니다.'
                ]);
            } else {
                $this->json([
                    'success' => false,
                    'message' => '프로필 수정 요청에 실패했습니다.'
                ], 400);
            }
        } catch (Exception $e) {
            $this->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update user profile (Web endpoint - alias for updateProfile)
     */
    public function update(): void
    {
        $this->updateProfile();
    }
}