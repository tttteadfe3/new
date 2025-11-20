<?php

namespace App\Services;

use App\Core\Database;
use App\Repositories\DistributionDocumentRepository;
use App\Services\SupplyStockService;
use App\Services\ActivityLogger;

class DistributionDocumentService
{
    private DistributionDocumentRepository $documentRepository;
    private SupplyStockService $stockService;
    private ActivityLogger $activityLogger;
    private Database $db;

    public function __construct(
        DistributionDocumentRepository $documentRepository,
        SupplyStockService $stockService,
        ActivityLogger $activityLogger,
        Database $db
    ) {
        $this->documentRepository = $documentRepository;
        $this->stockService = $stockService;
        $this->activityLogger = $activityLogger;
        $this->db = $db;
    }

    public function createDocument(array $data, int $userId): int
    {
        $this->db->beginTransaction();

        try {
            $documentId = $this->documentRepository->create([
                'title' => $data['title'],
                'created_by' => $userId
            ]);

            foreach ($data['items'] as $item) {
                $this->stockService->validateDistribution($item['id'], $item['quantity']);
                $this->documentRepository->addItem($documentId, $item['id'], $item['quantity']);
                $this->stockService->updateStockFromDistribution($item['id'], $item['quantity']);
            }

            foreach ($data['employees'] as $employeeId) {
                $this->documentRepository->addEmployee($documentId, $employeeId);
            }

            $this->db->commit();

            return $documentId;
        } catch (\Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
}
