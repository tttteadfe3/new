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

    /**
     * 모든 소모품 조회
     */
    public function getAllConsumables(array $filters = []): array
    {
        return $this->repository->findAll($filters);
    }

    /**
     * 소모품 상세 조회
     */
    public function getConsumable(int $id): ?array
    {
        return $this->repository->findById($id);
    }

    /**
     * 소모품 등록
     */
    public function createConsumable(array $data): int
    {
        if (empty($data['name'])) {
            throw new InvalidArgumentException('소모품명을 입력해주세요.');
        }

        return $this->repository->create($data);
    }

    /**
     * 소모품 수정
     */
    public function updateConsumable(int $id, array $data): bool
    {
        $consumable = $this->repository->findById($id);
        if (!$consumable) {
            throw new InvalidArgumentException('존재하지 않는 소모품입니다.');
        }

        return $this->repository->update($id, $data);
    }

    /**
     * 소모품 삭제
     */
    public function deleteConsumable(int $id): bool
    {
        $consumable = $this->repository->findById($id);
        if (!$consumable) {
            throw new InvalidArgumentException('존재하지 않는 소모품입니다.');
        }

        return $this->repository->delete($id);
    }

    /**
     * 입고 처리
     */
    public function stockIn(int $consumableId, array $data): bool
    {
        $consumable = $this->repository->findById($consumableId);
        if (!$consumable) {
            throw new InvalidArgumentException('존재하지 않는 소모품입니다.');
        }

        if (empty($data['quantity']) || $data['quantity'] <= 0) {
            throw new InvalidArgumentException('입고 수량은 0보다 커야 합니다.');
        }

        // 입고 이력 기록
        $data['consumable_id'] = $consumableId;
        $this->repository->recordStockIn($data);

        // 재고 증가
        return $this->repository->adjustStock($consumableId, $data['quantity']);
    }

    /**
     * 출고/사용 처리
     */
    public function useConsumable(int $consumableId, array $data): bool
    {
        $consumable = $this->repository->findById($consumableId);
        if (!$consumable) {
            throw new InvalidArgumentException('존재하지 않는 소모품입니다.');
        }

        if (empty($data['quantity']) || $data['quantity'] <= 0) {
            throw new InvalidArgumentException('사용 수량은 0보다 커야 합니다.');
        }

        if ($consumable['current_stock'] < $data['quantity']) {
            throw new InvalidArgumentException('재고가 부족합니다.');
        }

        // 사용 이력 기록
        $data['consumable_id'] = $consumableId;
        $this->repository->recordUsage($data);

        // 재고 감소
        return $this->repository->adjustStock($consumableId, -$data['quantity']);
    }

    /**
     * 카테고리 목록
     */
    public function getCategories(): array
    {
        return $this->repository->getCategories();
    }

    /**
     * 사용 이력 조회
     */
    public function getUsageHistory(int $consumableId, int $limit = 50): array
    {
        return $this->repository->getUsageHistory($consumableId, $limit);
    }

    /**
     * 입고 이력 조회
     */
    public function getStockInHistory(int $consumableId, int $limit = 50): array
    {
        return $this->repository->getStockInHistory($consumableId, $limit);
    }
}
