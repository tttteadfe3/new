<?php

namespace App\Repositories;

use App\Models\VehicleConsumable;
use PDO;

class VehicleConsumableRepository
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function find(int $id): ?VehicleConsumable
    {
        $stmt = $this->db->prepare('SELECT * FROM vehicle_consumables WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        return $data ? new VehicleConsumable($data) : null;
    }

    public function findAll(): array
    {
        $stmt = $this->db->query('SELECT * FROM vehicle_consumables ORDER BY name');
        return array_map(fn($data) => new VehicleConsumable($data), $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function create(VehicleConsumable $consumable): VehicleConsumable
    {
        $stmt = $this->db->prepare(
            'INSERT INTO vehicle_consumables (name, unit, unit_price) VALUES (:name, :unit, :unit_price)'
        );
        $stmt->execute([
            'name' => $consumable->name,
            'unit' => $consumable->unit,
            'unit_price' => $consumable->unit_price,
        ]);
        $consumable->id = $this->db->lastInsertId();
        return $consumable;
    }

    public function update(VehicleConsumable $consumable): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE vehicle_consumables SET name = :name, unit = :unit, unit_price = :unit_price WHERE id = :id'
        );
        return $stmt->execute([
            'id' => $consumable->id,
            'name' => $consumable->name,
            'unit' => $consumable->unit,
            'unit_price' => $consumable->unit_price,
        ]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM vehicle_consumables WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }
}
