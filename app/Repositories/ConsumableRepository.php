<?php

namespace App\Repositories;

use App\Core\Database;

class ConsumableRepository
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function findAll(array $filters = []): array
    {
        $sql = "SELECT * FROM vm_vehicle_consumables";
        return $this->db->query($sql);
    }

    public function findById(int $id): ?array
    {
        $sql = "SELECT * FROM vm_vehicle_consumables WHERE id = ?";
        $result = $this->db->query($sql, [$id]);
        return $result[0] ?? null;
    }

    public function save(array $data): int
    {
        if (isset($data['id'])) {
            $id = $data['id'];
            unset($data['id']);
            $this->db->update('vm_vehicle_consumables', $id, $data);
            return $id;
        } else {
            return $this->db->insert('vm_vehicle_consumables', $data);
        }
    }

    public function delete(int $id): bool
    {
        return $this->db->delete('vm_vehicle_consumables', $id);
    }
}
