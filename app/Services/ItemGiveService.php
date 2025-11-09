<?php

namespace App\Services;

use App\Models\ItemGive;
use App\Repositories\ItemGiveRepository;
use InvalidArgumentException;

class ItemGiveService
{
    private ItemGiveRepository $itemGiveRepository;
    private AuthService $authService;

    public function __construct(
        ItemGiveRepository $itemGiveRepository,
        AuthService $authService
    ) {
        $this->itemGiveRepository = $itemGiveRepository;
        $this->authService = $authService;
    }

    /**
     * 필터링된 지급 내역 목록을 조회합니다.
     * @param array $filters
     * @return array
     */
    public function getGives(array $filters = []): array
    {
        return $this->itemGiveRepository->findAll($filters);
    }

    /**
     * 재고가 있는 품목 목록을 조회합니다.
     * @return array
     */
    public function getAvailableItems(): array
    {
        return $this->itemGiveRepository->getAvailableItems();
    }

    /**
     * 신규 지급 내역을 생성합니다.
     * @param array $data
     * @return string
     */
    public function createGive(array $data): string
    {
        $user = $this->authService->user();
        $data['created_by'] = $user['employee_id'] ?? null;

        // 지급 대상 유효성 검사
        if (empty($data['department_id']) && empty($data['employee_id'])) {
            throw new InvalidArgumentException('지급 대상 부서 또는 직원을 선택해야 합니다.');
        }

        $give = ItemGive::make($data);
        if (!$give->validate()) {
            throw new InvalidArgumentException(implode(', ', $give->getErrors()));
        }

        return $this->itemGiveRepository->create($data);
    }

    /**
     * 지급 내역을 삭제(취소)합니다.
     * @param int $id
     * @return bool
     */
    public function deleteGive(int $id): bool
    {
        // TODO: 지급 취소에 대한 추가적인 비즈니스 규칙이 있다면 여기에 구현
        // 예: 특정 기간이 지난 지급 내역은 취소 불가 등

        return $this->itemGiveRepository->delete($id);
    }
}
