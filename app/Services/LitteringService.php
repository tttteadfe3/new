<?php

namespace App\Services;

use App\Repositories\LitteringRepository;
use App\Core\Validator;
use App\Core\FileUploader;
use App\Core\Database;
use Exception;

/**
 * 무단투기 신고와 관련된 모든 비즈니스 로직을 처리하기 위한 통합 서비스입니다.
 * 이 클래스는 이전 LitteringManager와 LitteringService의 책임을 통합합니다.
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

    /**
     * @return array
     */
    public function getActiveLittering(): array
    {
        return $this->litteringRepository->findAllActive();
    }

    /**
     * @return array
     */
    public function getPendingLittering(): array
    {
        return $this->litteringRepository->findAllPending();
    }

    /**
     * @return array
     */
    public function getCompletedLittering(): array
    {
        return $this->litteringRepository->findAllCompleted();
    }

    /**
     * @return array
     */
    public function getProcessedLitteringForApproval(): array
    {
        return $this->litteringRepository->findAllProcessedForApproval();
    }

    /**
     * @param string $status
     * @return array
     */
    public function getDeletedLittering(string $status): array
    {
        return $this->litteringRepository->findAllDeleted($status);
    }

    /**
     * @param int $id
     * @return array|null
     */
    public function getLitteringById(int $id): ?array
    {
        // 이전 서비스의 비효율적인 구현을 수정했습니다.
        return $this->litteringRepository->findById($id);
    }

    /**
     * @param array $postData
     * @param array $files
     * @param int $employeeId
     * @return array
     * @throws Exception
     */
    public function registerLittering(array $postData, array $files, int $employeeId): array
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
                'latitude'    => floatval($postData['lat']),
                'longitude'   => floatval($postData['lng']),
                'jibun_address' => Validator::sanitizeString($postData['jibun_address'] ?? ''),
                'road_address' => Validator::sanitizeString($postData['road_address'] ?? ''),
                'waste_type'  => Validator::sanitizeString($postData['waste_type'] ?? ''),
                'waste_type2' => Validator::sanitizeString($postData['waste_type2'] ?? ''),
                'fileName1'   => $fileName1,
                'fileName2'   => $fileName2,
                'created_by'  => $employeeId
            ];

            $newId = $this->litteringRepository->save($data);
            if ($newId === null) {
                throw new Exception("데이터베이스에 신고 등록을 실패했습니다.", 500);
            }

            $this->db->commit();
            return $this->getLitteringById($newId);

        } catch (Exception $e) {
            $this->db->rollBack();

            // 오류 발생 시 업로드된 파일 정리
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

    /**
     * @param array $postData
     * @param int $employeeId
     * @return array
     * @throws Exception
     */
    public function confirmLittering(array $postData, int $employeeId): array
    {
        $caseId = intval($postData['id'] ?? 0);
        if (!$caseId) {
            throw new Exception("잘못된 보고서 ID입니다.", 400);
        }

        $updateData = [
            'latitude'  => floatval($postData['latitude'] ?? 0),
            'longitude' => floatval($postData['longitude'] ?? 0),
            'jibun_address' => Validator::sanitizeString($postData['jibun_address'] ?? ''),
            'road_address' => Validator::sanitizeString($postData['road_address'] ?? ''),
            'waste_type'  => Validator::sanitizeString($postData['waste_type'] ?? ''),
            'waste_type2' => Validator::sanitizeString($postData['waste_type2'] ?? '')
        ];

        if (!$this->litteringRepository->confirm($caseId, $updateData, $employeeId)) {
            throw new Exception("보고서를 확인하지 못했습니다.", 500);
        }

        return $this->getLitteringById($caseId);
    }

    /**
     * @param array $postData
     * @param int $employeeId
     * @return array
     * @throws Exception
     */
    public function approveLittering(array $data, int $employeeId): array
    {
        $caseId = intval($data['id'] ?? 0);
        if (!$caseId) {
            throw new \InvalidArgumentException("잘못된 보고서 ID입니다.");
        }

        $correctedStatus = $data['corrected'] ?? 'o';
        $allowedStatuses = ['o', 'x', '='];
        if (!in_array($correctedStatus, $allowedStatuses)) {
            throw new \InvalidArgumentException("잘못된 개선 상태 값입니다.");
        }

        $case = $this->getLitteringById($caseId);
        if ($case['status'] !== '처리완료') {
            throw new Exception("승인하려면 보고서가 '처리됨' 상태여야 합니다.", 403);
        }

        if (!$this->litteringRepository->approve($caseId, $employeeId, $correctedStatus)) {
            throw new Exception("보고서를 승인하지 못했습니다.", 500);
        }

        return $this->getLitteringById($caseId);
    }

    /**
     * @param array $postData
     * @param int $employeeId
     * @return array
     * @throws Exception
     */
    public function deleteLittering(array $postData, int $employeeId): array
    {
        $caseId = intval($postData['id'] ?? 0);
        if (!$caseId) {
            throw new Exception("잘못된 보고서 ID입니다.", 400);
        }

        if (!$this->litteringRepository->softDelete($caseId, $employeeId)) {
            throw new Exception("보고서를 삭제하지 못했습니다.", 500);
        }

        return ['id' => $caseId];
    }

    /**
     * @param array $postData
     * @return array
     * @throws Exception
     */
    public function permanentlyDeleteLittering(array $postData): array
    {
        $caseId = intval($postData['id'] ?? 0);
        if (!$caseId) {
            throw new Exception("잘못된 보고서 ID입니다.", 400);
        }

        $case = $this->litteringRepository->findById($caseId);
        if ($case) {
            $photoPaths = array_filter([
                $case['reg_photo_path'] ?? null,
                $case['reg_photo_path2'] ?? null,
                $case['proc_photo_path'] ?? null
            ]);

            $deletedDir = UPLOAD_DIR . '/littering/deleted';
            if (!is_dir($deletedDir)) {
                mkdir($deletedDir, 0755, true);
            }

            foreach ($photoPaths as $photoPath) {
                $fileName = basename($photoPath);
                $sourcePath = UPLOAD_DIR . '/littering/' . $fileName;
                $destinationPath = $deletedDir . '/' . $fileName;

                if (file_exists($sourcePath)) {
                    rename($sourcePath, $destinationPath);
                }
            }
        }

        if (!$this->litteringRepository->deletePermanently($caseId)) {
            throw new Exception("보고서를 영구적으로 삭제하지 못했습니다.", 500);
        }

        return ['id' => $caseId];
    }

    /**
     * @param array $postData
     * @return array
     * @throws Exception
     */
    public function restoreLittering(array $postData): array
    {
        $caseId = intval($postData['id'] ?? 0);
        if (!$caseId) {
            throw new Exception("잘못된 보고서 ID입니다.", 400);
        }

        if (!$this->litteringRepository->restore($caseId)) {
            throw new Exception("보고서를 복원하지 못했습니다.", 500);
        }

        return $this->getLitteringById($caseId);
    }

    /**
     * @param array $postData
     * @param array $files
     * @param int $employeeId
     * @return array
     * @throws Exception
     */
    public function processLittering(array $postData, array $files, int $employeeId): array
    {
        $this->validateProcess($postData, $files);

        $caseId = intval($postData['id']);
        $case = $this->getLitteringById($caseId);
        if (!$case) {
            throw new Exception("보고서를 찾을 수 없습니다.", 404);
        }
        if ($case['status'] !== '확인') {
            throw new Exception("처리하기 전에 보고서를 확인해야 합니다.", 403);
        }

        $data = [
            'id'          => $caseId,
            'corrected'   => Validator::sanitizeString($postData['corrected']),
            'note'        => Validator::sanitizeString($postData['note'] ?? ''),
            'procFileName' => (isset($files['procPhoto']) && $files['procPhoto']['error'] === UPLOAD_ERR_OK)
                ? FileUploader::validateAndUpload($files['procPhoto'], 'littering', 'proc_') : ''
        ];

        if (!$this->litteringRepository->process($data, $employeeId)) {
            throw new Exception("데이터베이스에서 보고서 상태를 업데이트하지 못했습니다.", 500);
        }

        return $data;
    }

    /**
     * @return array
     */
    public function getLitteringStatistics(): array
    {
        // 이 로직은 원래 이전 LitteringService에 있었습니다.
        return [
            'active_count' => count($this->getActiveLittering()),
            'pending_count' => count($this->getPendingLittering()),
            'processed_count' => count($this->getProcessedLitteringForApproval()),
            'deleted_count' => count($this->getDeletedLittering()),
        ];
    }

    /**
     * @param array $postData
     * @param array $files
     * @return void
     * @throws Exception
     */
    private function validateRegistration(array $postData, array $files): void
    {
        if (!isset($files['photo2']) || $files['photo2']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("처리 후 사진이 필요합니다.", 400);
        }
        // ... LitteringManager의 추가 유효성 검사 로직
    }

    /**
     * @param array $postData
     * @param array $files
     * @return void
     * @throws Exception
     */
    private function validateProcess(array $postData, array $files): void
    {
        if (empty($postData['corrected'])) {
            throw new Exception("수정 상태가 필요합니다.", 400);
        }
        // ... LitteringManager의 추가 유효성 검사 로직
    }

    /**
     * @param array $file
     * @return void
     * @throws Exception
     */
    private function validateFile(array $file): void
    {
        // 이것은 단순화된 버전입니다. 실제 앱에서는 상수를 사용하세요.
        if (!in_array($file['type'], ['image/jpeg', 'image/png'])) {
            throw new Exception("잘못된 파일 형식입니다.", 400);
        }
        if ($file['size'] > 5 * 1024 * 1024) { // 5MB
            throw new Exception("파일 크기가 5MB를 초과합니다.", 400);
        }
    }
}
