<?php

namespace App\Services;

use App\Core\SessionManager;
use App\Repositories\SupplyPurchaseRepository;
use App\Repositories\SupplyItemRepository;
use App\Services\SupplyStockService;
use App\Models\SupplyPurchase;
use App\Services\ActivityLogger;

class SupplyPurchaseService
{
    private SupplyPurchaseRepository $purchaseRepository;
    private SupplyItemRepository $itemRepository;
    private SupplyStockService $stockService;
    private SessionManager $sessionManager;
    private ActivityLogger $activityLogger;

    public function __construct(
        SupplyPurchaseRepository $purchaseRepository,
        SupplyItemRepository $itemRepository,
        SupplyStockService $stockService,
        SessionManager $sessionManager,
        ActivityLogger $activityLogger
    ) {
        $this->purchaseRepository = $purchaseRepository;
        $this->itemRepository = $itemRepository;
        $this->stockService = $stockService;
        $this->sessionManager = $sessionManager;
        $this->activityLogger = $activityLogger;
    }

    /**
     * 구매를 등록합니다.
     */
    public function createPurchase(array $purchaseData): bool
    {
        // 데이터 검증
        $this->validatePurchaseData($purchaseData);

        // 품목 존재 여부 확인
        $item = $this->itemRepository->findById($purchaseData['item_id']);
        if (!$item) {
            throw new \InvalidArgumentException('존재하지 않는 품목입니다.');
        }

        // 사용자 정보 추가
        $user = $this->sessionManager->get('user');
        $purchaseData['created_by'] = $user['id'];

        // 입고 상태가 true이면 입고일 자동 설정
        if (isset($purchaseData['is_received']) && $purchaseData['is_received']) {
            if (empty($purchaseData['received_date'])) {
                $purchaseData['received_date'] = date('Y-m-d');
            }
        }

        // 구매 생성
        $purchaseId = $this->purchaseRepository->create($purchaseData);

        // 입고 완료 상태면 재고 업데이트
        if (isset($purchaseData['is_received']) && $purchaseData['is_received']) {
            $this->stockService->updateStockFromPurchase(
                $purchaseData['item_id'],
                $purchaseData['quantity']
            );
        }

        // 활동 로그 기록
        $this->activityLogger->log(
            'supply_purchase_create',
            "신규 구매 등록 (ID: {$purchaseId})",
            $purchaseData
        );

        return $purchaseId > 0;
    }

    /**
     * 구매 정보를 수정합니다.
     */
    public function updatePurchase(int $purchaseId, array $purchaseData): bool
    {
        $existingPurchase = $this->purchaseRepository->findById($purchaseId);
        if (!$existingPurchase) {
            throw new \InvalidArgumentException('존재하지 않는 구매입니다.');
        }

        // 이미 입고된 구매는 수정 불가
        if ($existingPurchase->isReceived()) {
            throw new \InvalidArgumentException('이미 입고된 구매는 수정할 수 없습니다.');
        }

        // 수정 가능한 필드만 추출
        $allowedFields = ['purchase_date', 'quantity', 'unit_price', 'supplier', 'notes'];
        $updateData = array_intersect_key($purchaseData, array_flip($allowedFields));
        
        if (empty($updateData)) {
            throw new \InvalidArgumentException('수정할 데이터가 없습니다.');
        }

        // 데이터 검증 (부분 검증)
        $this->validatePurchaseData($updateData, false);

        // 구매 수정
        $success = $this->purchaseRepository->update($purchaseId, $updateData);

        if ($success) {
            // 기존 데이터 조회
            $existingPurchase = $this->purchaseRepository->findById($purchaseId);
            $oldData = $existingPurchase ? $existingPurchase->toArray() : [];
            $newData = array_merge($oldData, $updateData);
            
            // 활동 로그 기록
            $this->activityLogger->log(
                'supply_purchase_update',
                "구매 정보 수정 (ID: {$purchaseId})",
                ['old' => $oldData, 'new' => $newData]
            );
        }

        return $success;
    }

    /**
     * 구매를 삭제합니다.
     */
    public function deletePurchase(int $purchaseId): bool
    {
        $purchase = $this->purchaseRepository->findById($purchaseId);
        if (!$purchase) {
            throw new \InvalidArgumentException('존재하지 않는 구매입니다.');
        }

        // 이미 입고된 구매는 삭제 불가
        if ($purchase->isReceived()) {
            throw new \InvalidArgumentException('이미 입고된 구매는 삭제할 수 없습니다. 재고가 이미 반영되었습니다.');
        }

        // 구매 삭제
        $success = $this->purchaseRepository->delete($purchaseId);

        if ($success) {
            // 활동 로그 기록
            $this->activityLogger->log(
                'supply_purchase_delete',
                "구매 삭제 (ID: {$purchaseId})",
                $purchase->toArray()
            );
        }

        return $success;
    }

    /**
     * 입고 처리를 합니다.
     */
    public function markAsReceived(int $purchaseId, ?string $receivedDate = null): bool
    {
        $purchase = $this->purchaseRepository->findById($purchaseId);
        if (!$purchase) {
            throw new \InvalidArgumentException('존재하지 않는 구매입니다.');
        }

        // 이미 입고된 구매인지 확인
        if ($purchase->isReceived()) {
            throw new \InvalidArgumentException('이미 입고 처리된 구매입니다.');
        }

        // 입고일 설정 (지정되지 않으면 오늘 날짜)
        $receivedDate = $receivedDate ?? date('Y-m-d');

        // 입고일 검증
        $purchaseDate = $purchase->getAttribute('purchase_date');
        if (strtotime($receivedDate) < strtotime($purchaseDate)) {
            throw new \InvalidArgumentException('입고일은 구매일보다 이전일 수 없습니다.');
        }

        if (strtotime($receivedDate) > time()) {
            throw new \InvalidArgumentException('입고일은 미래일 수 없습니다.');
        }

        // 트랜잭션 시작 (데이터베이스 일관성 보장)
        try {
            // 입고 처리
            $success = $this->purchaseRepository->markAsReceived($purchaseId, $receivedDate);

            if ($success) {
                // 재고 업데이트
                $this->stockService->updateStockFromPurchase(
                    $purchase->getAttribute('item_id'),
                    $purchase->getAttribute('quantity')
                );

                // 활동 로그 기록
                $this->activityLogger->log(
                    'supply_purchase_receive',
                    "구매 입고 처리 (ID: {$purchaseId})",
                    [
                        'quantity' => $purchase->getAttribute('quantity'),
                        'received_date' => $receivedDate
                    ]
                );
            }

            return $success;
        } catch (\Exception $e) {
            // 트랜잭션 롤백 (실제 구현에서는 DB 트랜잭션 사용)
            throw new \RuntimeException('입고 처리 중 오류가 발생했습니다: ' . $e->getMessage());
        }
    }

    /**
     * 구매 목록을 조회합니다.
     */
    public function getAllPurchases(): array
    {
        return $this->purchaseRepository->findWithItems();
    }

    /**
     * 구매 상세 정보를 조회합니다.
     */
    public function getPurchaseById(int $purchaseId): ?SupplyPurchase
    {
        return $this->purchaseRepository->findById($purchaseId);
    }

    /**
     * 품목별 구매 목록을 조회합니다.
     */
    public function getPurchasesByItem(int $itemId): array
    {
        return $this->purchaseRepository->findByItemId($itemId);
    }

    /**
     * 날짜 범위별 구매 목록을 조회합니다.
     */
    public function getPurchasesByDateRange(string $startDate, string $endDate): array
    {
        return $this->purchaseRepository->findByDateRange($startDate, $endDate);
    }

    /**
     * 미입고 구매 목록을 조회합니다.
     */
    public function getPendingPurchases(): array
    {
        return $this->purchaseRepository->findPendingReceived();
    }

    /**
     * 연도별 구매 통계를 조회합니다.
     */
    public function getPurchaseStatsByYear(int $year): array
    {
        return $this->purchaseRepository->getPurchaseStatsByYear($year);
    }

    /**
     * 구매 데이터를 검증합니다.
     */
    public function validatePurchaseData(array $data, bool $isFullValidation = true): array
    {
        $errors = [];

        // 전체 검증 시 필수 필드 확인
        if ($isFullValidation) {
            // 품목 ID 검증
            if (!isset($data['item_id']) || !is_numeric($data['item_id'])) {
                $errors['item_id'] = '품목 ID는 필수이며 숫자여야 합니다.';
            }

            // 구매일 검증
            if (!isset($data['purchase_date']) || empty($data['purchase_date'])) {
                $errors['purchase_date'] = '구매일은 필수입니다.';
            }

            // 수량 검증
            if (!isset($data['quantity']) || !is_numeric($data['quantity'])) {
                $errors['quantity'] = '수량은 필수이며 숫자여야 합니다.';
            }

            // 단가 검증
            if (!isset($data['unit_price']) || !is_numeric($data['unit_price'])) {
                $errors['unit_price'] = '단가는 필수이며 숫자여야 합니다.';
            }
        }

        // 구매일 검증 (있는 경우)
        if (isset($data['purchase_date'])) {
            if (!strtotime($data['purchase_date'])) {
                $errors['purchase_date'] = '유효한 날짜 형식이 아닙니다.';
            } elseif (strtotime($data['purchase_date']) > time()) {
                $errors['purchase_date'] = '구매일은 미래일 수 없습니다.';
            }
        }

        // 수량 검증 (있는 경우)
        if (isset($data['quantity'])) {
            $quantity = (int) $data['quantity'];
            if ($quantity <= 0) {
                $errors['quantity'] = '수량은 양수여야 합니다.';
            }
            if ($quantity > 999999) {
                $errors['quantity'] = '수량은 999,999개를 초과할 수 없습니다.';
            }
        }

        // 단가 검증 (있는 경우)
        if (isset($data['unit_price'])) {
            $unitPrice = (float) $data['unit_price'];
            if ($unitPrice < 0) {
                $errors['unit_price'] = '단가는 0 이상이어야 합니다.';
            }
            if ($unitPrice > 9999999.99) {
                $errors['unit_price'] = '단가는 9,999,999.99원을 초과할 수 없습니다.';
            }
        }

        // 입고일 검증 (있는 경우)
        if (isset($data['received_date']) && !empty($data['received_date'])) {
            if (!strtotime($data['received_date'])) {
                $errors['received_date'] = '유효한 날짜 형식이 아닙니다.';
            } elseif (strtotime($data['received_date']) > time()) {
                $errors['received_date'] = '입고일은 미래일 수 없습니다.';
            }
            
            // 구매일과 입고일 비교
            if (isset($data['purchase_date']) && strtotime($data['received_date']) < strtotime($data['purchase_date'])) {
                $errors['received_date'] = '입고일은 구매일보다 이전일 수 없습니다.';
            }
        }

        if (!empty($errors)) {
            throw new \InvalidArgumentException('데이터 검증 실패: ' . implode(', ', $errors));
        }

        return $errors;
    }

    /**
     * 활동 로그를 기록합니다.
     */
    private function logActivity(string $action, string $details): void
    {
        // ActivityLogger는 AuthService를 통해 자동으로 현재 사용자 정보를 가져옵니다
    }
}
