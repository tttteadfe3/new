<?php

namespace App\Repositories;

use App\Models\VehicleInspection;
use PDO;

class VehicleInspectionRepository
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function find(int $id): ?VehicleInspection
    {
        $stmt = $this->db->prepare('SELECT * FROM vehicle_inspections WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        return $data ? new VehicleInspection($data) : null;
    }

    public function findByVehicleId(int $vehicleId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM vehicle_inspections WHERE vehicle_id = :vehicle_id ORDER BY inspection_date DESC');
        $stmt->execute(['vehicle_id' => $vehicleId]);
        return array_map(fn($data) => new VehicleInspection($data), $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function create(VehicleInspection $inspection): VehicleInspection
    {
        $stmt = $this->db->prepare(
            'INSERT INTO vehicle_inspections (vehicle_id, inspection_date, expiry_date, result, inspector)
             VALUES (:vehicle_id, :inspection_date, :expiry_date, :result, :inspector)'
        );
        $stmt->execute([
            'vehicle_id' => $inspection->vehicle_id,
            'inspection_date' => $inspection->inspection_date,
            'expiry_date' => $inspection->expiry_date,
            'result' => $inspection->result,
            'inspector' => $inspection->inspector,
        ]);
        $inspection->id = $this->db->lastInsertId();
        return $inspection;
    }

    public function update(VehicleInspection $inspection): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE vehicle_inspections SET
                vehicle_id = :vehicle_id, inspection_date = :inspection_date,
                expiry_date = :expiry_date, result = :result, inspector = :inspector
             WHERE id = :id'
        );
        return $stmt->execute([
            'id' => $inspection->id,
            'vehicle_id' => $inspection->vehicle_id,
            'inspection_date' => $inspection->inspection_date,
            'expiry_date' => $inspection->expiry_date,
            'result' => $inspection->result,
            'inspector' => $inspection->inspector,
        ]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM vehicle_inspections WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }
}
