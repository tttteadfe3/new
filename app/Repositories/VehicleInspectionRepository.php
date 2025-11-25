<?php

namespace App\Repositories;

use App\Core\Database;
use App\Models\VehicleInspection;

class VehicleInspectionRepository
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function findAll(array $filters = []): array
    {
        $sql = "SELECT vi.*, v.vehicle_number, v.model
                FROM vehicle_inspections vi
                JOIN vehicles v ON vi.vehicle_id = v.id";
        
        $where = [];
        $params = [];

        if (!empty($filters['vehicle_id'])) {
            $where[] = "vi.vehicle_id = :vehicle_id";
            $params[':vehicle_id'] = $filters['vehicle_id'];
        }

        if (!empty($filters['department_id'])) {
            $where[] = "v.department_id = :department_id";
            $params[':department_id'] = $filters['department_id'];
        }

        if (!empty($filters['upcoming_expiry'])) {
            // Find inspections expiring within N days
            $where[] = "vi.expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL :days DAY)";
            $params[':days'] = $filters['upcoming_expiry'];
        }

        if (!empty($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }

        $sql .= " ORDER BY vi.expiry_date ASC";

        return $this->db->fetchAllAs(VehicleInspection::class, $sql, $params);
    }

    public function create(array $data): int
    {
        $sql = "INSERT INTO vehicle_inspections (vehicle_id, inspection_date, expiry_date, inspector_name, result, cost, document_path)
                VALUES (:vehicle_id, :inspection_date, :expiry_date, :inspector_name, :result, :cost, :document_path)";
        
        $this->db->execute($sql, [
            ':vehicle_id' => $data['vehicle_id'],
            ':inspection_date' => $data['inspection_date'],
            ':expiry_date' => $data['expiry_date'],
            ':inspector_name' => $data['inspector_name'] ?? null,
            ':result' => $data['result'],
            ':cost' => $data['cost'] ?? null,
            ':document_path' => $data['document_path'] ?? null
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function findById(int $id): ?VehicleInspection
    {
        $sql = "SELECT vi.*, v.vehicle_number, v.model
                FROM vehicle_inspections vi
                JOIN vehicles v ON vi.vehicle_id = v.id
                WHERE vi.id = :id";

        return $this->db->fetchOneAs(VehicleInspection::class, $sql, [':id' => $id]) ?: null;
    }
}
