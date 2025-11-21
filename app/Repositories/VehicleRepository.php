<?php

namespace App\Repositories;

use App\Core\Database;
use App\Models\Vehicle;
use App\Services\DataScopeService;
use App\Services\SessionManager;

class VehicleRepository
{
    private Database $db;
    private DataScopeService $dataScopeService;
    private SessionManager $sessionManager;

    public function __construct(Database $db, DataScopeService $dataScopeService, SessionManager $sessionManager)
    {
        $this->db = $db;
        $this->dataScopeService = $dataScopeService;
        $this->sessionManager = $sessionManager;
    }

    public function find(int $id): ?Vehicle
    {
        $data = $this->db->fetchOne('SELECT * FROM vehicles WHERE id = :id', ['id' => $id]);
        return $data ? new Vehicle($data) : null;
    }

    public function findAll(array $filters = []): array
    {
        $queryParts = [
            'sql' => "SELECT v.*, d.name as department_name, e.name as driver_name
                      FROM vehicles v
                      LEFT JOIN hr_departments d ON v.department_id = d.id
                      LEFT JOIN hr_employees e ON v.driver_id = e.id",
            'params' => [],
            'where' => []
        ];

        // Check if the user has department admin rights.
        if ($this->dataScopeService->hasDepartmentAdminRights()) {
            // Apply department scope for admins.
            $queryParts = $this->dataScopeService->applyVehicleScope($queryParts, 'v');
        } else {
            // Filter by driver_id for non-admins.
            $employeeId = $this->sessionManager->get('employee_id');
            if ($employeeId) {
                $queryParts['where'][] = "v.driver_id = :driver_id";
                $queryParts['params'][':driver_id'] = $employeeId;
            } else {
                // If no employee_id is found, return an empty array to prevent showing all vehicles.
                return [];
            }
        }

        if (!empty($filters['department_id'])) {
            $queryParts['where'][] = "v.department_id = :department_id";
            $queryParts['params'][':department_id'] = $filters['department_id'];
        }

        if (!empty($queryParts['where'])) {
            $queryParts['sql'] .= " WHERE " . implode(" AND ", $queryParts['where']);
        }

        $results = $this->db->query($queryParts['sql'], $queryParts['params']);

        return array_map(fn($data) => new Vehicle($data), $results);
    }

    public function create(array $data): string
    {
        return $this->db->insert(
            'INSERT INTO vehicles (vin, license_plate, make, model, year, department_id, status, driver_id)
             VALUES (:vin, :license_plate, :make, :model, :year, :department_id, :status, :driver_id)',
            [
                'vin' => $data['vin'],
                'license_plate' => $data['license_plate'],
                'make' => $data['make'],
                'model' => $data['model'],
                'year' => $data['year'],
                'department_id' => $data['department_id'],
                'status' => $data['status'] ?? 'active',
                'driver_id' => $data['driver_id']
            ]
        );
    }

    public function update(int $id, array $data): bool
    {
        return $this->db->execute(
            'UPDATE vehicles SET
                vin = :vin,
                license_plate = :license_plate,
                make = :make,
                model = :model,
                year = :year,
                department_id = :department_id,
                status = :status,
                driver_id = :driver_id
             WHERE id = :id',
            [
                'id' => $id,
                'vin' => $data['vin'],
                'license_plate' => $data['license_plate'],
                'make' => $data['make'],
                'model' => $data['model'],
                'year' => $data['year'],
                'department_id' => $data['department_id'],
                'status' => $data['status'],
                'driver_id' => $data['driver_id']
            ]
        ) > 0;
    }

    public function delete(int $id): bool
    {
        return $this->db->execute('DELETE FROM vehicles WHERE id = :id', ['id' => $id]) > 0;
    }
}
