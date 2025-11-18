<?php

namespace App\Repositories;

use App\Core\Database;
use App\Services\DataScopeService;

class BreakdownRepository
{
    private Database $db;
    private DataScopeService $dataScopeService;

    public function __construct(Database $db, DataScopeService $dataScopeService)
    {
        $this->db = $db;
        $this->dataScopeService = $dataScopeService;
    }

    public function findAll(array $filters = []): array
    {
        $queryParts = [
            'sql' => "SELECT b.*, v.vehicle_number, e.name as driver_name
                      FROM vm_vehicle_breakdowns b
                      JOIN vm_vehicles v ON b.vehicle_id = v.id
                      JOIN hr_employees e ON b.driver_employee_id = e.id",
            'params' => [],
            'where' => []
        ];

        // Apply data scope
        $queryParts = $this->dataScopeService->applyVehicleScope($queryParts, 'v');

        if (!empty($filters['status'])) {
            $queryParts['where'][] = "b.status = ?";
            $queryParts['params'][] = $filters['status'];
        }

        if (!empty($queryParts['where'])) {
            $queryParts['sql'] .= " WHERE " . implode(" AND ", $queryParts['where']);
        }

        return $this->db->query($queryParts['sql'], $queryParts['params']);
    }

    public function findById(int $id): ?array
    {
        $sql = "SELECT b.*, v.vehicle_number, e.name as driver_name
                FROM vm_vehicle_breakdowns b
                JOIN vm_vehicles v ON b.vehicle_id = v.id
                JOIN hr_employees e ON b.driver_employee_id = e.id
                WHERE b.id = ?";
        $result = $this->db->query($sql, [$id]);
        return $result[0] ?? null;
    }

    public function save(array $data): int
    {
        if (isset($data['id'])) {
            $id = $data['id'];
            unset($data['id']);
            $this->db->update('vm_vehicle_breakdowns', $id, $data);
            return $id;
        } else {
            return $this->db->insert('vm_vehicle_breakdowns', $data);
        }
    }

    public function delete(int $id): bool
    {
        return $this->db->delete('vm_vehicle_breakdowns', $id);
    }
}
