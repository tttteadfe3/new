<?php
// app/Repositories/LitteringRepository.php
namespace App\Repositories;
use App\Core\Database;

class LitteringRepository {
    private Database $db;

    public function __construct(Database $db) {
        $this->db = $db;
    }
    /**
     * 사용자가 보는 지도에 표시될 모든 활성 민원(처리 전)을 조회합니다.
     * @return array
     */
    public function findAllActive(): array {
        $query = "SELECT * FROM `illegal_disposal_cases2` WHERE `status` NOT IN ('processed', 'completed', 'deleted') AND `deleted_at` IS NULL ORDER BY `created_at` DESC";
        return $this->db->fetchAll($query);
    }

    /**
     * 관리자가 볼 '확인 대기(pending)' 상태의 민원만 조회합니다.
     * @return array
     */
    public function findAllPending(): array {
        $query = "
            SELECT 
                idc.id, idc.address, idc.waste_type, idc.waste_type2, idc.created_at, idc.latitude, idc.longitude,
                idc.reg_photo_path, idc.reg_photo_path2, idc.status,
                COALESCE(e.name, '알 수 없음') as created_by_name
            FROM `illegal_disposal_cases2` idc
            LEFT JOIN `hr_employees` e ON idc.created_by = e.id
            WHERE idc.status = 'pending' AND idc.deleted_at IS NULL
            ORDER BY idc.created_at DESC
        ";
        return $this->db->fetchAll($query);
    }

    /**
     * 최종 완료된 민원만 조회합니다.
     * @return array
     */
    public function findAllCompleted(): array {
        $query = "
            SELECT 
                idc.*, 
                e.name as created_by_name,
                e2.name as updated_by_name
            FROM `illegal_disposal_cases2` idc
            LEFT JOIN `hr_employees` e ON idc.created_by = e.id
            LEFT JOIN `hr_employees` e2 ON idc.updated_by = e2.id
            WHERE idc.status = 'completed' AND idc.deleted_at IS NULL
            ORDER BY idc.updated_at DESC
        ";
        return $this->db->fetchAll($query);
    }

    /**
     * 관리자가 볼 '승인 대기(processed)' 상태의 민원만 조회합니다.
     * @return array
     */
    public function findAllProcessedForApproval(): array {
        $query = "
            SELECT
                idc.id, idc.address, idc.waste_type, idc.waste_type2, idc.created_at, idc.latitude, idc.longitude,
                idc.reg_photo_path, idc.reg_photo_path2, idc.proc_photo_path, idc.status,
                COALESCE(e.name, '알 수 없음') as created_by_name,
                COALESCE(e2.name, '알 수 없음') as updated_by_name
            FROM `illegal_disposal_cases2` idc
            LEFT JOIN `hr_employees` e ON idc.created_by = e.id
            LEFT JOIN `hr_employees` e2 ON idc.updated_by = e2.id
            WHERE idc.status = 'processed' AND idc.deleted_at IS NULL
            ORDER BY idc.created_at DESC
        ";
        return $this->db->fetchAll($query);
    }

    /**
     * 새로운 민원을 등록합니다.
     * @param array $data
     * @return int|null
     */
    public function save(array $data): ?int {
        $query = 'INSERT INTO `illegal_disposal_cases2` 
                    (`status`, `latitude`, `longitude`, `address`, `waste_type`, `waste_type2`, 
                     `reg_photo_path`, `reg_photo_path2`, `created_at`, `created_by`) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?)';
        
        $params = [
            'pending',
            $data['latitude'], $data['longitude'], $data['address'], 
            $data['waste_type'], $data['waste_type2'],
            $data['fileName1'], $data['fileName2'],
            $data['created_by']
        ];
        
        $newId = $this->db->insert($query, $params);
        return $newId > 0 ? $newId : null;
    }

    /**
     * 관리자가 민원을 확인하고 상태를 'confirmed'로 변경합니다. (id 기준)
     * @param int $caseId
     * @param array $data
     * @param int $adminId
     * @return bool
     */
    public function confirm(int $caseId, array $data, int $employeeId): bool {
        $query = 'UPDATE `illegal_disposal_cases2` 
                  SET `latitude` = ?, `longitude` = ?, `address` = ?, `waste_type` = ?, `waste_type2` = ?, 
                      `status` = ?, `confirmed_by` = ?, `confirmed_at` = NOW(), `updated_by` = ?
                  WHERE `id` = ?';
        $params = [
            $data['latitude'], $data['longitude'], $data['address'],
            $data['waste_type'], $data['waste_type2'], 'confirmed',
            $employeeId, $employeeId, $caseId
        ];
        return $this->db->execute($query, $params) > 0;
    }

    public function softDelete(int $caseId, int $employeeId): bool {
        $query = "UPDATE illegal_disposal_cases2 SET status = 'deleted', deleted_by = ?, deleted_at = NOW(), updated_by = ? WHERE id = ?";
        return $this->db->execute($query, [$employeeId, $employeeId, $caseId]) > 0;
    }

    /**
     * 민원 처리를 완료하고 상태를 'processed'로 변경합니다. (id 기준)
     * @param array $data (id 포함)
     * @return bool
     */
    public function process(array $data, int $employeeId): bool {
        $updateFields = '`corrected` = ?, `note` = ?, `status` = ?, `updated_by` = ?';
        $params = [$data['corrected'], $data['note'], 'processed', $employeeId];
        if (!empty($data['procFileName'])) {
            $updateFields .= ', `proc_photo_path` = ?';
            $params[] = $data['procFileName'];
        }
        
        // WHERE 절에 사용할 id를 파라미터 배열의 마지막에 추가
        $params[] = $data['id'];
        
        $query = "UPDATE `illegal_disposal_cases2` SET {$updateFields}, `updated_at` = NOW() WHERE `id` = ?";
        
        return $this->db->execute($query, $params) > 0;
    }

    /**
     * ID로 특정 민원 정보를 조회합니다.
     * @param int $id
     * @return array|null
     */
    public function findById(int $id): ?array {
        return $this->db->fetchOne("SELECT * FROM illegal_disposal_cases2 WHERE id = ?", [$id]);
    }

    public function findAllDeleted(): array {
        $query = "
            SELECT 
                idc.*, 
                e.name as created_by_name,
                e2.name as deleted_by_name
            FROM `illegal_disposal_cases2` idc
            LEFT JOIN `hr_employees` e ON idc.created_by = e.id
            LEFT JOIN `hr_employees` e2 ON idc.deleted_by = e2.id
            WHERE idc.status = 'deleted' 
            ORDER BY idc.deleted_at DESC
        ";
        return $this->db->fetchAll($query);
    }

    public function deletePermanently(int $caseId): bool {
        $query = "DELETE FROM illegal_disposal_cases2 WHERE id = ?";
        return $this->db->execute($query, [$caseId]) > 0;
    }

    public function restore(int $caseId): bool {
        $query = "UPDATE illegal_disposal_cases2 SET status = 'pending', deleted_by = NULL, deleted_at = NULL WHERE id = ?";
        return $this->db->execute($query, [$caseId]) > 0;
    }

    /**
     * 관리자가 처리된 민원을 최종 승인하고 상태를 'completed'로 변경합니다.
     * @param int $caseId
     * @param int $adminId
     * @return bool
     */
    public function approve(int $caseId, int $employeeId): bool {
        $query = 'UPDATE `illegal_disposal_cases2`
                  SET `status` = ?, `approved_by` = ?, `approved_at` = NOW(), `updated_by` = ?
                  WHERE `id` = ?';
        $params = ['completed', $employeeId, $employeeId, $caseId];
        return $this->db->execute($query, $params) > 0;
    }
}
