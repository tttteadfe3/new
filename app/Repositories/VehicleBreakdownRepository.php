<?php

namespace App\Repositories;

use App\Models\VehicleBreakdown;
use PDO;

class VehicleBreakdownRepository
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function find(int $id): ?VehicleBreakdown
    {
        $stmt = $this->db->prepare('SELECT * FROM vehicle_breakdowns WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        return $data ? new VehicleBreakdown($data) : null;
    }

    public function findByVehicleId(int $vehicleId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM vehicle_breakdowns WHERE vehicle_id = :vehicle_id ORDER BY reported_at DESC');
        $stmt->execute(['vehicle_id' => $vehicleId]);
        return array_map(fn($data) => new VehicleBreakdown($data), $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function findAll(): array
    {
        $stmt = $this->db->query('SELECT * FROM vehicle_breakdowns ORDER BY reported_at DESC');
        return array_map(fn($data) => new VehicleBreakdown($data), $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function create(VehicleBreakdown $breakdown): VehicleBreakdown
    {
        $stmt = $this->db->prepare(
            'INSERT INTO vehicle_breakdowns (vehicle_id, reporter_id, breakdown_item, description, mileage, status, reported_at)
             VALUES (:vehicle_id, :reporter_id, :breakdown_item, :description, :mileage, :status, :reported_at)'
        );
        $stmt->execute([
            'vehicle_id' => $breakdown->vehicle_id,
            'reporter_id' => $breakdown->reporter_id,
            'breakdown_item' => $breakdown->breakdown_item,
            'description' => $breakdown->description,
            'mileage' => $breakdown->mileage,
            'status' => $breakdown->status ?? 'reported',
            'reported_at' => $breakdown->reported_at ?? date('Y-m-d H:i:s'),
        ]);
        $breakdown->id = $this->db->lastInsertId();
        return $breakdown;
    }

    public function update(VehicleBreakdown $breakdown): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE vehicle_breakdowns SET
                vehicle_id = :vehicle_id,
                reporter_id = :reporter_id,
                breakdown_item = :breakdown_item,
                description = :description,
                mileage = :mileage,
                status = :status,
                confirmed_at = :confirmed_at,
                resolved_at = :resolved_at,
                approved_at = :approved_at
             WHERE id = :id'
        );
        return $stmt->execute([
            'id' => $breakdown->id,
            'vehicle_id' => $breakdown->vehicle_id,
            'reporter_id' => $breakdown->reporter_id,
            'breakdown_item' => $breakdown->breakdown_item,
            'description' => $breakdown->description,
            'mileage' => $breakdown->mileage,
            'status' => $breakdown->status,
            'confirmed_at' => $breakdown->confirmed_at,
            'resolved_at' => $breakdown->resolved_at,
            'approved_at' => $breakdown->approved_at,
        ]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM vehicle_breakdowns WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }
}
