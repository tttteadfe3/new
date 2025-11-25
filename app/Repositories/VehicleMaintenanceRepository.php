<?php

namespace App\Repositories;

use App\Core\Database;
use App\Services\DataScopeService;

class VehicleMaintenanceRepository
{
    private Database $db;
    private DataScopeService $dataScopeService;

    public function __construct(Database $db, DataScopeService $dataScopeService)
    {
        $this->db = $db;
        $this->dataScopeService = $dataScopeService;
    }

    // Breakdowns (고장)
    public function findAllBreakdowns(array $filters = []): array
    {
        $queryParts = [
            'sql' => "SELECT vw.*, v.vehicle_number, v.model, e.name as reporter_name
                      FROM vehicle_works vw
                      JOIN vehicles v ON vw.vehicle_id = v.id
                      JOIN hr_employees e ON vw.reporter_id = e.id
                      WHERE vw.type = '고장'",
            'params' => [],
            'where' => []
        ];

        // 데이터 스코프 적용 (부서 권한 + 운전자 본인 차량)
        $queryParts = $this->dataScopeService->applyVehicleScope($queryParts, 'v');

        // 추가 필터
        if (!empty($filters['vehicle_id'])) {
            $queryParts['where'][] = "vw.vehicle_id = :vehicle_id";
            $queryParts['params'][':vehicle_id'] = $filters['vehicle_id'];
        }

        if (!empty($filters['status'])) {
            $queryParts['where'][] = "vw.status = :status";
            $queryParts['params'][':status'] = $filters['status'];
        }

        // WHERE 절 조립
        if (!empty($queryParts['where'])) {
            $queryParts['sql'] .= " AND " . implode(" AND ", $queryParts['where']);
        }

        $queryParts['sql'] .= " ORDER BY vw.created_at DESC";

        return $this->db->fetchAll($queryParts['sql'], $queryParts['params']);
    }

    public function findBreakdownById(int $id)
    {
        $sql = "SELECT vw.*, v.vehicle_number, v.model, e.name as reporter_name
                FROM vehicle_works vw
                JOIN vehicles v ON vw.vehicle_id = v.id
                JOIN hr_employees e ON vw.reporter_id = e.id
                WHERE vw.id = :id AND vw.type = '고장'";
        
        return $this->db->fetchOne($sql, [':id' => $id]);
    }

    public function createBreakdown(array $data): int
    {
        $sql = "INSERT INTO vehicle_works (vehicle_id, reporter_id, type, work_item, description, mileage, photo_path, status)
                VALUES (:vehicle_id, :reporter_id, '고장', :work_item, :description, :mileage, :photo_path, :status)";
        
        $this->db->execute($sql, [
            ':vehicle_id' => $data['vehicle_id'],
            ':reporter_id' => $data['driver_employee_id'], // reporter_id = driver_employee_id
            ':work_item' => $data['breakdown_item'],
            ':description' => $data['description'] ?? null,
            ':mileage' => $data['mileage'] ?? null,
            ':photo_path' => $data['photo_path'] ?? null,
            ':status' => $data['status'] ?? '대기'
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function updateBreakdown(int $id, array $data): bool
    {
        $fields = [];
        $params = [':id' => $id];

        $fieldMapping = [
            'breakdown_item' => 'work_item',
            'description' => 'description',
            'mileage' => 'mileage',
            'photo_path' => 'photo_path',
            'status' => 'status'
        ];

        foreach ($data as $key => $value) {
            if (isset($fieldMapping[$key])) {
                $dbField = $fieldMapping[$key];
                $fields[] = "{$dbField} = :{$key}";
                $params[":{$key}"] = $value;
            }
        }

        if (empty($fields)) {
            return false;
        }

        $sql = "UPDATE vehicle_works SET " . implode(', ', $fields) . " WHERE id = :id AND type = '고장'";
        
        return $this->db->execute($sql, $params) > 0;
    }

    // Repairs
    public function findRepairByBreakdownId(int $breakdownId)
    {
        // vehicle_works 테이블에서 repair 정보도 함께 저장됨
        $sql = "SELECT * FROM vehicle_works WHERE id = :id AND type = '고장'";
        return $this->db->fetchOne($sql, [':id' => $breakdownId]);
    }

    public function createRepair(array $data): int
    {
        // 고장 레코드를 업데이트하는 방식
        $sql = "UPDATE vehicle_works 
                SET repair_type = :repair_type,
                    parts_used = :parts_used,
                    cost = :cost,
                    worker_id = :worker_id,
                    repair_shop = :repair_shop,
                    completed_at = :completed_at
                WHERE id = :breakdown_id";
        
        $this->db->execute($sql, [
            ':breakdown_id' => $data['breakdown_id'],
            ':repair_type' => $data['repair_type'] ?? null,
            ':parts_used' => $data['parts_used'] ?? null,
            ':cost' => $data['cost'] ?? null,
            ':worker_id' => $data['repairer_id'] ?? null,
            ':repair_shop' => $data['repair_shop'] ?? null,
            ':completed_at' => $data['completed_at'] ?? null
        ]);

        return $data['breakdown_id'];
    }

    // Self Maintenances (정비)
    public function findAllMaintenances(array $filters = []): array
    {
        $queryParts = [
            'sql' => "SELECT vw.*, v.vehicle_number, v.model, e.name as reporter_name
                      FROM vehicle_works vw
                      JOIN vehicles v ON vw.vehicle_id = v.id
                      JOIN hr_employees e ON vw.reporter_id = e.id
                      WHERE vw.type = '정비'",
            'params' => [],
            'where' => []
        ];

        // 데이터 스코프 적용 (부서 권한 + 운전자 본인 차량)
        $queryParts = $this->dataScopeService->applyVehicleScope($queryParts, 'v');

        // 추가 필터
        if (!empty($filters['vehicle_id'])) {
            $queryParts['where'][] = "vw.vehicle_id = :vehicle_id";
            $queryParts['params'][':vehicle_id'] = $filters['vehicle_id'];
        }

        // WHERE 절 조립
        if (!empty($queryParts['where'])) {
            $queryParts['sql'] .= " AND " . implode(" AND ", $queryParts['where']);
        }

        $queryParts['sql'] .= " ORDER BY vw.created_at DESC";

        return $this->db->fetchAll($queryParts['sql'], $queryParts['params']);
    }

    public function createMaintenance(array $data): int
    {
        $sql = "INSERT INTO vehicle_works (vehicle_id, reporter_id, type, work_item, description, photo_path, status)
                VALUES (:vehicle_id, :reporter_id, '정비', :work_item, :description, :photo_path, :status)";
        
        $this->db->execute($sql, [
            ':vehicle_id' => $data['vehicle_id'],
            ':reporter_id' => $data['driver_employee_id'],
            ':work_item' => $data['maintenance_item'],
            ':description' => $data['description'] ?? null,
            ':photo_path' => $data['photo_path'] ?? null,
            ':status' => $data['status'] ?? '대기'
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function updateMaintenance(int $id, array $data): bool
    {
        $fields = [];
        $params = [':id' => $id];

        $fieldMapping = [
            'maintenance_item' => 'work_item',
            'description' => 'description',
            'photo_path' => 'photo_path',
            'status' => 'status'
        ];

        foreach ($data as $key => $value) {
            if (isset($fieldMapping[$key])) {
                $dbField = $fieldMapping[$key];
                $fields[] = "{$dbField} = :{$key}";
                $params[":{$key}"] = $value;
            }
        }

        if (empty($fields)) {
            return false;
        }

        $sql = "UPDATE vehicle_works SET " . implode(', ', $fields) . " WHERE id = :id AND type = '정비'";
        
        return $this->db->execute($sql, $params) > 0;
    }
}