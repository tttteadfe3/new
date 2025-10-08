<?php

namespace App\Services;

use App\Services\LitteringManager;
use Exception;

class LitteringService
{
    private LitteringManager $litteringManager;

    public function __construct()
    {
        $this->litteringManager = new LitteringManager();
    }

    /**
     * Get all active littering reports
     */
    public function getActiveLittering(): array
    {
        return $this->litteringManager->getActiveLittering();
    }

    /**
     * Get all pending littering reports
     */
    public function getPendingLittering(): array
    {
        return $this->litteringManager->getPendingLittering();
    }

    /**
     * Get all processed littering reports
     */
    public function getProcessedLittering(): array
    {
        return $this->litteringManager->getProcessedLittering();
    }

    /**
     * Get all deleted littering reports
     */
    public function getDeletedLittering(): array
    {
        return $this->litteringManager->getDeletedLittering();
    }

    /**
     * Get littering report by ID
     */
    public function getLitteringById(int $id): array
    {
        // This method might need to be added to LitteringManager or Repository
        // For now, we'll use a basic implementation
        $activeLittering = $this->litteringManager->getActiveLittering();
        $pendingLittering = $this->litteringManager->getPendingLittering();
        $processedLittering = $this->litteringManager->getProcessedLittering();
        
        $allLittering = array_merge($activeLittering, $pendingLittering, $processedLittering);
        
        foreach ($allLittering as $item) {
            if ($item['id'] == $id) {
                return $item;
            }
        }
        
        throw new Exception("Littering report not found", 404);
    }

    /**
     * Register a new littering report
     */
    public function registerLittering(array $postData, array $files, int $userId, ?int $employeeId): array
    {
        return $this->litteringManager->registerLittering($postData, $files, $userId, $employeeId);
    }

    /**
     * Confirm littering report (admin review)
     */
    public function confirmLittering(array $postData, int $adminId): array
    {
        return $this->litteringManager->confirmLittering($postData, $adminId);
    }

    /**
     * Delete littering report (soft delete)
     */
    public function deleteLittering(array $postData, int $adminId): array
    {
        return $this->litteringManager->deleteLittering($postData, $adminId);
    }

    /**
     * Restore deleted littering report
     */
    public function restoreLittering(array $postData): array
    {
        return $this->litteringManager->restoreLittering($postData);
    }

    /**
     * Permanently delete littering report
     */
    public function permanentlyDeleteLittering(array $postData): array
    {
        return $this->litteringManager->permanentlyDeleteLittering($postData);
    }

    /**
     * Process littering report (mark as processed)
     */
    public function processLittering(array $postData, array $files): array
    {
        return $this->litteringManager->processLittering($postData, $files);
    }

    /**
     * Get littering statistics for dashboard
     */
    public function getLitteringStatistics(): array
    {
        $active = $this->getActiveLittering();
        $pending = $this->getPendingLittering();
        $processed = $this->getProcessedLittering();
        $deleted = $this->getDeletedLittering();

        return [
            'active_count' => count($active),
            'pending_count' => count($pending),
            'processed_count' => count($processed),
            'deleted_count' => count($deleted),
            'total_count' => count($active) + count($pending) + count($processed)
        ];
    }

    /**
     * Get littering reports by status
     */
    public function getLitteringByStatus(string $status): array
    {
        switch ($status) {
            case 'active':
                return $this->getActiveLittering();
            case 'pending':
                return $this->getPendingLittering();
            case 'processed':
                return $this->getProcessedLittering();
            case 'deleted':
                return $this->getDeletedLittering();
            default:
                throw new Exception("Invalid status: {$status}", 400);
        }
    }

    /**
     * Validate littering data before processing
     */
    public function validateLitteringData(array $data): array
    {
        $errors = [];

        // Required fields validation
        if (empty($data['mainType'])) {
            $errors['mainType'] = '주성상을 선택해주세요.';
        }

        if (empty($data['address'])) {
            $errors['address'] = '주소를 입력해주세요.';
        }

        if (empty($data['lat']) || empty($data['lng'])) {
            $errors['location'] = '위치 정보가 필요합니다.';
        }

        // Mixed waste validation
        if (!empty($data['subType']) && $data['subType'] === $data['mainType']) {
            $errors['subType'] = '부성상은 주성상과 다른 종류를 선택해주세요.';
        }

        return $errors;
    }

    /**
     * Format littering data for display
     */
    public function formatLitteringForDisplay(array $littering): array
    {
        // Add formatted dates, status labels, etc.
        $littering['formatted_created_at'] = date('Y-m-d H:i', strtotime($littering['created_at'] ?? ''));
        $littering['formatted_issue_date'] = date('Y-m-d', strtotime($littering['issueDate'] ?? ''));
        
        // Add status label
        $statusLabels = [
            'pending' => '검토 대기',
            'confirmed' => '확인 완료',
            'processed' => '처리 완료',
            'deleted' => '삭제됨'
        ];
        
        $littering['status_label'] = $statusLabels[$littering['status'] ?? 'pending'] ?? '알 수 없음';
        
        // Add waste type display
        $wasteTypes = [
            '생활폐기물' => '생활폐기물',
            '음식물' => '음식물',
            '재활용' => '재활용',
            '대형' => '대형폐기물',
            '소각' => '소각폐기물'
        ];
        
        $littering['main_type_display'] = $wasteTypes[$littering['mainType'] ?? ''] ?? $littering['mainType'] ?? '';
        $littering['sub_type_display'] = !empty($littering['subType']) ? 
            ($wasteTypes[$littering['subType']] ?? $littering['subType']) : '';
        
        return $littering;
    }
}