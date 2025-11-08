<?php

namespace App\Services;

use App\Models\ItemCategory;
use App\Repositories\ItemCategoryRepository;
use InvalidArgumentException;

class ItemCategoryService
{
    private ItemCategoryRepository $itemCategoryRepository;

    public function __construct(ItemCategoryRepository $itemCategoryRepository)
    {
        $this->itemCategoryRepository = $itemCategoryRepository;
    }

    /**
     * 모든 지급품 분류를 계층 구조로 가져옵니다.
     * @return array
     */
    public function getAllCategoriesAsHierarchy(): array
    {
        return $this->itemCategoryRepository->getAllAsHierarchy();
    }

    /**
     * 새로운 지급품 분류를 생성합니다.
     * @param array $data
     * @return string
     */
    public function createCategory(array $data): string
    {
        $category = ItemCategory::make($data);
        if (!$category->validate()) {
            throw new InvalidArgumentException(implode(', ', $category->getErrors()));
        }

        if (!empty($data['parent_id'])) {
            $parent = $this->itemCategoryRepository->findById($data['parent_id']);
            if (!$parent) {
                throw new InvalidArgumentException('상위 분류를 찾을 수 없습니다.');
            }
        }

        return $this->itemCategoryRepository->create($data);
    }

    /**
     * 기존 지급품 분류를 업데이트합니다.
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function updateCategory(int $id, array $data): bool
    {
        $category = $this->itemCategoryRepository->findById($id);
        if (!$category) {
            throw new InvalidArgumentException('분류를 찾을 수 없습니다.');
        }

        // 유효성 검사를 위해 기존 데이터와 새 데이터를 병합
        $mergedData = array_merge($category->toArray(), $data);
        $validationModel = ItemCategory::make($mergedData);
        if (!$validationModel->validate()) {
            throw new InvalidArgumentException(implode(', ', $validationModel->getErrors()));
        }

        if (!empty($data['parent_id'])) {
            if ($id == $data['parent_id']) {
                throw new InvalidArgumentException('자기 자신을 상위 분류로 지정할 수 없습니다.');
            }
            $parent = $this->itemCategoryRepository->findById($data['parent_id']);
            if (!$parent) {
                throw new InvalidArgumentException('상위 분류를 찾을 수 없습니다.');
            }
        }

        return $this->itemCategoryRepository->update($id, $data);
    }

    /**
     * 지급품 분류를 삭제합니다.
     * @param int $id
     * @return bool
     */
    public function deleteCategory(int $id): bool
    {
        $category = $this->itemCategoryRepository->findById($id);
        if (!$category) {
            throw new InvalidArgumentException('분류를 찾을 수 없습니다.');
        }

        // 리포지토리에서 하위 항목 존재 여부를 이미 확인하지만,
        // 서비스 계층에서도 명시적인 비즈니스 규칙을 적용할 수 있습니다.
        // 예를 들어, 더 복잡한 조건(예: 특정 상태의 품목이 연결된 경우 삭제 불가)이 추가될 수 있습니다.

        if (!$this->itemCategoryRepository->delete($id)) {
            throw new \RuntimeException('하위 분류 또는 연결된 품목이 있어 삭제할 수 없습니다.');
        }

        return true;
    }
}
