<?php
// app/Repositories/LitteringRepository.php
namespace App\Repositories;
use App\Core\Database;

class LitteringRepository {
    /**
     * 사용자가 보는 지도에 표시될 모든 활성 민원(처리 전)을 조회합니다.
     * @return array
     */
    public static function findAllActive(): array {
        $query = "SELECT * FROM `illegal_disposal_cases2` WHERE `status` not in( 'processed', 'deleted' )ORDER BY `created_at` DESC";
        return Database::fetchAll($query);
    }

    /**
     * 관리자가 볼 '확인 대기(pending)' 상태의 민원만 조회합니다.
     * @return array
     */
    public static function findAllPending(): array {
        $query = "
            SELECT 
                idc.*, 
                u.nickname as user_name, 
                e.name as employee_name 
            FROM `illegal_disposal_cases2` idc
            LEFT JOIN `sys_users` u ON idc.user_id = u.id
            LEFT JOIN `hr_employees` e ON idc.employee_id = e.id
            WHERE idc.status = 'pending' AND idc.deleted_at IS NULL
            ORDER BY idc.created_at DESC
        ";
        return Database::fetchAll($query);
    }

    /**
     * 처리 완료된 민원만 조회합니다.
     * @return array
     */
    public static function findAllProcessed(): array {
        $query = "
            SELECT 
                idc.*, 
                u.nickname as user_name, 
                e.name as employee_name 
            FROM `illegal_disposal_cases2` idc
            LEFT JOIN `sys_users` u ON idc.user_id = u.id
            LEFT JOIN `hr_employees` e ON idc.employee_id = e.id
            WHERE idc.status = 'processed' 
            ORDER BY idc.updated_at DESC
        ";
        return Database::fetchAll($query);
    }

    /**
     * 새로운 민원을 등록합니다.
     * @param array $data
     * @return int|null
     */
    public static function save(array $data): ?int {
        $query = 'INSERT INTO `illegal_disposal_cases2` 
                    (`user_id`, `employee_id`, `status`, `latitude`, `longitude`, `address`, `waste_type`, `waste_type2`, 
                     `issue_date`, `reg_photo_path`, `reg_photo_path2`, `created_at`) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())';
        
        $params = [
            $data['user_id'], $data['employee_id'], 'pending',
            $data['latitude'], $data['longitude'], $data['address'], 
            $data['mainType'], $data['subType'], $data['issueDate'], 
            $data['fileName1'], $data['fileName2']
        ];
        
        $newId = Database::insert($query, $params);
        return $newId > 0 ? $newId : null;
    }

    /**
     * 관리자가 민원을 확인하고 상태를 'confirmed'로 변경합니다. (id 기준)
     * @param int $caseId
     * @param array $data
     * @param int $adminId
     * @return bool
     */
    public static function confirm(int $caseId, array $data, int $adminId): bool {
        $query = 'UPDATE `illegal_disposal_cases2` 
                  SET `latitude` = ?, `longitude` = ?, `address` = ?, `waste_type` = ?, `waste_type2` = ?, 
                      `status` = ?, `confirmed_by` = ?, `confirmed_at` = NOW()
                  WHERE `id` = ?';
        $params = [
            $data['latitude'], $data['longitude'], $data['address'],
            $data['mainType'], $data['subType'], 'confirmed',
            $adminId, $caseId
        ];
        return Database::execute($query, $params) > 0;
    }

    public static function softDelete(int $caseId, int $adminId): bool {
        $query = "UPDATE illegal_disposal_cases2 SET status = 'deleted', deleted_by = ?, deleted_at = NOW() WHERE id = ?";
        return Database::execute($query, [$adminId, $caseId]) > 0;
    }

    /**
     * 민원 처리를 완료하고 상태를 'processed'로 변경합니다. (id 기준)
     * @param array $data (id 포함)
     * @return bool
     */
    public static function process(array $data): bool {
        $updateFields = '`collect_date` = ?, `corrected` = ?, `note` = ?, `status` = ?';
        $params = [$data['collectDate'], $data['corrected'], $data['note'], 'processed'];
        if (!empty($data['procFileName'])) {
            $updateFields .= ', `proc_photo_path` = ?';
            $params[] = $data['procFileName'];
        }
        
        // WHERE 절에 사용할 id를 파라미터 배열의 마지막에 추가
        $params[] = $data['id'];
        
        $query = "UPDATE `illegal_disposal_cases2` SET {$updateFields}, `updated_at` = NOW() WHERE `id` = ?";
        
        return Database::execute($query, $params) > 0;
    }

    /**
     * ID로 특정 민원 정보를 조회합니다.
     * @param int $id
     * @return array|null
     */
    public static function findById(int $id): ?array {
        return Database::fetchOne("SELECT * FROM illegal_disposal_cases2 WHERE id = ?", [$id]);
    }

    public static function findAllDeleted(): array {
        $query = "
            SELECT 
                idc.*, 
                u.nickname as user_name, 
                e.name as employee_name 
            FROM `illegal_disposal_cases2` idc
            LEFT JOIN `sys_users` u ON idc.user_id = u.id
            LEFT JOIN `hr_employees` e ON idc.employee_id = e.id
            WHERE idc.status = 'deleted' 
            ORDER BY idc.deleted_at DESC
        ";
        return Database::fetchAll($query);
    }

    public static function deletePermanently(int $caseId): bool {
        $query = "DELETE FROM illegal_disposal_cases2 WHERE id = ?";
        return Database::execute($query, [$caseId]) > 0;
    }

    public static function restore(int $caseId): bool {
        $query = "UPDATE illegal_disposal_cases2 SET status = 'pending', deleted_by = NULL, deleted_at = NULL WHERE id = ?";
        return Database::execute($query, [$caseId]) > 0;
    }
}