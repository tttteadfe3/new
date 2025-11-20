<?php

namespace App\Repositories;

use App\Core\Database;
use App\Models\DistributionDocument;

class DistributionDocumentRepository
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function create(array $data): int
    {
        $sql = "INSERT INTO distribution_documents (title, created_by) VALUES (:title, :created_by)";
        $this->db->execute($sql, [
            ':title' => $data['title'],
            ':created_by' => $data['created_by']
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function addItem(int $documentId, int $itemId, int $quantity): void
    {
        $sql = "INSERT INTO distribution_document_items (document_id, item_id, quantity) VALUES (:document_id, :item_id, :quantity)";
        $this->db->execute($sql, [
            ':document_id' => $documentId,
            ':item_id' => $itemId,
            ':quantity' => $quantity
        ]);
    }

    public function addEmployee(int $documentId, int $employeeId): void
    {
        $sql = "INSERT INTO distribution_document_employees (document_id, employee_id) VALUES (:document_id, :employee_id)";
        $this->db->execute($sql, [
            ':document_id' => $documentId,
            ':employee_id' => $employeeId
        ]);
    }
}
