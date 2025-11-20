<?php

namespace App\Repositories;

use App\Models\VehicleDocument;
use PDO;

class VehicleDocumentRepository
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function find(int $id): ?VehicleDocument
    {
        $stmt = $this->db->prepare('SELECT * FROM vehicle_documents WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        return $data ? new VehicleDocument($data) : null;
    }

    public function findByVehicleId(int $vehicleId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM vehicle_documents WHERE vehicle_id = :vehicle_id ORDER BY uploaded_at DESC');
        $stmt->execute(['vehicle_id' => $vehicleId]);
        return array_map(fn($data) => new VehicleDocument($data), $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function create(VehicleDocument $document): VehicleDocument
    {
        $stmt = $this->db->prepare(
            'INSERT INTO vehicle_documents (vehicle_id, document_type, file_path)
             VALUES (:vehicle_id, :document_type, :file_path)'
        );
        $stmt->execute([
            'vehicle_id' => $document->vehicle_id,
            'document_type' => $document->document_type,
            'file_path' => $document->file_path,
        ]);
        $document->id = $this->db->lastInsertId();
        return $document;
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM vehicle_documents WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }
}
