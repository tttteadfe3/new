<?php

namespace App\Controllers\Api;

use App\Services\LitteringManager;
use App\Repositories\UserRepository;

class LitteringApiController extends BaseApiController
{
    private LitteringManager $litteringManager;

    public function __construct()
    {
        parent::__construct();
        $this->litteringManager = new LitteringManager();
    }

    /**
     * Handle all littering API requests based on action parameter
     */
    public function index(): void
    {
        $action = $this->getAction();
        
        try {
            switch ($action) {
                case 'get_active_littering':
                    $this->getActiveLittering();
                    break;
                case 'get_pending_littering':
                    $this->getPendingLittering();
                    break;
                case 'get_processed_littering':
                    $this->getProcessedLittering();
                    break;
                case 'register_littering':
                    $this->registerLittering();
                    break;
                case 'process_littering':
                    $this->processLittering();
                    break;
                default:
                    $this->apiBadRequest('Invalid action');
            }
        } catch (\Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * Get all active littering reports for map display
     */
    private function getActiveLittering(): void
    {
        $data = $this->litteringManager->getActiveLittering();
        $this->apiSuccess($data, '활성 무단투기 목록 조회 성공');
    }

    /**
     * Get pending littering reports for admin review
     */
    private function getPendingLittering(): void
    {
        $data = $this->litteringManager->getPendingLittering();
        $this->apiSuccess($data, '대기 중인 무단투기 목록 조회 성공');
    }

    /**
     * Get processed littering reports
     */
    private function getProcessedLittering(): void
    {
        $data = $this->litteringManager->getProcessedLittering();
        $this->apiSuccess($data, '처리 완료된 무단투기 목록 조회 성공');
    }

    /**
     * Register a new littering report
     */
    private function registerLittering(): void
    {
        // Get current user and employee information
        $user = $this->user();
        if (!$user) {
            $this->apiError('로그인이 필요합니다.', 'UNAUTHORIZED', 401);
            return;
        }
        
        $userId = $user['id'];
        
        // Get user details and employee ID
        $userDetails = UserRepository::findById($userId);
        if (!$userDetails) {
            $this->apiError('사용자 정보를 찾을 수 없습니다.', 'USER_NOT_FOUND', 404);
            return;
        }
        
        $employeeId = $userDetails['employee_id'] ?? null;
        
        $result = $this->litteringManager->registerLittering($_POST, $_FILES, $userId, $employeeId);
        $this->apiSuccess($result, '무단투기 정보가 성공적으로 등록되었습니다. 관리자 확인 후 지도에 표시됩니다.');
    }

    /**
     * Process a littering report (admin action)
     */
    private function processLittering(): void
    {
        $caseId = $_POST['id'] ?? null;
        
        if (!$caseId) {
            $this->apiBadRequest('처리할 민원의 ID가 필요합니다.');
            return;
        }
        
        $result = $this->litteringManager->processLittering($_POST, $_FILES);
        $this->apiSuccess($result, '처리 상태가 성공적으로 업데이트되었습니다.');
    }
}