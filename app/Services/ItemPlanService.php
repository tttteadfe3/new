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

    /**
     * CSV 파일로부터 여러 계획을 생성합니다.
     * @param string $filePath
     * @param int $year
     * @return array
     * @throws InvalidArgumentException
     * @throws \Exception
     */
    public function createPlansFromCsv(string $filePath, int $year): array
    {
        $user = $this->authService->user();
        $creatorId = $user['employee_id'] ?? null;
        if (!$creatorId) {
            throw new \Exception('로그인한 사용자 정보를 찾을 수 없습니다.');
        }

        $items = $this->itemRepository->findAll();
        $itemMap = [];
        foreach ($items as $item) {
            $itemMap[$item['name']] = $item['id'];
        }

        $plansToCreate = [];
        $errors = [];
        $rowNumber = 1;

        if (($handle = fopen($filePath, "r")) === FALSE) {
            throw new \Exception("파일을 열 수 없습니다: {$filePath}");
        }

        // Skip header row
        fgetcsv($handle, 1000, ",");

        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            $rowNumber++;
            // Expected columns: 품목명, 단가, 예정 수량, 비고
            if (count($data) < 3) {
                $errors[] = "{$rowNumber}행: 데이터 열 개수가 부족합니다.";
                continue;
            }

            $itemName = trim($data[0]);
            $unitPrice = trim($data[1]);
            $quantity = trim($data[2]);
            $note = isset($data[3]) ? trim($data[3]) : null;

            if (!isset($itemMap[$itemName])) {
                $errors[] = "{$rowNumber}행: 존재하지 않는 품목명입니다 '{$itemName}'.";
                continue;
            }
            $itemId = $itemMap[$itemName];

            $planData = [
                'year' => $year,
                'item_id' => $itemId,
                'unit_price' => $unitPrice,
                'quantity' => $quantity,
                'note' => $note,
                'created_by' => $creatorId,
            ];

            $plan = ItemPlan::make($planData);
            if (!$plan->validate()) {
                $errorMessages = implode(', ', $plan->getErrors());
                $errors[] = "{$rowNumber}행 ({$itemName}): {$errorMessages}";
                continue;
            }

            $plansToCreate[] = $planData;
        }
        fclose($handle);

        if (!empty($errors)) {
            throw new InvalidArgumentException(implode("\n", $errors));
        }

        if (empty($plansToCreate)) {
            throw new InvalidArgumentException("업로드할 유효한 데이터가 없습니다.");
        }

        $createdCount = $this->itemPlanRepository->createBulk($plansToCreate);

        return ['created' => $createdCount];
    }
}
