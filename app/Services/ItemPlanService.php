<?php

namespace App\Services;

use App\Models\ItemPlan;
use App\Repositories\ItemPlanRepository;
use App\Repositories\ItemRepository;
use InvalidArgumentException;

class ItemPlanService
{
    private ItemPlanRepository $itemPlanRepository;
    private ItemRepository $itemRepository;
    private AuthService $authService;

    public function __construct(
        ItemPlanRepository $itemPlanRepository,
        ItemRepository $itemRepository,
        AuthService $authService
    ) {
        $this->itemPlanRepository = $itemPlanRepository;
        $this->itemRepository = $itemRepository;
        $this->authService = $authService;
    }

    /**
     * 특정 연도의 모든 계획을 가져옵니다.
     * @param int $year
     * @return array
     */
    public function getPlansByYear(int $year): array
    {
        return $this->itemPlanRepository->findByYear($year);
    }

    /**
     * 새로운 계획을 생성합니다.
     * @param array $data
     * @return string
     */
    public function createPlan(array $data): string
    {
        $user = $this->authService->user();
        $data['created_by'] = $user['employee_id'] ?? null;

        $plan = ItemPlan::make($data);
        if (!$plan->validate()) {
            throw new InvalidArgumentException(implode(', ', $plan->getErrors()));
        }

        $item = $this->itemRepository->findById($data['item_id']);
        if (!$item) {
            throw new InvalidArgumentException('존재하지 않는 품목입니다.');
        }

        return $this->itemPlanRepository->create($data);
    }

    /**
     * 기존 계획을 업데이트합니다.
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function updatePlan(int $id, array $data): bool
    {
        $plan = $this->itemPlanRepository->findById($id);
        if (!$plan) {
            throw new InvalidArgumentException('존재하지 않는 계획입니다.');
        }

        $mergedData = array_merge($plan->toArray(), $data);
        $validationModel = ItemPlan::make($mergedData);
        if (!$validationModel->validate()) {
            throw new InvalidArgumentException(implode(', ', $validationModel->getErrors()));
        }

        $item = $this->itemRepository->findById($data['item_id']);
        if (!$item) {
            throw new InvalidArgumentException('존재하지 않는 품목입니다.');
        }

        return $this->itemPlanRepository->update($id, $data);
    }

    /**
     * 계획을 삭제합니다.
     * @param int $id
     * @return bool
     */
    public function deletePlan(int $id): bool
    {
        $plan = $this->itemPlanRepository->findById($id);
        if (!$plan) {
            throw new InvalidArgumentException('존재하지 않는 계획입니다.');
        }

        return $this->itemPlanRepository->delete($id);
    }
}
