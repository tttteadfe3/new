<?php

namespace App\Repositories;

use App\Models\VehicleConsumableLog;
use PDO;

class VehicleConsumableLogRepository
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function find(int $id): ?VehicleConsumableLog
    {
        $stmt = $this->db->prepare('SELECT * FROM vehicle_consumable_logs WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        return $data ? new VehicleConsumableLog($data) : null;
    }

    public function findByVehicleId(int $vehicleId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM vehicle_consumable_logs WHERE vehicle_id = :vehicle_id ORDER BY replacement_date DESC');
        $stmt->execute(['vehicle_id' => $vehicleId]);
        return array_map(fn($data) => new VehicleConsumableLog($data), $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function findByConsumableId(int $consumableId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM vehicle_consumable_logs WHERE consumable_id = :consumable_id ORDER BY replacement_date DESC');
        $stmt->execute(['consumable_id' => $consumableId]);
        return array_map(fn($data) => new VehicleConsumableLog($data), $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function create(VehicleConsumableLog $log): VehicleConsumableLog
    {
        $stmt = $this->db->prepare(
            'INSERT INTO vehicle_consumable_logs (vehicle_id, consumable_id, quantity, replacement_date, replacer_id)
             VALUES (:vehicle_id, :consumable_id, :quantity, :replacement_date, :replacer_id)'
        );
        $stmt->execute([
            'vehicle_id' => $log->vehicle_id,
            'consumable_id' => $log->consumable_id,
            'quantity' => $log->quantity,
            'replacement_date' => $log->replacement_date,
            'replacer_id' => $log->replacer_id,
        ]);
        $log->id = $this->db->lastInsertId();
        return $log;
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM vehicle_consumable_logs WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }
}
