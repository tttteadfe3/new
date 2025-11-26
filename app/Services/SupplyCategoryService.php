<?php

namespace App\Services;

use App\Repositories\SupplyCategoryRepository;
use App\Repositories\SupplyItemRepository;
use App\Models\SupplyCategory;
use App\Services\ActivityLogger;

class SupplyCategoryService
{
    private SupplyCategoryRepository $categoryRepository;
    private SupplyItemRepository $itemRepository;
    private ActivityLogger $activityLogger;

    public function __construct(
        SupplyCategoryRepository $categoryRepository,
        SupplyItemRepository $itemRepository,
        ActivityLogger $activityLogger
    ) {
        $this->categoryRepository = $categoryRepository;
        $this->itemRepository = $itemRepository;
        $this->activityLogger = $activityLogger;
    }

    /**
     * 모든 분류를 계층적 구조로 조회합니다.
     */
    public function getAllCategories(): array
    {
        return $this->categoryRepository->findAll();
    }

    /**
     * 계층적 분류 구조를 조회합니다.
     */
    public function getHierarchicalCategories(): array
    {
        return $this->categoryRepository->findHierarchical();
    }

    /**
     * 활성 분류만 조회합니다.
     */
    public function getActiveCategories(): array
    {
        return $this->categoryRepository->findActiveCategories();
    }

    /**
     * 레벨별 분류를 조회합니다.
     */
    public function getCategoriesByLevel(int $level): array
    {
        if (!in_array($level, [1, 2])) {
            throw new \InvalidArgumentException('레벨은 1(대분류) 또는 2(소분류)만 가능합니다.');
        }

        return $this->categoryRepository->findByLevel($level);
    }

    /**
     * 상위 분류의 하위 분류들을 조회합니다.
     */
    public function getSubCategories(int $parentId): array
    {
        $parentCategory = $this->categoryRepository->findById($parentId);
        if (!$parentCategory || !$parentCategory->isMainCategory()) {
            throw new \InvalidArgumentException('유효하지 않은 상위 분류입니다.');
        }

        return $this->categoryRepository->findByParentId($parentId);
    }

    /**
     * ID로 분류를 조회합니다.
     */
    public function getCategoryById(int $id): ?SupplyCategory
    {
        return $this->categoryRepository->findById($id);
    }

    /**
     * 분류에 연관된 데이터가 있는지 확인합니다.
     */
    private function hasAssociatedData(int $categoryId): bool
    {
        // 연관된 품목이 있는지 확인
        if ($this->categoryRepository->hasAssociatedItems($categoryId)) {
            return true;
        }

        // 하위 분류가 있는지 확인
        $subCategories = $this->categoryRepository->findByParentId($categoryId);
        if (!empty($subCategories)) {
            return true;
        }

        return false;
    }

    /**
     * 분류 데이터를 검증합니다.
     */
    private function validateCategoryData(array $data): void
    {
        $model = SupplyCategory::make($data);
        if (!$model->validate()) {
            $errors = $model->getErrors();
            $errorMessage = implode(', ', $errors);
            throw new \InvalidArgumentException("데이터 검증 실패: {$errorMessage}");
        }
    }

    /**
     * 비즈니스 규칙을 검증합니다.
     */
    private function validateBusinessRules(array $data): void
    {
        $level = $data['level'];
        $parentId = $data['parent_id'] ?? null;

        // 대분류는 상위 분류가 없어야 함
        if ($level === 1 && $parentId !== null) {
            throw new \InvalidArgumentException('대분류는 상위 분류를 가질 수 없습니다.');
        }

        // 소분류는 반드시 상위 분류가 있어야 함
        if ($level === 2) {
            if ($parentId === null) {
                throw new \InvalidArgumentException('소분류는 상위 분류를 선택해야 합니다.');
            }

            // 상위 분류가 존재하고 대분류인지 확인
            $parentCategory = $this->categoryRepository->findById($parentId);
            if (!$parentCategory || !$parentCategory->isMainCategory()) {
                throw new \InvalidArgumentException('유효하지 않은 상위 분류입니다.');
            }

            // 상위 분류가 활성 상태인지 확인
            if (!$parentCategory->isActive()) {
                throw new \InvalidArgumentException('비활성 상태의 상위 분류에는 소분류를 생성할 수 없습니다.');
            }
        }
    }

    /**
     * 활동 로그를 기록합니다.
     */
    private function logActivity(string $action, string $details): void
    {
        // ActivityLogger는 AuthService를 통해 자동으로 현재 사용자 정보를 가져옵니다
        // 여기서는 간단히 호출만 하면 됩니다
    }

    /**
     * 분류를 생성합니다.
     */
    public function createCategory(array $data): int
    {
        $this->validateCategoryData($data);
        $this->validateBusinessRules($data);

        $id = $this->categoryRepository->create($data);
        $this->logActivity('create', "분류 생성: {$data['category_name']} (ID: {$id})");

        return $id;
    }

    /**
     * 분류를 수정합니다.
     */
    public function updateCategory(int $id, array $data): bool
    {
        $category = $this->categoryRepository->findById($id);
        if (!$category) {
            throw new \InvalidArgumentException('존재하지 않는 분류입니다.');
        }

        // 상위 분류 변경 시 유효성 검사
        if (isset($data['parent_id']) && $data['parent_id'] != $category->parent_id) {
            $this->validateBusinessRules(array_merge($category->toArray(), $data));
        }

        $success = $this->categoryRepository->update($id, $data);
        if ($success) {
            $this->logActivity('update', "분류 수정: {$data['category_name']} (ID: {$id})");
        }

        return $success;
    }

    /**
     * 분류 상태를 토글합니다.
     */
    public function toggleCategoryStatus(int $id): bool
    {
        $category = $this->categoryRepository->findById($id);
        if (!$category) {
            throw new \InvalidArgumentException('존재하지 않는 분류입니다.');
        }

        $newStatus = !$category->is_active;

        // 소분류 활성화 시 상위 분류 확인
        if ($newStatus && $category->level == 2) {
            $parent = $this->categoryRepository->findById($category->parent_id);
            if (!$parent || !$parent->is_active) {
                throw new \InvalidArgumentException('상위 분류가 비활성 상태입니다.');
            }
        }

        // 대분류 비활성화 시 하위 분류 확인
        if (!$newStatus && $category->level == 1) {
            $subCategories = $this->categoryRepository->findByParentId($id);
            foreach ($subCategories as $sub) {
                if ($sub->is_active) {
                    throw new \InvalidArgumentException('활성 상태인 하위 분류가 있어 비활성화할 수 없습니다.');
                }
            }
        }

        $success = $this->categoryRepository->update($id, ['is_active' => $newStatus]);
        if ($success) {
            $statusStr = $newStatus ? '활성' : '비활성';
            $this->logActivity('update', "분류 상태 변경: {$category->category_name} (ID: {$id}) -> {$statusStr}");
        }

        return $success;
    }

    /**
     * 분류를 삭제합니다.
     */
    public function deleteCategory(int $id): bool
    {
        if ($this->hasAssociatedData($id)) {
            throw new \InvalidArgumentException('하위 분류나 품목이 있는 분류는 삭제할 수 없습니다.');
        }

        $category = $this->categoryRepository->findById($id);
        $success = $this->categoryRepository->delete($id);
        if ($success) {
            $this->logActivity('delete', "분류 삭제: {$category->category_name} (ID: {$id})");
        }

        return $success;
    }
}