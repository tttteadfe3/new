<?php

namespace App\Repositories;

use App\Models\VehicleInsurance;
use PDO;

class VehicleInsuranceRepository
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function find(int $id): ?VehicleInsurance
    {
        $stmt = $this->db->prepare('SELECT * FROM vehicle_insurances WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        return $data ? new VehicleInsurance($data) : null;
    }

    public function findByVehicleId(int $vehicleId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM vehicle_insurances WHERE vehicle_id = :vehicle_id ORDER BY end_date DESC');
        $stmt->execute(['vehicle_id' => $vehicleId]);
        return array_map(fn($data) => new VehicleInsurance($data), $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function create(VehicleInsurance $insurance): VehicleInsurance
    {
        $stmt = $this->db->prepare(
            'INSERT INTO vehicle_insurances (vehicle_id, insurer, policy_number, start_date, end_date, premium, document_path)
             VALUES (:vehicle_id, :insurer, :policy_number, :start_date, :end_date, :premium, :document_path)'
        );
        $stmt->execute([
            'vehicle_id' => $insurance->vehicle_id,
            'insurer' => $insurance->insurer,
            'policy_number' => $insurance->policy_number,
            'start_date' => $insurance->start_date,
            'end_date' => $insurance->end_date,
            'premium' => $insurance->premium,
            'document_path' => $insurance->document_path,
        ]);
        $insurance->id = $this->db->lastInsertId();
        return $insurance;
    }

    public function update(VehicleInsurance $insurance): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE vehicle_insurances SET
                vehicle_id = :vehicle_id, insurer = :insurer, policy_number = :policy_number,
                start_date = :start_date, end_date = :end_date, premium = :premium,
                document_path = :document_path
             WHERE id = :id'
        );
        return $stmt->execute([
            'id' => $insurance->id,
            'vehicle_id' => $insurance->vehicle_id,
            'insurer' => $insurance->insurer,
            'policy_number' => $insurance->policy_number,
            'start_date' => $insurance->start_date,
            'end_date' => $insurance->end_date,
            'premium' => $insurance->premium,
            'document_path' => $insurance->document_path,
        ]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM vehicle_insurances WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }
}
