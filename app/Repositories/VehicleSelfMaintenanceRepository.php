<?php

namespace App\Repositories;

use App\Models\VehicleSelfMaintenance;
use PDO;

class VehicleSelfMaintenanceRepository
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function find(int $id): ?VehicleSelfMaintenance
    {
        $stmt = $this->db->prepare('SELECT * FROM vehicle_self_maintenances WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        return $data ? new VehicleSelfMaintenance($data) : null;
    }

    public function findByVehicleId(int $vehicleId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM vehicle_self_maintenances WHERE vehicle_id = :vehicle_id ORDER BY maintenance_date DESC');
        $stmt->execute(['vehicle_id' => $vehicleId]);
        return array_map(fn($data) => new VehicleSelfMaintenance($data), $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function create(VehicleSelfMaintenance $maintenance): VehicleSelfMaintenance
    {
        $stmt = $this->db->prepare(
            'INSERT INTO vehicle_self_maintenances (vehicle_id, driver_id, maintenance_item, description, parts_used, maintenance_date)
             VALUES (:vehicle_id, :driver_id, :maintenance_item, :description, :parts_used, :maintenance_date)'
        );
        $stmt->execute([
            'vehicle_id' => $maintenance->vehicle_id,
            'driver_id' => $maintenance->driver_id,
            'maintenance_item' => $maintenance->maintenance_item,
            'description' => $maintenance->description,
            'parts_used' => $maintenance->parts_used,
            'maintenance_date' => $maintenance->maintenance_date,
        ]);
        $maintenance->id = $this->db->lastInsertId();
        return $maintenance;
    }

    public function update(VehicleSelfMaintenance $maintenance): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE vehicle_self_maintenances SET
                vehicle_id = :vehicle_id,
                driver_id = :driver_id,
                maintenance_item = :maintenance_item,
                description = :description,
                parts_used = :parts_used,
                maintenance_date = :maintenance_date
             WHERE id = :id'
        );
        return $stmt->execute([
            'id' => $maintenance->id,
            'vehicle_id' => $maintenance->vehicle_id,
            'driver_id' => $maintenance->driver_id,
            'maintenance_item' => $maintenance->maintenance_item,
            'description' => $maintenance->description,
            'parts_used' => $maintenance->parts_used,
            'maintenance_date' => $maintenance->maintenance_date,
        ]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM vehicle_self_maintenances WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }
}
