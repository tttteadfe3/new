<?php

namespace App\Repositories;

use App\Core\Database;
use App\Services\DataScopeService;

class VehicleRepository
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
            'sql' => "SELECT v.*, d.name as department_name FROM vm_vehicles v LEFT JOIN hr_departments d ON v.department_id = d.id",
            'params' => [],
            'where' => []
        ];

        // Apply data scope
        // Assuming a scope method `applyVehicleScope` will be created in DataScopeService
        $queryParts = $this->dataScopeService->applyVehicleScope($queryParts, 'v');

        if (!empty($filters['status_code'])) {
            $queryParts['where'][] = "v.status_code = ?";
            $queryParts['params'][] = $filters['status_code'];
        }

        if (!empty($queryParts['where'])) {
            $queryParts['sql'] .= " WHERE " . implode(" AND ", $queryParts['where']);
        }

        return $this->db->query($queryParts['sql'], $queryParts['params']);
    }

    public function findById(int $id): ?array
    {
        $sql = "SELECT v.*, d.name as department_name FROM vm_vehicles v LEFT JOIN hr_departments d ON v.department_id = d.id WHERE v.id = ?";
        $result = $this->db->query($sql, [$id]);
        return $result[0] ?? null;
    }

    public function save(array $data): int
    {
        if (isset($data['id'])) {
            // Update
            $id = $data['id'];
            unset($data['id']);
            $this->db->update('vm_vehicles', $id, $data);
            return $id;
        } else {
            // Create
            return $this->db->insert('vm_vehicles', $data);
        }
    }

    public function delete(int $id): bool
    {
        return $this->db->delete('vm_vehicles', $id);
    }
}
