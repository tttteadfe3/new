<?php

namespace App\Repositories;

use App\Core\Database;
use App\Models\Vehicle;
use App\Services\PolicyEngine;
use App\Core\SessionManager;

class VehicleRepository
{
    private Database $db;
    private PolicyEngine $policyEngine;
    private SessionManager $sessionManager;

    public function __construct(Database $db, PolicyEngine $policyEngine, SessionManager $sessionManager)
    {
        $this->db = $db;
        $this->policyEngine = $policyEngine;
        $this->sessionManager = $sessionManager;
    }

    public function findAll(array $filters = []): array
    {
        $queryParts = [
            'sql' => "SELECT v.*, d.name as department_name, e.name as driver_name
                      FROM vehicles v
                      LEFT JOIN hr_departments d ON v.department_id = d.id
                      LEFT JOIN hr_employees e ON v.driver_employee_id = e.id",
            'params' => [],
            'where' => []
        ];

        // 데이터 스코프 적용 (PolicyEngine 사용)
        $user = $this->sessionManager->get('user');
        if ($user) {
            $scopeIds = $this->policyEngine->getScopeIds($user['id'], 'vehicle', 'view');
            
            if ($scopeIds === null) {
                // 전체 조회 가능
            } elseif (empty($scopeIds)) {
                $queryParts['where'][] = "1=0";
            } else {
                $inClause = implode(',', array_map('intval', $scopeIds));
                $queryParts['where'][] = "v.department_id IN ($inClause)";
            }
        }

        // 추가 필터 적용
        if (!empty($filters['department_id'])) {
            $queryParts['where'][] = "v.department_id = :department_id";
            $queryParts['params'][':department_id'] = $filters['department_id'];
        }

        if (!empty($filters['status_code'])) {
            $queryParts['where'][] = "v.status_code = :status_code";
            $queryParts['params'][':status_code'] = $filters['status_code'];
        }

        if (!empty($filters['search'])) {
            $queryParts['where'][] = "(v.vehicle_number LIKE :search OR v.model LIKE :search)";
            $queryParts['params'][':search'] = '%' . $filters['search'] . '%';
        }

        // WHERE 절 조립
        if (!empty($queryParts['where'])) {
            $queryParts['sql'] .= " WHERE " . implode(" AND ", $queryParts['where']);
        }

        $queryParts['sql'] .= " ORDER BY v.created_at DESC";

        return $this->db->fetchAll($queryParts['sql'], $queryParts['params']);
    }

    public function findById(int $id): ?array
    {
        $sql = "SELECT v.*, d.name as department_name, e.name as driver_name
                FROM vehicles v
                LEFT JOIN hr_departments d ON v.department_id = d.id
                LEFT JOIN hr_employees e ON v.driver_employee_id = e.id
                WHERE v.id = :id";
        
        $result = $this->db->fetchOne($sql, [':id' => $id]);
        return $result === false ? null : $result;
    }

    public function create(array $data): int
    {
        $sql = "INSERT INTO vehicles (vehicle_number, model, payload_capacity, year, release_date, vehicle_type, department_id, driver_employee_id, status_code)
                VALUES (:vehicle_number, :model, :payload_capacity, :year, :release_date, :vehicle_type, :department_id, :driver_employee_id, :status_code)";
        
        $this->db->execute($sql, [
            ':vehicle_number' => $data['vehicle_number'] ?? '',
            ':model' => $data['model'] ?? '',
            ':payload_capacity' => $data['payload_capacity'] ?? null,
            ':year' => !empty($data['year']) ? $data['year'] : null,
            ':release_date' => !empty($data['release_date']) ? $data['release_date'] : null,
            ':vehicle_type' => $data['vehicle_type'] ?? null,
            ':department_id' => !empty($data['department_id']) ? $data['department_id'] : null,
            ':driver_employee_id' => !empty($data['driver_employee_id']) ? $data['driver_employee_id'] : null,
            ':status_code' => $data['status_code'] ?? '정상'
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $fields = [];
        $params = [':id' => $id];

        foreach ($data as $key => $value) {
            if (in_array($key, ['vehicle_number', 'model', 'payload_capacity', 'year', 'release_date', 'vehicle_type', 'department_id', 'driver_employee_id', 'status_code'])) {
                $fields[] = "{$key} = :{$key}";
                // 빈 문자열이나 0이 아닌 경우에만 null 처리 (department_id, driver_employee_id 등)
                if (in_array($key, ['department_id', 'driver_employee_id', 'year', 'release_date']) && empty($value)) {
                     $params[":{$key}"] = null;
                } else {
                     $params[":{$key}"] = $value;
                }
            }
        }

        if (empty($fields)) {
            return false;
        }

        $sql = "UPDATE vehicles SET " . implode(', ', $fields) . " WHERE id = :id";
        
        return $this->db->execute($sql, $params) > 0;
    }

    public function delete(int $id): bool
    {
        $sql = "DELETE FROM vehicles WHERE id = :id";
        return $this->db->execute($sql, [':id' => $id]) > 0;
    }
}
