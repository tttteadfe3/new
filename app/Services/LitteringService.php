<?php

namespace App\Services;

use App\Repositories\LitteringRepository;
use App\Core\Validator;
use App\Core\FileUploader;
use App\Core\Database;
use Exception;

/**
 * A unified service for handling all business logic related to littering reports.
 * This class merges the responsibilities of the old LitteringManager and LitteringService.
 */
class LitteringService
{
    private LitteringRepository $litteringRepository;
    private Database $db;

    public function __construct(LitteringRepository $litteringRepository, Database $db)
    {
        $this->litteringRepository = $litteringRepository;
        $this->db = $db;
    }

    public function getActiveLittering(): array
    {
        return $this->litteringRepository->findAllActive();
    }

    public function getPendingLittering(): array
    {
        return $this->litteringRepository->findAllPending();
    }

    public function getProcessedLittering(): array
    {
        return $this->litteringRepository->findAllProcessed();
    }

    public function getDeletedLittering(): array
    {
        return $this->litteringRepository->findAllDeleted();
    }

    public function getLitteringById(int $id): ?array
    {
        // Fixed the inefficient implementation from the old service.
        return $this->litteringRepository->findById($id);
    }

    public function registerLittering(array $postData, array $files, int $userId, ?int $employeeId): array
    {
        $this->validateRegistration($postData, $files);
        $this->db->beginTransaction();

        $fileName1 = null;
        $fileName2 = null;

        try {
            $fileName1 = (isset($files['photo1']) && $files['photo1']['error'] === UPLOAD_ERR_OK)
                ? FileUploader::validateAndUpload($files['photo1'], 'littering', 'reg1_') : '';
            $fileName2 = (isset($files['photo2']) && $files['photo2']['error'] === UPLOAD_ERR_OK)
                ? FileUploader::validateAndUpload($files['photo2'], 'littering', 'reg2_') : '';

            $data = [
                'status'      => 'pending',
                'user_id'     => $userId,
                'employee_id' => $employeeId,
                'latitude'    => floatval($postData['lat']),
                'longitude'   => floatval($postData['lng']),
                'address'     => Validator::sanitizeString($postData['address'] ?? ''),
                'waste_type'  => Validator::sanitizeString($postData['waste_type'] ?? ''),
                'waste_type2' => Validator::sanitizeString($postData['waste_type2'] ?? ''),
                'issueDate'   => $postData['issueDate'] ?? '',
                'fileName1'   => $fileName1,
                'fileName2'   => $fileName2
            ];

            $newId = $this->litteringRepository->save($data);
            if ($newId === null) {
                throw new Exception("데이터베이스에 신고 등록을 실패했습니다.", 500);
            }

            $this->db->commit();
            return $this->getLitteringById($newId);

        } catch (Exception $e) {
            $this->db->rollBack();

            // Clean up uploaded files on error
            foreach ([$fileName1, $fileName2] as $photoPath) {
                if (isset($photoPath) && !empty($photoPath)) {
                    $prefix = UPLOAD_URL_PATH . '/';
                    if (strpos($photoPath, $prefix) === 0) {
                        $relativeFilePath = substr($photoPath, strlen($prefix));
                        $fullPath = UPLOAD_DIR . $relativeFilePath;
                        if (file_exists($fullPath)) {
                            unlink($fullPath);
                        }
                    }
                }
            }

            throw $e;
        }
    }

    public function confirmLittering(array $postData, int $adminId): array
    {
        $caseId = intval($postData['id'] ?? 0);
        if (!$caseId) {
            throw new Exception("Invalid report ID.", 400);
        }

        $updateData = [
            'latitude'  => floatval($postData['latitude'] ?? 0),
            'longitude' => floatval($postData['longitude'] ?? 0),
            'address'   => Validator::sanitizeString($postData['address'] ?? ''),
            'waste_type'  => Validator::sanitizeString($postData['waste_type'] ?? ''),
            'waste_type2' => Validator::sanitizeString($postData['waste_type2'] ?? '')
        ];

        if (!$this->litteringRepository->confirm($caseId, $updateData, $adminId)) {
            throw new Exception("Failed to confirm report.", 500);
        }

        return $this->getLitteringById($caseId);
    }

    public function deleteLittering(array $postData, int $adminId): array
    {
        $caseId = intval($postData['id'] ?? 0);
        if (!$caseId) {
            throw new Exception("Invalid report ID.", 400);
        }

        if (!$this->litteringRepository->softDelete($caseId, $adminId)) {
            throw new Exception("Failed to delete report.", 500);
        }

        return ['id' => $caseId];
    }

    public function permanentlyDeleteLittering(array $postData): array
    {
        $caseId = intval($postData['id'] ?? 0);
        if (!$caseId) {
            throw new Exception("Invalid report ID.", 400);
        }

        if (!$this->litteringRepository->deletePermanently($caseId)) {
            throw new Exception("Failed to permanently delete report.", 500);
        }

        return ['id' => $caseId];
    }

    public function restoreLittering(array $postData): array
    {
        $caseId = intval($postData['id'] ?? 0);
        if (!$caseId) {
            throw new Exception("Invalid report ID.", 400);
        }

        if (!$this->litteringRepository->restore($caseId)) {
            throw new Exception("Failed to restore report.", 500);
        }

        return $this->getLitteringById($caseId);
    }

    public function processLittering(array $postData, array $files): array
    {
        $this->validateProcess($postData, $files);

        $caseId = intval($postData['id']);
        $case = $this->getLitteringById($caseId);
        if (!$case) {
            throw new Exception("Report not found.", 404);
        }
        if ($case['status'] !== 'confirmed') {
            throw new Exception("Report must be confirmed before processing.", 403);
        }

        $data = [
            'id'          => $caseId,
            'corrected'   => Validator::sanitizeString($postData['corrected']),
            'collectDate' => $postData['collectDate'],
            'note'        => Validator::sanitizeString($postData['note'] ?? ''),
            'procFileName' => (isset($files['procPhoto']) && $files['procPhoto']['error'] === UPLOAD_ERR_OK)
                ? FileUploader::validateAndUpload($files['procPhoto'], 'littering', 'proc_') : ''
        ];

        if (!$this->litteringRepository->process($data)) {
            throw new Exception("Failed to update report status in database.", 500);
        }

        return $data;
    }

    public function getLitteringStatistics(): array
    {
        // This logic was originally in the old LitteringService
        return [
            'active_count' => count($this->getActiveLittering()),
            'pending_count' => count($this->getPendingLittering()),
            'processed_count' => count($this->getProcessedLittering()),
            'deleted_count' => count($this->getDeletedLittering()),
        ];
    }

    private function validateRegistration(array $postData, array $files): void
    {
        if (!isset($files['photo2']) || $files['photo2']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("After photo is required.", 400);
        }
        // ... more validation logic from LitteringManager
    }

    private function validateProcess(array $postData, array $files): void
    {
        if (empty($postData['corrected'])) {
            throw new Exception("Correction status is required.", 400);
        }
        // ... more validation logic from LitteringManager
    }

    private function validateFile(array $file): void
    {
        // This is a simplified version. In a real app, use constants.
        if (!in_array($file['type'], ['image/jpeg', 'image/png'])) {
            throw new Exception("Invalid file type.", 400);
        }
        if ($file['size'] > 5 * 1024 * 1024) { // 5MB
            throw new Exception("File size exceeds 5MB.", 400);
        }
    }
}
