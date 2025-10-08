<?php

namespace App\Controllers\Api;

use App\Services\LitteringManager;
use App\Core\SessionManager;

class LitteringAdminApiController extends BaseApiController
{
    private LitteringManager $litteringManager;

    public function __construct()
    {
        parent::__construct();
        $this->litteringManager = new LitteringManager();
    }

    /**
     * Handle all littering admin API requests based on action parameter
     */
    public function index(): void
    {
        $this->requireAuth('littering_manage');
        
        $action = $this->getAction();
        
        if (!$action) {
            $this->apiBadRequest('API action이 지정되지 않았습니다.');
            return;
        }
        
        try {
            switch ($action) {
                case 'get_pending_littering':
                    $this->getPendingLittering();
                    break;
                case 'confirm_littering':
                    $this->confirmLittering();
                    break;
                case 'delete_littering':
                    $this->deleteLittering();
                    break;
                case 'get_deleted_littering':
                    $this->getDeletedLittering();
                    break;
                case 'permanently_delete_littering':
                    $this->permanentlyDeleteLittering();
                    break;
                case 'restore_littering':
                    $this->restoreLittering();
                    break;
                default:
                    $this->apiNotFound('요청한 관리자 API를 찾을 수 없습니다.');
            }
        } catch (\Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * Get pending littering reports for admin review
     */
    private function getPendingLittering(): void
    {
        $data = $this->litteringManager->getPendingLittering();
        $this->apiSuccess($data, '확인 대기 목록 조회 성공');
    }

    /**
     * Confirm a littering report
     */
    private function confirmLittering(): void
    {
        $input = $this->getJsonInput();
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->apiBadRequest('잘못된 JSON 형식입니다.');
            return;
        }
        
        $adminId = SessionManager::get('user')['id'];
        $result = $this->litteringManager->confirmLittering($input, $adminId);
        $this->apiSuccess($result, '민원 정보가 성공적으로 확인 및 업데이트되었습니다.');
    }

    /**
     * Delete a littering report
     */
    private function deleteLittering(): void
    {
        $input = $this->getJsonInput();
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->apiBadRequest('잘못된 JSON 형식입니다.');
            return;
        }
        
        $adminId = SessionManager::get('user')['id'];
        $result = $this->litteringManager->deleteLittering($input, $adminId);
        $this->apiSuccess($result, '민원 정보가 성공적으로 삭제되었습니다.');
    }

    /**
     * Get deleted littering reports
     */
    private function getDeletedLittering(): void
    {
        $data = $this->litteringManager->getDeletedLittering();
        $this->apiSuccess($data, '삭제된 목록 조회 성공');
    }

    /**
     * Permanently delete a littering report
     */
    private function permanentlyDeleteLittering(): void
    {
        $input = $this->getJsonInput();
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->apiBadRequest('잘못된 JSON 형식입니다.');
            return;
        }
        
        $result = $this->litteringManager->permanentlyDeleteLittering($input);
        $this->apiSuccess($result, '민원 정보가 성공적으로 영구 삭제되었습니다.');
    }

    /**
     * Restore a deleted littering report
     */
    private function restoreLittering(): void
    {
        $input = $this->getJsonInput();
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->apiBadRequest('잘못된 JSON 형식입니다.');
            return;
        }
        
        $result = $this->litteringManager->restoreLittering($input);
        $this->apiSuccess($result, '민원 정보가 성공적으로 복원되었습니다.');
    }
}