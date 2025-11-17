<?php

namespace App\Services;

use App\Core\Database;
use App\Repositories\SupplyDistributionRepository;
use App\Repositories\SupplyItemRepository;
use App\Repositories\EmployeeRepository;
use App\Repositories\DepartmentRepository;
use App\Models\SupplyDistribution;
use App\Services\ActivityLogger;

class SupplyDistributionService
{
    private SupplyDistributionRepository $distributionRepository;
    private SupplyItemRepository $itemRepository;
    private SupplyStockService $stockService;
    private EmployeeRepository $employeeRepository;
    private DepartmentRepository $departmentRepository;
    private ActivityLogger $activityLogger;
    private Database $db;

    public function __construct(
        SupplyDistributionRepository $distributionRepository,
        SupplyItemRepository $itemRepository,
        SupplyStockService $stockService,
        EmployeeRepository $employeeRepository,
        DepartmentRepository $departmentRepository,
        ActivityLogger $activityLogger,
        Database $db
    ) {
        $this->distributionRepository = $distributionRepository;
        $this->itemRepository = $itemRepository;
        $this->stockService = $stockService;
        $this->employeeRepository = $employeeRepository;
        $this->departmentRepository = $departmentRepository;
        $this->activityLogger = $activityLogger;
        $this->db = $db;
    }

    /**
     * 개별 직원에게 지급품을 지급합니다.
     */
    public function distributeToEmployee(int $itemId, int $employeeId, int $departmentId, int $quantity, int $distributedBy, ?string $notes = null): int
    {
        // 지급 데이터 검증
        $this->validateDistribution($itemId, $quantity);

        // 직원 존재 여부 확인
        $this->validateEmployee($employeeId);

        // 부서 존재 여부 확인
        $this->validateDepartment($departmentId);

        // 트랜잭션 시작
        $this->db->beginTransaction();

        try {
            // 지급 기록 생성
            $distributionId = $this->distributionRepository->create([
                'item_id' => $itemId,
                'employee_id' => $employeeId,
                'department_id' => $departmentId,
                'distribution_date' => date('Y-m-d'),
                'quantity' => $quantity,
                'notes' => $notes,
                'distributed_by' => $distributedBy
            ]);

            // 재고 차감
            $this->stockService->updateStockFromDistribution($itemId, $quantity);

            // 감사 로그 기록
            $distributionData = [
                'item_id' => $itemId,
                'employee_id' => $employeeId,
                'department_id' => $departmentId,
                'quantity' => $quantity,
                'distribution_date' => $distributionDate,
                'distributed_by' => $distributedBy
            ];
            $this->activityLogger->logSupplyDistributionCreate($distributionId, $distributionData);

            $this->db->commit();

            return $distributionId;
        } catch (\Exception $e) {
            $this->db->rollback();
            throw new \RuntimeException('지급 처리 중 오류가 발생했습니다: ' . $e->getMessage());
        }
    }

    /**
     * 부서 전체 직원에게 지급품을 지급합니다.
     */
    public function distributeToDepartment(int $itemId, int $departmentId, array $employees, int $distributedBy, ?string $notes = null): array
    {
        if (empty($employees)) {
            throw new \InvalidArgumentException('지급할 직원을 선택해주세요.');
        }

        $results = [];
        $errors = [];

        // 트랜잭션 시작
        $this->db->beginTransaction();

        try {
            foreach ($employees as $employeeData) {
                $employeeId = $employeeData['employee_id'];
                $quantity = $employeeData['quantity'];

                try {
                    // 개별 지급 처리 (트랜잭션 내에서)
                    $distributionId = $this->createDistributionRecord(
                        $itemId,
                        $employeeId,
                        $departmentId,
                        $quantity,
                        $distributedBy,
                        $notes
                    );

                    $results[] = [
                        'employee_id' => $employeeId,
                        'distribution_id' => $distributionId,
                        'success' => true
                    ];
                } catch (\Exception $e) {
                    $errors[] = [
                        'employee_id' => $employeeId,
                        'error' => $e->getMessage()
                    ];
                }
            }

            // 오류가 있으면 롤백
            if (!empty($errors)) {
                $this->db->rollback();
                throw new \RuntimeException('일부 직원에 대한 지급 처리가 실패했습니다.');
            }

            $this->db->commit();

            return $results;
        } catch (\Exception $e) {
            $this->db->rollback();
            throw new \RuntimeException('부서 지급 처리 중 오류가 발생했습니다: ' . $e->getMessage());
        }
    }

    /**
     * 지급을 취소합니다.
     */
    public function cancelDistribution(int $distributionId, int $cancelledBy, string $reason): bool
    {
        // 지급 기록 조회
        $distribution = $this->distributionRepository->findById($distributionId);
        if (!$distribution) {
            throw new \InvalidArgumentException('존재하지 않는 지급 기록입니다.');
        }

        // 이미 취소된 지급인지 확인
        if ($distribution->isCancelled()) {
            throw new \InvalidArgumentException('이미 취소된 지급입니다.');
        }

        // 취소 사유 검증
        if (empty(trim($reason))) {
            throw new \InvalidArgumentException('취소 사유를 입력해주세요.');
        }

        // 트랜잭션 시작
        $this->db->beginTransaction();

        try {
            // 지급 취소 처리
            $success = $this->distributionRepository->cancel($distributionId, $cancelledBy, $reason);

            if (!$success) {
                throw new \RuntimeException('지급 취소 처리에 실패했습니다.');
            }

            // 재고 복원
            $itemId = $distribution->getAttribute('item_id');
            $quantity = $distribution->getQuantity();
            $this->stockService->updateStockFromCancelDistribution($itemId, $quantity);

            // 감사 로그 기록
            $this->activityLogger->logSupplyDistributionCancel($distributionId, $distribution->toArray(), $reason);

            $this->db->commit();

            return true;
        } catch (\Exception $e) {
            $this->db->rollback();
            throw new \RuntimeException('지급 취소 중 오류가 발생했습니다: ' . $e->getMessage());
        }
    }

    /**
     * 지급 정보를 수정합니다.
     */
    public function updateDistribution(int $distributionId, array $data, int $updatedBy): bool
    {
        // 지급 기록 조회
        $distribution = $this->distributionRepository->findById($distributionId);
        if (!$distribution) {
            throw new \InvalidArgumentException('존재하지 않는 지급 기록입니다.');
        }

        // 취소된 지급은 수정 불가
        if ($distribution->isCancelled()) {
            throw new \InvalidArgumentException('취소된 지급은 수정할 수 없습니다.');
        }

        $oldQuantity = $distribution->getQuantity();
        $newQuantity = $data['quantity'] ?? $oldQuantity;
        $itemId = $distribution->getAttribute('item_id');

        // 수량이 변경된 경우 재고 검증
        if ($newQuantity != $oldQuantity) {
            $quantityDiff = $newQuantity - $oldQuantity;
            
            // 수량이 증가한 경우 재고 확인
            if ($quantityDiff > 0) {
                $this->stockService->validateDistribution($itemId, $quantityDiff);
            }
        }

        // 트랜잭션 시작
        $this->db->beginTransaction();

        try {
            // 지급 정보 수정
            $success = $this->distributionRepository->update($distributionId, $data);

            if (!$success) {
                throw new \RuntimeException('지급 정보 수정에 실패했습니다.');
            }

            // 수량이 변경된 경우 재고 조정
            if ($newQuantity != $oldQuantity) {
                $quantityDiff = $newQuantity - $oldQuantity;
                
                if ($quantityDiff > 0) {
                    // 수량 증가: 재고 차감
                    $this->stockService->updateStockFromDistribution($itemId, $quantityDiff);
                } else {
                    // 수량 감소: 재고 복원
                    $this->stockService->updateStockFromCancelDistribution($itemId, abs($quantityDiff));
                }
            }

            // 감사 로그 기록
            $oldData = $distribution->toArray();
            $newData = array_merge($oldData, $updateData);
            $this->activityLogger->logSupplyDistributionUpdate($distributionId, $oldData, $newData);

            $this->db->commit();

            return true;
        } catch (\Exception $e) {
            $this->db->rollback();
            throw new \RuntimeException('지급 정보 수정 중 오류가 발생했습니다: ' . $e->getMessage());
        }
    }

    /**
     * 지급 가능한 품목 목록을 조회합니다.
     */
    public function getAvailableItems(): array
    {
        // 재고가 있는 활성 품목만 조회
        $items = $this->stockService->getItemsWithStock();
        
        return array_map(function($item) {
            return [
                'id' => $item['id'],
                'item_code' => $item['item_code'],
                'item_name' => $item['item_name'],
                'category_name' => $item['category_name'],
                'unit' => $item['unit'],
                'current_stock' => $item['current_stock']
            ];
        }, $items);
    }

    /**
     * 부서별 직원 목록을 조회합니다.
     */
    public function getEmployeesByDepartment(int $departmentId): array
    {
        $sql = "SELECT id, name, position, email 
                FROM hr_employees 
                WHERE department_id = :department_id AND is_active = 1
                ORDER BY name";
        
        return $this->db->query($sql, [':department_id' => $departmentId]);
    }

    /**
     * 지급 내역을 조회합니다.
     */
    public function getDistributions(array $filters = []): array
    {
        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            return $this->distributionRepository->findByDateRange($filters['start_date'], $filters['end_date']);
        }

        if (!empty($filters['employee_id'])) {
            return $this->distributionRepository->findByEmployee($filters['employee_id']);
        }

        if (!empty($filters['department_id'])) {
            return $this->distributionRepository->findByDepartment($filters['department_id']);
        }

        return $this->distributionRepository->findWithRelations();
    }

    /**
     * 지급 통계를 조회합니다.
     */
    public function getDistributionStats(array $filters = []): array
    {
        return $this->distributionRepository->getDistributionStats($filters);
    }

    /**
     * 지급 데이터를 검증합니다.
     */
    public function validateDistribution(int $itemId, int $quantity): void
    {
        // 품목 존재 및 재고 확인
        $this->stockService->validateDistribution($itemId, $quantity);
    }

    /**
     * 직원 존재 여부를 확인합니다.
     */
    private function validateEmployee(int $employeeId): void
    {
        $sql = "SELECT id FROM hr_employees WHERE id = :id AND is_active = 1";
        $result = $this->db->fetchOne($sql, [':id' => $employeeId]);
        
        if (!$result) {
            throw new \InvalidArgumentException('존재하지 않거나 비활성화된 직원입니다.');
        }
    }

    /**
     * 부서 존재 여부를 확인합니다.
     */
    private function validateDepartment(int $departmentId): void
    {
        $sql = "SELECT id FROM hr_departments WHERE id = :id AND is_active = 1";
        $result = $this->db->fetchOne($sql, [':id' => $departmentId]);
        
        if (!$result) {
            throw new \InvalidArgumentException('존재하지 않거나 비활성화된 부서입니다.');
        }
    }

    /**
     * 지급 기록을 생성합니다 (내부용).
     */
    private function createDistributionRecord(int $itemId, int $employeeId, int $departmentId, int $quantity, int $distributedBy, ?string $notes): int
    {
        // 지급 데이터 검증
        $this->validateDistribution($itemId, $quantity);

        // 지급 기록 생성
        $distributionId = $this->distributionRepository->create([
            'item_id' => $itemId,
            'employee_id' => $employeeId,
            'department_id' => $departmentId,
            'distribution_date' => date('Y-m-d'),
            'quantity' => $quantity,
            'notes' => $notes,
            'distributed_by' => $distributedBy
        ]);

        // 재고 차감
        $this->stockService->updateStockFromDistribution($itemId, $quantity);

        return $distributionId;
    }

    /**
     * 활동 로그를 기록합니다.
     */
    private function logActivity(string $action, int $distributionId, int $userId, array $details = []): void
    {
        // ActivityLogger는 AuthService를 통해 자동으로 현재 사용자 정보를 가져옵니다
    }

    /**
     * 부서별 지급 현황을 조회합니다.
     */
    public function getDepartmentDistributionStats(int $departmentId, int $year): array
    {
        return $this->distributionRepository->getDepartmentDistributionStats($departmentId, $year);
    }

    /**
     * 직원별 지급 현황을 조회합니다.
     */
    public function getEmployeeDistributionStats(int $employeeId, int $year): array
    {
        return $this->distributionRepository->getEmployeeDistributionStats($employeeId, $year);
    }

    /**
     * 지급 상세 정보를 조회합니다.
     */
    public function getDistributionById(int $id): ?SupplyDistribution
    {
        return $this->distributionRepository->findById($id);
    }

    /**
     * 모든 부서 목록을 조회합니다.
     */
    public function getAllDepartments(): array
    {
        $sql = "SELECT id, name, code FROM hr_departments WHERE is_active = 1 ORDER BY name";
        return $this->db->query($sql);
    }
}
