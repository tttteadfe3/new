<?php
// app/Services/LitteringManager.php
namespace App\Services;

use App\Repositories\LitteringRepository;
use App\Core\Validator;
use App\Core\FileUploader;
use Exception;

class LitteringManager {
    public function __construct() {}

    public function getActiveLittering(): array {
        return LitteringRepository::findAllActive();
    }

    public function getPendingLittering(): array {
        return LitteringRepository::findAllPending();
    }

    public function getProcessedLittering(): array {
        return LitteringRepository::findAllProcessed();
    }

    public function registerLittering(array $postData, array $files, int $userId, ?int $employeeId): array {
        // [NEW] 서버 측 등록 유효성 검사 실행
        $this->validateRegistration($postData, $files);

        $data = [
            'user_id'     => $userId,
            'employee_id' => $employeeId,
            'latitude'    => floatval($postData['lat']),
            'longitude'   => floatval($postData['lng']),
            'address'     => Validator::sanitizeString($postData['address'] ?? ''),
            'mainType'    => Validator::sanitizeString($postData['mainType'] ?? ''),
            'subType'     => Validator::sanitizeString($postData['subType'] ?? ''),
            'issueDate'   => $postData['issueDate'] ?? '',
            'fileName1'   => (isset($files['photo1']) && $files['photo1']['error'] === UPLOAD_ERR_OK)
                ? FileUploader::validateAndUpload($files['photo1'], 'littering', 'reg1_') : '',
            'fileName2'   => FileUploader::validateAndUpload($files['photo2'], 'littering', 'reg2_')
        ];

        $newId = LitteringRepository::save($data);
        if ($newId === null) throw new Exception("데이터베이스 등록에 실패했습니다.", 500);
        
        return LitteringRepository::findById($newId);
    }
    
    public function confirmLittering(array $postData, int $adminId): array {
        $caseId = intval($postData['id'] ?? 0);
        if (!$caseId) throw new Exception("유효하지 않은 민원 ID입니다.", 400);

        $updateData = [
            'latitude'  => floatval($postData['latitude']),
            'longitude' => floatval($postData['longitude']),
            'address'   => Validator::sanitizeString($postData['address']),
            'mainType'  => Validator::sanitizeString($postData['mainType']),
            'subType'   => Validator::sanitizeString($postData['subType'] ?? '')
        ];
        
        $success = LitteringRepository::confirm($caseId, $updateData, $adminId);
        if (!$success) {
            throw new Exception("민원 정보 확인 처리에 실패했습니다.", 500);
        }
        
        return LitteringRepository::findById($caseId);
    }

    public function deleteLittering(array $postData, int $adminId): array {
        $caseId = intval($postData['id'] ?? 0);
        if (!$caseId) throw new Exception("유효하지 않은 민원 ID입니다.", 400);

        $success = LitteringRepository::softDelete($caseId, $adminId);
        if (!$success) {
            throw new Exception("민원 정보 삭제 처리에 실패했습니다.", 500);
        }
        
        return ['id' => $caseId];
    }

    public function getDeletedLittering(): array {
        return LitteringRepository::findAllDeleted();
    }

    public function permanentlyDeleteLittering(array $postData): array {
        $caseId = intval($postData['id'] ?? 0);
        if (!$caseId) throw new Exception("유효하지 않은 민원 ID입니다.", 400);

        $success = LitteringRepository::deletePermanently($caseId);
        if (!$success) {
            throw new Exception("민원 정보 영구 삭제 처리에 실패했습니다.", 500);
        }
        
        return ['id' => $caseId];
    }

    public function restoreLittering(array $postData): array {
        $caseId = intval($postData['id'] ?? 0);
        if (!$caseId) throw new Exception("유효하지 않은 민원 ID입니다.", 400);

        $success = LitteringRepository::restore($caseId);
        if (!$success) {
            throw new Exception("민원 정보 복원 처리에 실패했습니다.", 500);
        }
        
        return LitteringRepository::findById($caseId);
    }

    public function processLittering(array $postData, array $files): array {
        // [NEW] 서버 측 처리 유효성 검사 실행
        $this->validateProcess($postData, $files);

        $caseId = intval($postData['id']);
        $case = LitteringRepository::findById($caseId);
        if (!$case) throw new Exception("존재하지 않는 민원입니다.", 404);
        if ($case['status'] !== 'confirmed') throw new Exception("관리자의 확인이 완료되지 않아 처리할 수 없습니다.", 403);
        
        $data = [
            'id'          => $caseId,
            'corrected'   => Validator::sanitizeString($postData['corrected']),
            'collectDate' => $postData['collectDate'],
            'note'        => Validator::sanitizeString($postData['note'] ?? ''),
            'procFileName' => (isset($files['procPhoto']) && $files['procPhoto']['error'] === UPLOAD_ERR_OK)
                ? FileUploader::validateAndUpload($files['procPhoto'], 'littering', 'proc_') : ''
        ];
        
        if (!LitteringRepository::process($data)) throw new Exception("데이터베이스 업데이트에 실패했습니다.", 500);

        return $data;
    }

    /**
     * [NEW] 민원 등록 데이터에 대한 서버 측 유효성 검사
     */
    private function validateRegistration(array $postData, array $files): void {
        if (!isset($files['photo2']) || $files['photo2']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("작업 후 사진은 필수 항목입니다.", 400);
        }

        if (isset($files['photo1']) && $files['photo1']['error'] === UPLOAD_ERR_OK) {
            $this->validateFile($files['photo1']);
        }
        $this->validateFile($files['photo2']);

        // 혼합 배출 시 부성상 선택 여부 (클라이언트 측 로직을 서버에서 간접적으로 확인)
        if (!empty($postData['subType']) && $postData['subType'] === '') {
             throw new Exception("혼합 배출 시 부성상을 선택해주세요.", 400);
        }
    }

    /**
     * [NEW] 민원 처리 데이터에 대한 서버 측 유효성 검사
     */
    private function validateProcess(array $postData, array $files): void {
        if (empty($postData['corrected'])) {
            throw new Exception("개선 여부를 선택해주세요.", 400);
        }

        $corrected = $postData['corrected'];
        $procPhoto = $files['procPhoto'] ?? null;

        if (($corrected === 'o' || $corrected === 'x') && (empty($procPhoto) || $procPhoto['error'] !== UPLOAD_ERR_OK)) {
            throw new Exception("개선/미개선의 경우 처리 사진은 필수입니다.", 400);
        }

        if ($procPhoto && $procPhoto['error'] === UPLOAD_ERR_OK) {
            $this->validateFile($procPhoto);
        }

        if (empty($postData['collectDate'])) {
            throw new Exception("수거일자를 입력해주세요.", 400);
        }
    }

    /**
     * [NEW] 단일 파일에 대한 유효성 검사 (타입, 크기)
     */
    private function validateFile(array $file): void {
        if (!in_array($file['type'], ALLOWED_MIMES)) {
            throw new Exception("허용되지 않는 파일 형식입니다.", 400);
        }
        if ($file['size'] > MAX_FILE_SIZE) {
            throw new Exception("파일 크기가 5MB를 초과할 수 없습니다.", 400);
        }
    }
}