<?php

namespace App\Repositories;

use App\Models\VehicleTax;
use PDO;

class VehicleTaxRepository
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function find(int $id): ?VehicleTax
    {
        $stmt = $this->db->prepare('SELECT * FROM vehicle_taxes WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        return $data ? new VehicleTax($data) : null;
    }

    public function findByVehicleId(int $vehicleId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM vehicle_taxes WHERE vehicle_id = :vehicle_id ORDER BY payment_date DESC');
        $stmt->execute(['vehicle_id' => $vehicleId]);
        return array_map(fn($data) => new VehicleTax($data), $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function create(VehicleTax $tax): VehicleTax
    {
        $stmt = $this->db->prepare(
            'INSERT INTO vehicle_taxes (vehicle_id, tax_type, payment_date, amount, year)
             VALUES (:vehicle_id, :tax_type, :payment_date, :amount, :year)'
        );
        $stmt->execute([
            'vehicle_id' => $tax->vehicle_id,
            'tax_type' => $tax->tax_type,
            'payment_date' => $tax->payment_date,
            'amount' => $tax->amount,
            'year' => $tax->year,
        ]);
        $tax->id = $this->db->lastInsertId();
        return $tax;
    }

    public function update(VehicleTax $tax): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE vehicle_taxes SET
                vehicle_id = :vehicle_id, tax_type = :tax_type, payment_date = :payment_date,
                amount = :amount, year = :year
             WHERE id = :id'
        );
        return $stmt->execute([
            'id' => $tax->id,
            'vehicle_id' => $tax->vehicle_id,
            'tax_type' => $tax->tax_type,
            'payment_date' => $tax->payment_date,
            'amount' => $tax->amount,
            'year' => $tax->year,
        ]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM vehicle_taxes WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }
}
