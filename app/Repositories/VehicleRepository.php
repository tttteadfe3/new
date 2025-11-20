<?php

namespace App\Repositories;

use App\Models\Vehicle;
use PDO;

class VehicleRepository
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function find(int $id): ?Vehicle
    {
        $stmt = $this->db->prepare('SELECT * FROM vehicles WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        return $data ? new Vehicle($data) : null;
    }

    public function findAll(): array
    {
        $stmt = $this->db->query('SELECT * FROM vehicles');
        return array_map(fn($data) => new Vehicle($data), $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function create(Vehicle $vehicle): Vehicle
    {
        $stmt = $this->db->prepare(
            'INSERT INTO vehicles (vin, license_plate, make, model, year, department_id, status)
             VALUES (:vin, :license_plate, :make, :model, :year, :department_id, :status)'
        );
        $stmt->execute([
            'vin' => $vehicle->vin,
            'license_plate' => $vehicle->license_plate,
            'make' => $vehicle->make,
            'model' => $vehicle->model,
            'year' => $vehicle->year,
            'department_id' => $vehicle->department_id,
            'status' => $vehicle->status ?? 'active',
        ]);
        $vehicle->id = $this->db->lastInsertId();
        return $vehicle;
    }

    public function update(Vehicle $vehicle): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE vehicles SET
                vin = :vin,
                license_plate = :license_plate,
                make = :make,
                model = :model,
                year = :year,
                department_id = :department_id,
                status = :status
             WHERE id = :id'
        );
        return $stmt->execute([
            'id' => $vehicle->id,
            'vin' => $vehicle->vin,
            'license_plate' => $vehicle->license_plate,
            'make' => $vehicle->make,
            'model' => $vehicle->model,
            'year' => $vehicle->year,
            'department_id' => $vehicle->department_id,
            'status' => $vehicle->status,
        ]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM vehicles WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }
}
