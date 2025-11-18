<?php

namespace App\Repositories;

use App\Core\Database;
use App\Services\DataScopeService;

class ConsumableLogRepository
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
            'sql' => "SELECT cl.*, v.vehicle_number, c.name as consumable_name, e.name as employee_name
                      FROM vm_vehicle_consumable_logs cl
                      JOIN vm_vehicles v ON cl.vehicle_id = v.id
                      JOIN vm_vehicle_consumables c ON cl.consumable_id = c.id
                      LEFT JOIN hr_employees e ON cl.replaced_by_employee_id = e.id",
            'params' => [],
            'where' => []
        ];

        // Apply data scope
        $queryParts = $this->dataScopeService->applyVehicleScope($queryParts, 'v');

        if (!empty($filters['vehicle_id'])) {
            $queryParts['where'][] = "cl.vehicle_id = ?";
            $queryParts['params'][] = $filters['vehicle_id'];
        }

        if (!empty($filters['consumable_id'])) {
            $queryParts['where'][] = "cl.consumable_id = ?";
            $queryParts['params'][] = $filters['consumable_id'];
        }

        if (!empty($queryParts['where'])) {
            $queryParts['sql'] .= " WHERE " . implode(" AND ", $queryParts['where']);
        }

        return $this->db->query($queryParts['sql'], $queryParts['params']);
    }

    public function findById(int $id): ?array
    {
        $sql = "SELECT cl.*, v.vehicle_number, c.name as consumable_name, e.name as employee_name
                FROM vm_vehicle_consumable_logs cl
                JOIN vm_vehicles v ON cl.vehicle_id = v.id
                JOIN vm_vehicle_consumables c ON cl.consumable_id = c.id
                LEFT JOIN hr_employees e ON cl.replaced_by_employee_id = e.id
                WHERE cl.id = ?";
        $result = $this->db->query($sql, [$id]);
        return $result[0] ?? null;
    }

    public function save(array $data): int
    {
        if (isset($data['id'])) {
            $id = $data['id'];
            unset($data['id']);
            $this->db->update('vm_vehicle_consumable_logs', $id, $data);
            return $id;
        } else {
            return $this->db->insert('vm_vehicle_consumable_logs', $data);
        }
    }

    public function delete(int $id): bool
    {
        return $this->db->delete('vm_vehicle_consumable_logs', $id);
    }
}
