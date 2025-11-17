<?php

namespace App\Services;

use App\Repositories\SupplyItemRepository;
use App\Repositories\SupplyCategoryRepository;
use App\Services\ActivityLogger;

class SupplyItemService
{
    private SupplyItemRepository $itemRepository;
    private SupplyCategoryRepository $categoryRepository;
    private ActivityLogger $activityLogger;

    public function __construct(
        SupplyItemRepository $itemRepository,
        SupplyCategoryRepository $categoryRepository,
        ActivityLogger $activityLogger
    ) {
        $this->itemRepository = $itemRepository;
        $this->categoryRepository = $categoryRepository;
        $this->activityLogger = $activityLogger;
    }

    /**
     * 모든 품목을 조회합니다.
     */
    public function getAllItems(array $filters = []): array
    {
        return $this->itemRepository->findAll($filters);
    }

    /**
     * 활성 품목만 조회합니다.
     */
    public function getActiveItems(): array
    {
        return $this->itemRepository->findActiveItems();
    }

    /**
     * ID로 품목을 조회합니다.
     */
    public function getItemById(int $id): ?array
    {
        return $this->itemRepository->findById($id);
    }

    /**
     * 분류별 품목을 조회합니다.
     */
    public function getItemsByCategory(int $categoryId): array
    {
        return $this->itemRepository->findByCategoryId($categoryId);
    }

    /**
     * 새로운 품목을 생성합니다.
     */
    public function createItem(array $data): int
    {
        // 데이터 검증
        $this->validateItemData($data);

        // 품목 코드 중복 검사
        if ($this->itemRepository->isDuplicateCode($data['item_code'])) {
            throw new \InvalidArgumentException('이미 존재하는 품목 코드입니다.');
        }

        // 분류 존재 여부 확인
        $category = $this->categoryRepository->findById($data['category_id']);
        if (!$category) {
            throw new \InvalidArgumentException('존재하지 않는 분류입니다.');
        }

        // 품목 생성
        $itemId = $this->itemRepository->create($data);

        // 활동 로그 기록
        $this->activityLogger->log('supply_item_create', "품목 생성: {$data['item_name']} (코드: {$data['item_code']})", [
            'item_id' => $itemId,
            'item_code' => $data['item_code'],
            'item_name' => $data['item_name']
        ]);

        return $itemId;
    }

    /**
     * 품목을 수정합니다.
     */
    public function updateItem(int $id, array $data): bool
    {
        $existingItem = $this->itemRepository->findById($id);
        if (!$existingItem) {
            throw new \InvalidArgumentException('존재하지 않는 품목입니다.');
        }

        // 품목 코드 중복 검사 (자신 제외)
        if (isset($data['item_code']) && $this->itemRepository->isDuplicateCode($data['item_code'], $id)) {
            throw new \InvalidArgumentException('이미 존재하는 품목 코드입니다.');
        }

        // 분류 존재 여부 확인
        if (isset($data['category_id'])) {
            $category = $this->categoryRepository->findById($data['category_id']);
            if (!$category) {
                throw new \InvalidArgumentException('존재하지 않는 분류입니다.');
            }
        }

        // 허용된 필드만 업데이트
        $allowedFields = ['item_code', 'item_name', 'category_id', 'unit', 'description', 'is_active'];
        $updateData = array_intersect_key($data, array_flip($allowedFields));

        if (empty($updateData)) {
            throw new \InvalidArgumentException('수정할 데이터가 없습니다.');
        }

        // 품목 수정
        $success = $this->itemRepository->update($id, $updateData);

        if ($success) {
            // 활동 로그 기록
            $this->activityLogger->log('supply_item_update', "품목 수정: {$existingItem['item_name']}", [
                'item_id' => $id,
                'old_data' => $existingItem,
                'new_data' => $updateData
            ]);
        }

        return $success;
    }

    /**
     * 품목을 삭제합니다.
     */
    public function deleteItem(int $id): bool
    {
        $item = $this->itemRepository->findById($id);
        if (!$item) {
            throw new \InvalidArgumentException('존재하지 않는 품목입니다.');
        }

        // 연관 데이터 검증
        if ($this->hasAssociatedData($id)) {
            throw new \InvalidArgumentException('연관된 데이터가 있어 삭제할 수 없습니다. 비활성화를 권장합니다.');
        }

        // 품목 삭제
        $success = $this->itemRepository->delete($id);

        if ($success) {
            // 활동 로그 기록
            $this->activityLogger->log('supply_item_delete', "품목 삭제: {$item['item_name']}", [
                'item_id' => $id,
                'item_data' => $item
            ]);
        }

        return $success;
    }

    /**
     * 품목 상태를 변경합니다 (활성/비활성).
     */
    public function toggleItemStatus(int $id): bool
    {
        $item = $this->itemRepository->findById($id);
        if (!$item) {
            throw new \InvalidArgumentException('존재하지 않는 품목입니다.');
        }

        $currentStatus = $item['is_active'];
        $newStatus = $currentStatus ? 0 : 1;

        $success = $this->itemRepository->update($id, ['is_active' => $newStatus]);

        if ($success) {
            $statusText = $newStatus ? '활성' : '비활성';
            $this->activityLogger->log('supply_item_status_change', "품목 상태 변경: {$item['item_name']} → {$statusText}", [
                'item_id' => $id,
                'old_status' => $currentStatus,
                'new_status' => $newStatus
            ]);
        }

        return $success;
    }

    /**
     * 다음 품목 코드를 생성합니다.
     */
    public function generateNextCode(): string
    {
        return $this->itemRepository->generateNextCode();
    }

    /**
     * 품목에 연관된 데이터가 있는지 확인합니다.
     */
    private function hasAssociatedData(int $itemId): bool
    {
        return $this->itemRepository->hasAssociatedPlans($itemId)
            || $this->itemRepository->hasAssociatedPurchases($itemId)
            || $this->itemRepository->hasAssociatedDistributions($itemId);
    }

    /**
     * 품목 데이터를 검증합니다.
     */
    private function validateItemData(array $data): void
    {
        // 필수 필드 검증
        $requiredFields = ['item_code', 'item_name', 'category_id'];
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                throw new \InvalidArgumentException("{$field}은(는) 필수입니다.");
            }
        }

        // 품목 코드 형식 검증
        if (!preg_match('/^[A-Z0-9]{3,30}$/', $data['item_code'])) {
            throw new \InvalidArgumentException('품목 코드는 3-30자의 영문 대문자와 숫자만 사용할 수 있습니다.');
        }

        // 품목명 길이 검증
        if (strlen($data['item_name']) > 200) {
            throw new \InvalidArgumentException('품목명은 200자를 초과할 수 없습니다.');
        }

        // 단위 길이 검증
        if (isset($data['unit']) && strlen($data['unit']) > 20) {
            throw new \InvalidArgumentException('단위는 20자를 초과할 수 없습니다.');
        }
    }
}
