<?php

namespace App\Repositories;

use App\Core\Database;
use App\Services\DataScopeService;
use PDO;

class VehicleWorkRepository
{
    private Database $db;
    private DataScopeService $dataScopeService;

    public function __construct(Database $db, DataScopeService $dataScopeService)
    {
        $this->db = $db;
        $this->dataScopeService = $dataScopeService;
    }

    /**
     * 작업 목록 조회 (필터링 지원)
     */
    public function findAll(array $filters = []): array
    {
        $queryParts = [
            'sql' => "SELECT 
                        vw.*,
                        v.vehicle_number,
                        v.model,
                        v.department_id,
                        e.name as reporter_name
                      FROM vehicle_works vw
                      LEFT JOIN vehicles v ON vw.vehicle_id = v.id
                      LEFT JOIN hr_employees e ON vw.reporter_id = e.id",
            'params' => [],
            'where' => []
        ];
        
        // DataScope 적용 (부서 권한 + 운전자 본인 차량)
        $queryParts = $this->dataScopeService->applyVehicleScope($queryParts, 'v');
        
        // type 필터
        if (!empty($filters['type'])) {
            $queryParts['where'][] = "vw.type = :type";
            $queryParts['params'][':type'] = $filters['type'];
        }
        
        // status 필터
        if (!empty($filters['status'])) {
            $queryParts['where'][] = "vw.status = :status";
            $queryParts['params'][':status'] = $filters['status'];
        }
        
        // vehicle_id 필터
        if (!empty($filters['vehicle_id'])) {
            $queryParts['where'][] = "vw.vehicle_id = :vehicle_id";
            $queryParts['params'][':vehicle_id'] = $filters['vehicle_id'];
        }
        
        // WHERE 절 조립
        if (!empty($queryParts['where'])) {
            $queryParts['sql'] .= " WHERE " . implode(" AND ", $queryParts['where']);
        }
        
        $queryParts['sql'] .= " ORDER BY vw.created_at DESC";
        
        return $this->db->fetchAll($queryParts['sql'], $queryParts['params']);
    }

    /**
     * 작업 상세 조회
     */
    public function findById(int $id): ?array
    {
        $sql = "
            SELECT 
                vw.*,
                v.vehicle_number,
                v.model,
                e.name as reporter_name,
                w.name as worker_name,
                d.name as decided_by_name,
                c.name as confirmed_by_name
            FROM vehicle_works vw
            LEFT JOIN vehicles v ON vw.vehicle_id = v.id
            LEFT JOIN hr_employees e ON vw.reporter_id = e.id
            LEFT JOIN hr_employees w ON vw.worker_id = w.id
            LEFT JOIN hr_employees d ON vw.decided_by = d.id
            LEFT JOIN hr_employees c ON vw.confirmed_by = c.id
            WHERE vw.id = :id
        ";
        
        $result = $this->db->fetchOne($sql, [':id' => $id]);
        return $result ?: null;
    }

    /**
     * 작업 등록
     */
    public function create(array $data): int
    {
        $sql = "
            INSERT INTO vehicle_works (
                vehicle_id, type, status, reporter_id, work_item, 
                description, mileage, photo_path, photo2_path, photo3_path,
                completed_at
            ) VALUES (
                :vehicle_id, :type, :status, :reporter_id, :work_item,
                :description, :mileage, :photo_path, :photo2_path, :photo3_path,
                :completed_at
            )
        ";
        
        return $this->db->insert($sql, [
            ':vehicle_id' => $data['vehicle_id'],
            ':type' => $data['type'],
            ':status' => $data['status'] ?? '신고',
            ':reporter_id' => $data['reporter_id'],
            ':work_item' => $data['work_item'],
            ':description' => $data['description'] ?? null,
            ':mileage' => $data['mileage'] ?? null,
            ':photo_path' => $data['photo_path'] ?? null,
            ':photo2_path' => $data['photo2_path'] ?? null,
            ':photo3_path' => $data['photo3_path'] ?? null,
            ':completed_at' => $data['completed_at'] ?? null
        ]);
    }

    /**
     * 작업 수정
     */
    public function update(int $id, array $data): bool
    {
        $fields = [];
        $params = [':id' => $id];
        
        $allowedFields = [
            'status', 'repair_type',  'decided_at', 'decided_by',
            'parts_used', 'cost', 'worker_id', 'repair_shop',
            'completed_at', 'confirmed_at', 'confirmed_by',
            'photo_path', 'photo2_path', 'photo3_path', 'description'
        ];
        
        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "`{$field}` = :{$field}";
                $params[":{$field}"] = $data[$field];
            }
        }
        
        if (empty($fields)) {
            return false;
        }
        
        $sql = "UPDATE vehicle_works SET " . implode(', ', $fields) . " WHERE id = :id";
        
        return (bool) $this->db->execute($sql, $params);
    }

    /**
     * 작업 삭제
     */
    public function delete(int $id): bool
    {
        $sql = "DELETE FROM vehicle_works WHERE id = :id";
        return (bool) $this->db->execute($sql, [':id' => $id]);
    }
}
