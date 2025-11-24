<?php

namespace App\Repositories;

use App\Core\Database;
use App\Models\VehicleBreakdown;
use App\Models\VehicleRepair;
use App\Models\VehicleMaintenance;

class VehicleMaintenanceRepository
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    // Breakdowns
    public function findAllBreakdowns(array $filters = []): array
    {
        $sql = "SELECT vb.*, v.vehicle_number, v.model, e.name as driver_name
                FROM vehicle_breakdowns vb
                JOIN vehicles v ON vb.vehicle_id = v.id
                JOIN hr_employees e ON vb.driver_employee_id = e.id";
        
        $where = [];
        $params = [];

        if (!empty($filters['vehicle_id'])) {
            $where[] = "vb.vehicle_id = :vehicle_id";
            $params[':vehicle_id'] = $filters['vehicle_id'];
        }

        if (!empty($filters['department_id'])) {
            $where[] = "v.department_id = :department_id";
            $params[':department_id'] = $filters['department_id'];
        }

        if (!empty($filters['status'])) {
            $where[] = "vb.status = :status";
            $params[':status'] = $filters['status'];
        }

        if (!empty($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }

        $sql .= " ORDER BY vb.created_at DESC";

        return $this->db->fetchAllAs(VehicleBreakdown::class, $sql, $params);
    }

    public function findBreakdownById(int $id): ?VehicleBreakdown
    {
        $sql = "SELECT vb.*, v.vehicle_number, v.model, e.name as driver_name
                FROM vehicle_breakdowns vb
                JOIN vehicles v ON vb.vehicle_id = v.id
                JOIN hr_employees e ON vb.driver_employee_id = e.id
                WHERE vb.id = :id";
        
        return $this->db->fetchOneAs(VehicleBreakdown::class, $sql, [':id' => $id]);
    }

    public function createBreakdown(array $data): int
    {
        $sql = "INSERT INTO vehicle_breakdowns (vehicle_id, driver_employee_id, breakdown_item, description, mileage, photo_path, status)
                VALUES (:vehicle_id, :driver_employee_id, :breakdown_item, :description, :mileage, :photo_path, :status)";
        
        $this->db->execute($sql, [
            ':vehicle_id' => $data['vehicle_id'],
            ':driver_employee_id' => $data['driver_employee_id'],
            ':breakdown_item' => $data['breakdown_item'],
            ':description' => $data['description'] ?? null,
            ':mileage' => $data['mileage'] ?? null,
            ':photo_path' => $data['photo_path'] ?? null,
            ':status' => $data['status'] ?? '?‘ìˆ˜'
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function updateBreakdown(int $id, array $data): bool
    {
        $fields = [];
        $params = [':id' => $id];

        foreach ($data as $key => $value) {
            if (in_array($key, ['breakdown_item', 'description', 'mileage', 'photo_path', 'status'])) {
                $fields[] = "{$key} = :{$key}";
                $params[":{$key}"] = $value;
            }
        }

        if (empty($fields)) {
            return false;
        }

        $sql = "UPDATE vehicle_breakdowns SET " . implode(', ', $fields) . " WHERE id = :id";
        
        return $this->db->execute($sql, $params) > 0;
    }

    // Repairs
    public function findRepairByBreakdownId(int $breakdownId): ?VehicleRepair
    {
        $sql = "SELECT vr.*, e.name as repairer_name
                FROM vehicle_repairs vr
                LEFT JOIN hr_employees e ON vr.repairer_id = e.id
                WHERE vr.breakdown_id = :breakdown_id";
        
        return $this->db->fetchOneAs(VehicleRepair::class, $sql, [':breakdown_id' => $breakdownId]);
    }

    public function createRepair(array $data): int
    {
        $sql = "INSERT INTO vehicle_repairs (breakdown_id, repair_type, repair_item, parts_used, cost, repairer_id, repair_shop, completed_at)
                VALUES (:breakdown_id, :repair_type, :repair_item, :parts_used, :cost, :repairer_id, :repair_shop, :completed_at)";
        
        $this->db->execute($sql, [
            ':breakdown_id' => $data['breakdown_id'],
            ':repair_type' => $data['repair_type'] ?? null,
            ':repair_item' => $data['repair_item'] ?? null,
            ':parts_used' => $data['parts_used'] ?? null,
            ':cost' => $data['cost'] ?? null,
            ':repairer_id' => $data['repairer_id'] ?? null,
            ':repair_shop' => $data['repair_shop'] ?? null,
            ':completed_at' => $data['completed_at'] ?? null
        ]);

        return (int) $this->db->lastInsertId();
    }

    // Self Maintenances
    public function findAllMaintenances(array $filters = []): array
    {
        $sql = "SELECT vm.*, v.vehicle_number, v.model, e.name as driver_name
                FROM vehicle_maintenances vm
                JOIN vehicles v ON vm.vehicle_id = v.id
                JOIN hr_employees e ON vm.driver_employee_id = e.id";
        
        $where = [];
        $params = [];

        if (!empty($filters['vehicle_id'])) {
            $where[] = "vm.vehicle_id = :vehicle_id";
            $params[':vehicle_id'] = $filters['vehicle_id'];
        }

        if (!empty($filters['department_id'])) {
            $where[] = "v.department_id = :department_id";
            $params[':department_id'] = $filters['department_id'];
        }

        if (!empty($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }

        $sql .= " ORDER BY vm.created_at DESC";

        return $this->db->fetchAllAs(VehicleMaintenance::class, $sql, $params);
    }

    public function createMaintenance(array $data): int
    {
        $sql = "INSERT INTO vehicle_maintenances (vehicle_id, driver_employee_id, maintenance_item, description, used_parts, photo_path, status)
                VALUES (:vehicle_id, :driver_employee_id, :maintenance_item, :description, :used_parts, :photo_path, :status)";
        
        $this->db->execute($sql, [
            ':vehicle_id' => $data['vehicle_id'],
            ':driver_employee_id' => $data['driver_employee_id'],
            ':maintenance_item' => $data['maintenance_item'],
            ':description' => $data['description'] ?? null,
            ':used_parts' => $data['used_parts'] ?? null,
            ':photo_path' => $data['photo_path'] ?? null,
            ':status' => $data['status'] ?? '?„ë£Œ'
        ]);

        return (int) $this->db->lastInsertId();
    }
}
