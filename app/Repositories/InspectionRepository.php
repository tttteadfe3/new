<?php

namespace App\Repositories;

use App\Core\Database;
use App\Services\DataScopeService;

class InspectionRepository
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
            'sql' => "SELECT i.*, v.vehicle_number
                      FROM vm_vehicle_inspections i
                      JOIN vm_vehicles v ON i.vehicle_id = v.id",
            'params' => [],
            'where' => []
        ];

        // Apply data scope
        $queryParts = $this->dataScopeService->applyVehicleScope($queryParts, 'v');

        if (!empty($filters['vehicle_id'])) {
            $queryParts['where'][] = "i.vehicle_id = ?";
            $queryParts['params'][] = $filters['vehicle_id'];
        }

        if (!empty($queryParts['where'])) {
            $queryParts['sql'] .= " WHERE " . implode(" AND ", $queryParts['where']);
        }

        return $this->db->query($queryParts['sql'], $queryParts['params']);
    }

    public function findById(int $id): ?array
    {
        $sql = "SELECT i.*, v.vehicle_number
                FROM vm_vehicle_inspections i
                JOIN vm_vehicles v ON i.vehicle_id = v.id
                WHERE i.id = ?";
        $result = $this->db->query($sql, [$id]);
        return $result[0] ?? null;
    }

    public function save(array $data): int
    {
        if (isset($data['id'])) {
            $id = $data['id'];
            unset($data['id']);
            $this->db->update('vm_vehicle_inspections', $id, $data);
            return $id;
        } else {
            return $this->db->insert('vm_vehicle_inspections', $data);
        }
    }

    public function delete(int $id): bool
    {
        return $this->db->delete('vm_vehicle_inspections', $id);
    }
}
