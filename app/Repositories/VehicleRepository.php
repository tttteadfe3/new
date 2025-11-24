<?php

namespace App\Repositories;

use App\Core\Database;
use App\Models\Vehicle;

class VehicleRepository
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function findAll(array $filters = []): array
    {
        $sql = "SELECT v.*, d.name as department_name, e.name as driver_name
                FROM vehicles v
                LEFT JOIN hr_departments d ON v.department_id = d.id
                LEFT JOIN hr_employees e ON v.driver_employee_id = e.id";
        
        $where = [];
        $params = [];

        // Complex filtering logic for visibility
        $visibilityConditions = [];

        if (!empty($filters['visible_department_ids'])) {
            $deptIds = $filters['visible_department_ids'];
            $placeholders = [];
            foreach ($deptIds as $i => $id) {
                $key = ":vis_dept_$i";
                $placeholders[] = $key;
                $params[$key] = $id;
            }
            $visibilityConditions[] = "v.department_id IN (" . implode(',', $placeholders) . ")";
        }

        if (!empty($filters['current_user_driver_id'])) {
            $visibilityConditions[] = "v.driver_employee_id = :current_user_driver_id";
            $params[':current_user_driver_id'] = $filters['current_user_driver_id'];
        }

        if (!empty($visibilityConditions)) {
            $where[] = "(" . implode(" OR ", $visibilityConditions) . ")";
        }

        // Standard filters (AND)
        if (!empty($filters['department_id'])) {
            $where[] = "v.department_id = :department_id";
            $params[':department_id'] = $filters['department_id'];
        }

        if (!empty($filters['status_code'])) {
            $where[] = "v.status_code = :status_code";
            $params[':status_code'] = $filters['status_code'];
        }

        if (!empty($filters['search'])) {
            $where[] = "(v.vehicle_number LIKE :search OR v.model LIKE :search)";
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        if (!empty($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }

        $sql .= " ORDER BY v.created_at DESC";

        return $this->db->fetchAll($sql, $params);
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
