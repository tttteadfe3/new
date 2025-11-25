<?php

namespace App\Services;

use App\Repositories\VehicleConsumableRepository;
use InvalidArgumentException;

class VehicleConsumableService
{
    private VehicleConsumableRepository $repository;

    public function __construct(VehicleConsumableRepository $repository)
    {
        $this->repository = $repository;
    }

    // ============ 카테고리 관리 ============

    /**
     * 모든 카테고리 조회
     */
    public function getAllCategories(array $filters = []): array
    {
        return $this->repository->findAllCategories($filters);
    }

    /**
     * 카테고리 트리 조회
     */
    public function getCategoryTree(): array
    {
        return $this->repository->getCategoryTree();
    }

    /**
     * 카테고리 상세 조회
     */
    public function getCategory(int $id): ?array
    {
        return $this->repository->findById($id);
    }

    /**
     * 카테고리 등록
     */
    public function createCategory(array $data): int
    {
        if (empty($data['name'])) {
            throw new InvalidArgumentException('카테고리명을 입력해주세요.');
        }

        return $this->repository->create($data);
    }

    /**
     * 카테고리 수정
     */
    public function updateCategory(int $id, array $data): bool
    {
        $category = $this->repository->findById($id);
        if (!$category) {
            throw new InvalidArgumentException('존재하지 않는 카테고리입니다.');
        }

        return $this->repository->update($id, $data);
    }

    /**
     * 카테고리 삭제
     */
    public function deleteCategory(int $id): bool
    {
        $category = $this->repository->findById($id);
        if (!$category) {
            throw new InvalidArgumentException('존재하지 않는 카테고리입니다.');
        }

        // 자식 카테고리가 있으면 삭제 불가
        if ($this->repository->hasChildren($id)) {
            throw new InvalidArgumentException('하위 카테고리가 있어 삭제할 수 없습니다.');
        }

        // 재고가 있으면 삭제 불가
        if ($category['current_stock'] > 0) {
            throw new InvalidArgumentException('재고가 있어 삭제할 수 없습니다.');
        }

        return $this->repository->delete($id);
    }

    // ============ 입고 관리 ============

    /**
     * 입고 처리
     */
    public function stockIn(array $data): int
    {
        if (empty($data['category_id'])) {
            throw new InvalidArgumentException('카테고리를 선택해주세요.');
        }

        if (empty($data['item_name'])) {
            throw new InvalidArgumentException('품명을 입력해주세요.');
        }

        if (empty($data['quantity']) || $data['quantity'] <= 0) {
            throw new InvalidArgumentException('수량은 0보다 커야 합니다.');
        }

        // 카테고리 존재 확인
        $category = $this->repository->findById($data['category_id']);
        if (!$category) {
            throw new InvalidArgumentException('존재하지 않는 카테고리입니다.');
        }

        return $this->repository->recordStockIn($data);
    }

    /**
     * 입고 이력 조회
     */
    public function getStockInHistory(int $categoryId): array
    {
        return $this->repository->getStockInHistory($categoryId);
    }

    // ============ 사용 관리 ============

    /**
     * 사용 처리
     */
    public function useConsumable(array $data): int
    {
        if (empty($data['category_id'])) {
            throw new InvalidArgumentException('카테고리를 선택해주세요.');
        }

        if (empty($data['quantity']) || $data['quantity'] <= 0) {
            throw new InvalidArgumentException('사용 수량은 0보다 커야 합니다.');
        }

        // 카테고리 존재 확인
        $category = $this->repository->findById($data['category_id']);
        if (!$category) {
            throw new InvalidArgumentException('존재하지 않는 카테고리입니다.');
        }

        return $this->repository->recordUsage($data);
    }

    /**
     * 사용 이력 조회
     */
    public function getUsageHistory(int $categoryId): array
    {
        return $this->repository->getUsageHistory($categoryId);
    }

    // ============ 재고 조회 ============

    /**
     * 카테고리별 재고 조회
     */
    public function getStockByCategory(int $categoryId): array
    {
        return $this->repository->getStockByCategory($categoryId);
    }

    /**
     * 품명별 재고 조회
     */
    public function getStockByItem(int $categoryId): array
    {
        return $this->repository->getStockByItem($categoryId);
    }

    /**
     * 재고 조정
     */
    public function adjustStock(int $categoryId, int $quantity, string $itemName = '재고조정'): bool
    {
        $category = $this->repository->findById($categoryId);
        if (!$category) {
            throw new InvalidArgumentException('존재하지 않는 카테고리입니다.');
        }

        return $this->repository->adjustStock($categoryId, $quantity, $itemName);
    }
}
