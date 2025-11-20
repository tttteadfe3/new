<?php

namespace App\Repositories;

use App\Models\VehicleRepair;
use PDO;

class VehicleRepairRepository
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function find(int $id): ?VehicleRepair
    {
        $stmt = $this->db->prepare('SELECT * FROM vehicle_repairs WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        return $data ? new VehicleRepair($data) : null;
    }

    public function findByBreakdownId(int $breakdownId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM vehicle_repairs WHERE breakdown_id = :breakdown_id');
        $stmt->execute(['breakdown_id' => $breakdownId]);
        return array_map(fn($data) => new VehicleRepair($data), $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function create(VehicleRepair $repair): VehicleRepair
    {
        $stmt = $this->db->prepare(
            'INSERT INTO vehicle_repairs (breakdown_id, repair_type, repair_item, parts_used, cost, repairer_id, completed_at)
             VALUES (:breakdown_id, :repair_type, :repair_item, :parts_used, :cost, :repairer_id, :completed_at)'
        );
        $stmt->execute([
            'breakdown_id' => $repair->breakdown_id,
            'repair_type' => $repair->repair_type,
            'repair_item' => $repair->repair_item,
            'parts_used' => $repair->parts_used,
            'cost' => $repair->cost,
            'repairer_id' => $repair->repairer_id,
            'completed_at' => $repair->completed_at ?? date('Y-m-d H:i:s'),
        ]);
        $repair->id = $this->db->lastInsertId();
        return $repair;
    }

    public function update(VehicleRepair $repair): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE vehicle_repairs SET
                breakdown_id = :breakdown_id,
                repair_type = :repair_type,
                repair_item = :repair_item,
                parts_used = :parts_used,
                cost = :cost,
                repairer_id = :repairer_id,
                completed_at = :completed_at
             WHERE id = :id'
        );
        return $stmt->execute([
            'id' => $repair->id,
            'breakdown_id' => $repair->breakdown_id,
            'repair_type' => $repair->repair_type,
            'repair_item' => $repair->repair_item,
            'parts_used' => $repair->parts_used,
            'cost' => $repair->cost,
            'repairer_id' => $repair->repairer_id,
            'completed_at' => $repair->completed_at,
        ]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM vehicle_repairs WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }
}
