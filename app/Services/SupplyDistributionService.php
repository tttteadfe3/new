<?php

namespace App\Services;

use App\Core\Database;
use App\Repositories\SupplyDistributionRepository;
use App\Services\SupplyStockService;
use App\Services\ActivityLogger;

class SupplyDistributionService
{
    private SupplyDistributionRepository $distributionRepository;
    private SupplyStockService $stockService;
    private ActivityLogger $activityLogger;
    private Database $db;

    public function __construct(
        SupplyDistributionRepository $distributionRepository,
        SupplyStockService $stockService,
        ActivityLogger $activityLogger,
        Database $db
    ) {
        $this->distributionRepository = $distributionRepository;
        $this->stockService = $stockService;
        $this->activityLogger = $activityLogger;
        $this->db = $db;
    }

    public function createDocument(array $data, int $employeeId): int
    {
        $this->db->beginTransaction();

        try {
            // 직원 수 계산
            $employeeCount = count($data['employees']);
            
            if ($employeeCount === 0) {
                throw new \InvalidArgumentException('지급받을 직원을 선택해주세요.');
            }

            $documentId = $this->distributionRepository->create([
                'title' => $data['title'],
                'distribution_date' => $data['distribution_date'] ?? date('Y-m-d'),
                'created_by' => $employeeId,
                'status' => '완료'
            ]);

            // 품목별 처리: 각 품목의 수량 × 직원 수 = 실제 차감 수량
            foreach ($data['items'] as $item) {
                $quantityPerEmployee = $item['quantity']; // 직원 1인당 수량
                $totalQuantity = $quantityPerEmployee * $employeeCount; // 총 차감 수량
                
                // 재고 검증 (총 수량 기준, 지급일자 기준)
                $this->stockService->validateDistribution($item['id'], $totalQuantity, $data['distribution_date']);
                
                // 문서에 품목 저장 (1인당 수량 저장)
                $this->distributionRepository->addItem($documentId, $item['id'], $quantityPerEmployee);
                
                // 재고 차감 (총 수량 차감)
                $this->stockService->updateStockFromDistribution($item['id'], $totalQuantity);
            }

            // 직원별 지급 정보 저장
            foreach ($data['employees'] as $empId) {
                $this->distributionRepository->addEmployee($documentId, $empId);
            }

            $this->db->commit();

            return $documentId;
        } catch (\Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    public function getDocuments(array $filters = []): array
    {
        $documents = $this->distributionRepository->getAll($filters);

        foreach ($documents as &$doc) {
            $doc['items'] = $this->distributionRepository->getDocumentItems($doc['id']);
            $doc['employee_count'] = count($this->distributionRepository->getDocumentEmployees($doc['id']));
        }

        return $documents;
    }

    public function getDocumentById(int $id): ?array
    {
        $document = $this->distributionRepository->getById($id);
        
        if ($document) {
            $document['items'] = $this->distributionRepository->getDocumentItems($id);
            $document['employees'] = $this->distributionRepository->getDocumentEmployees($id);
        }
        
        return $document;
    }

    public function getAvailableItems(): array
    {
        // Get items with available stock from SupplyStockService
        return $this->stockService->findItemsWithStock();
    }

    public function updateDocument(int $id, array $data, int $employeeId): void
    {
        $this->db->beginTransaction();

        try {
            // 기존 문서 조회
            $document = $this->distributionRepository->getById($id);
            if (!$document) {
                throw new \InvalidArgumentException('존재하지 않는 문서입니다.');
            }

            // 이미 취소된 문서인지 확인
            if (($document['status'] ?? '완료') === '취소') {
                throw new \InvalidArgumentException('이미 취소된 문서는 수정할 수 없습니다.');
            }

            // 1. 기존 재고 복원 (기존 아이템 수량 * 기존 직원 수)
            $oldItems = $this->distributionRepository->getDocumentItems($id);
            $oldEmployees = $this->distributionRepository->getDocumentEmployees($id);
            $oldEmployeeCount = count($oldEmployees);

            foreach ($oldItems as $item) {
                $totalQuantity = $item['quantity'] * $oldEmployeeCount;
                $this->stockService->updateStockFromCancelDistribution($item['item_id'], $totalQuantity);
            }

            // 2. 기존 아이템 및 직원 삭제
            $this->distributionRepository->deleteDocumentItems($id);
            $this->distributionRepository->deleteDocumentEmployees($id);

            // 3. 문서 정보 업데이트
            $this->distributionRepository->update($id, [
                'title' => $data['title'],
                'distribution_date' => $data['distribution_date']
            ]);

            // 4. 새 아이템 및 직원 추가, 재고 차감
            $employeeIds = $data['employees'];
            $items = $data['items'];
            $newEmployeeCount = count($employeeIds);

            foreach ($items as $item) {
                $totalQuantity = $item['quantity'] * $newEmployeeCount;
                
                // 재고 확인 및 차감 (지급일자 기준)
                $this->stockService->validateDistribution($item['id'], $totalQuantity, $data['distribution_date']);
                $this->stockService->updateStockFromDistribution($item['id'], $totalQuantity);

                $this->distributionRepository->addItem($id, $item['id'], $item['quantity']);
            }

            foreach ($employeeIds as $empId) {
                $this->distributionRepository->addEmployee($id, $empId);
            }

            $this->db->commit();
            
            // 로그 기록
            $this->activityLogger->log('supply_distribution_update', "지급 문서 수정: {$data['title']} (ID: {$id})");

        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function deleteDocument(int $id): void
    {
        $this->db->beginTransaction();

        try {
            $document = $this->distributionRepository->getById($id);
            if (!$document) {
                throw new \InvalidArgumentException('존재하지 않는 문서입니다.');
            }

            // 이미 취소된 문서는 삭제 불가 (정책에 따라 다를 수 있음, 여기서는 허용하되 재고 복원은 취소 여부에 따라 다르게 처리)
            // 하지만 취소된 문서는 이미 재고가 복원되었으므로, 삭제 시 중복 복원을 막아야 함.
            $isCancelled = ($document['status'] ?? '완료') === '취소';

            if (!$isCancelled) {
                // 취소되지 않은 문서만 재고 복원 수행
                $items = $this->distributionRepository->getDocumentItems($id);
                $employees = $this->distributionRepository->getDocumentEmployees($id);
                $employeeCount = count($employees);

                foreach ($items as $item) {
                    $totalQuantity = $item['quantity'] * $employeeCount;
                    $this->stockService->updateStockFromCancelDistribution($item['item_id'], $totalQuantity);
                }
            }

            $this->distributionRepository->deleteDocumentItems($id);
            $this->distributionRepository->deleteDocumentEmployees($id);
            $this->distributionRepository->delete($id);

            $this->db->commit();
            
            $this->activityLogger->log('supply_distribution_delete', "지급 문서 삭제: ID {$id}");

        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function cancelDocument(int $id, string $cancelReason): void
    {
        $this->db->beginTransaction();

        try {
            $document = $this->distributionRepository->getById($id);
            if (!$document) {
                throw new \InvalidArgumentException('존재하지 않는 문서입니다.');
            }

            if (($document['status'] ?? '완료') === '취소') {
                throw new \InvalidArgumentException('이미 취소된 문서입니다.');
            }

            // 재고 복원
            $items = $this->distributionRepository->getDocumentItems($id);
            $employees = $this->distributionRepository->getDocumentEmployees($id);
            $employeeCount = count($employees);

            foreach ($items as $item) {
                $totalQuantity = $item['quantity'] * $employeeCount;
                $this->stockService->updateStockFromCancelDistribution($item['item_id'], $totalQuantity);
            }

            // 상태 업데이트
            $this->distributionRepository->updateStatus($id, '취소', $cancelReason);

            $this->db->commit();
            
            $this->activityLogger->log('supply_distribution_cancel', "지급 문서 취소: ID {$id}");

        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}
