<?php

namespace App\Repositories;

use App\Core\Database;


class SupplyDistributionRepository
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function create(array $data): int
    {
        $sql = "INSERT INTO supply_distribution_documents (title, distribution_date, created_by) VALUES (:title, :distribution_date, :created_by)";
        $this->db->execute($sql, [
            ':title' => $data['title'],
            ':distribution_date' => $data['distribution_date'] ?? date('Y-m-d'),
            ':created_by' => $data['created_by']
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function addItem(int $documentId, int $itemId, int $quantity): void
    {
        $sql = "INSERT INTO supply_distribution_document_items (document_id, item_id, quantity) VALUES (:document_id, :item_id, :quantity)";
        $this->db->execute($sql, [
            ':document_id' => $documentId,
            ':item_id' => $itemId,
            ':quantity' => $quantity
        ]);
    }

    public function addEmployee(int $documentId, int $employeeId): void
    {
        $sql = "INSERT INTO supply_distribution_document_employees (document_id, employee_id) VALUES (:document_id, :employee_id)";
        $this->db->execute($sql, [
            ':document_id' => $documentId,
            ':employee_id' => $employeeId
        ]);
    }

    public function getAll(array $filters = []): array
    {
        $sql = "SELECT 
                    d.id,
                    d.title,
                    d.distribution_date,
                    d.status,
                    d.created_at,
                    d.created_by,
                    e.name as created_by_name
                FROM supply_distribution_documents d
                LEFT JOIN hr_employees e ON d.created_by = e.id
                WHERE 1=1";
        
        $params = [];
        
        if (!empty($filters['search'])) {
            $sql .= " AND d.title LIKE :search";
            $params[':search'] = '%' . $filters['search'] . '%';
        }
        
        $sql .= " ORDER BY d.created_at DESC";
        
        return $this->db->fetchAll($sql, $params);
    }

    public function getById(int $id): ?array
    {
        $sql = "SELECT 
                    d.id,
                    d.title,
                    d.distribution_date,
                    d.status,
                    d.cancel_reason,
                    d.created_at,
                    d.created_by,
                    e.name as created_by_name
                FROM supply_distribution_documents d
                LEFT JOIN hr_employees e ON d.created_by = e.id
                WHERE d.id = :id";
        
        return $this->db->fetchOne($sql, [':id' => $id]);
    }

    public function getDocumentItems(int $documentId): array
    {
        $sql = "SELECT 
                    di.id,
                    di.item_id,
                    di.quantity,
                    si.item_name,
                    si.item_code
                FROM supply_distribution_document_items di
                LEFT JOIN supply_items si ON di.item_id = si.id
                WHERE di.document_id = :document_id";
        
        return $this->db->fetchAll($sql, [':document_id' => $documentId]);
    }

    public function getDocumentEmployees(int $documentId): array
    {
        $sql = "SELECT 
                    de.id,
                    de.employee_id,
                    e.name as employee_name,
                    e.department_id,
                    d.name as department_name
                FROM supply_distribution_document_employees de
                LEFT JOIN hr_employees e ON de.employee_id = e.id
                LEFT JOIN hr_departments d ON e.department_id = d.id
                WHERE de.document_id = :document_id";
        
        return $this->db->fetchAll($sql, [':document_id' => $documentId]);
    }

    public function update(int $id, array $data): bool
    {
        $sql = "UPDATE supply_distribution_documents 
                SET title = :title, 
                    distribution_date = :distribution_date 
                WHERE id = :id";
        
        return $this->db->execute($sql, [
            ':id' => $id,
            ':title' => $data['title'],
            ':distribution_date' => $data['distribution_date']
        ]) > 0;
    }

    public function delete(int $id): bool
    {
        $sql = "DELETE FROM supply_distribution_documents WHERE id = :id";
        return $this->db->execute($sql, [':id' => $id]) > 0;
    }

    public function deleteDocumentItems(int $documentId): bool
    {
        $sql = "DELETE FROM supply_distribution_document_items WHERE document_id = :document_id";
        return $this->db->execute($sql, [':document_id' => $documentId]) > 0;
    }

    public function deleteDocumentEmployees(int $documentId): bool
    {
        $sql = "DELETE FROM supply_distribution_document_employees WHERE document_id = :document_id";
        return $this->db->execute($sql, [':document_id' => $documentId]) > 0;
    }

    public function updateStatus(int $id, string $status, string $cancelReason = null): bool
    {
        $sql = "UPDATE supply_distribution_documents 
                SET status = :status, 
                    cancel_reason = :cancel_reason,
                    cancelled_at = " . ($status === '취소' ? 'NOW()' : 'NULL') . "
                WHERE id = :id";
        
        return $this->db->execute($sql, [
            ':id' => $id,
            ':status' => $status,
            ':cancel_reason' => $cancelReason
        ]) > 0;
    }

    /**
     * 특정 품목의 지급 내역을 조회합니다.
     */
    public function getDistributionsByItem(int $itemId, ?int $limit = null): array
    {
        $sql = "SELECT 
                    d.id,
                    d.title,
                    d.distribution_date,
                    d.status,
                    d.created_at,
                    d.created_by,
                    e.name as created_by_name,
                    di.quantity,
                    si.item_name,
                    si.item_code
                FROM supply_distribution_documents d
                INNER JOIN supply_distribution_document_items di ON d.id = di.document_id
                INNER JOIN supply_items si ON di.item_id = si.id
                LEFT JOIN hr_employees e ON d.created_by = e.id
                WHERE di.item_id = :item_id
                AND d.status != '취소'
                ORDER BY d.distribution_date DESC, d.created_at DESC";
        
        if ($limit) {
            $sql .= " LIMIT " . (int)$limit;
        }
        
        return $this->db->fetchAll($sql, [':item_id' => $itemId]);
    }
}
