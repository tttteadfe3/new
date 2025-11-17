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
     * 새로운 분류를 생성합니다.
     */
    public function createCategory(array $data): int
    {
        // 데이터 검증
        $this->validateCategoryData($data);

        // 분류 코드 중복 검사
        if ($this->categoryRepository->isDuplicateCategoryCode($data['category_code'])) {
            throw new \InvalidArgumentException('이미 존재하는 분류 코드입니다.');
        }

        // 비즈니스 규칙 검증
        $this->validateBusinessRules($data);

        // 분류 생성
        $categoryId = $this->categoryRepository->create($data);

        // 활동 로그 기록
        $this->activityLogger->logSupplyCategoryCreate($categoryId, $data);

        return $categoryId;
    }

    /**
     * 분류를 수정합니다.
     */
    public function updateCategory(int $id, array $data): bool
    {
        $existingCategory = $this->categoryRepository->findById($id);
        if (!$existingCategory) {
            throw new \InvalidArgumentException('존재하지 않는 분류입니다.');
        }

        // 분류 코드는 수정할 수 없음 (비즈니스 규칙)
        if (isset($data['category_code']) && $data['category_code'] !== $existingCategory->getAttribute('category_code')) {
            throw new \InvalidArgumentException('분류 코드는 수정할 수 없습니다.');
        }

        // 레벨과 상위 분류는 수정할 수 없음 (비즈니스 규칙)
        if (isset($data['level']) && $data['level'] !== $existingCategory->getAttribute('level')) {
            throw new \InvalidArgumentException('분류 레벨은 수정할 수 없습니다.');
        }

        if (isset($data['parent_id']) && $data['parent_id'] !== $existingCategory->getAttribute('parent_id')) {
            throw new \InvalidArgumentException('상위 분류는 수정할 수 없습니다.');
        }

        // 데이터 검증
        $allowedFields = ['category_name', 'is_active', 'display_order'];
        $updateData = array_intersect_key($data, array_flip($allowedFields));
        
        if (empty($updateData)) {
            throw new \InvalidArgumentException('수정할 데이터가 없습니다.');
        }

        // 분류명 검증
        if (isset($updateData['category_name'])) {
            if (empty(trim($updateData['category_name']))) {
                throw new \InvalidArgumentException('분류명은 필수입니다.');
            }
            if (strlen($updateData['category_name']) > 100) {
                throw new \InvalidArgumentException('분류명은 100자를 초과할 수 없습니다.');
            }
        }

        // 분류 수정
        $success = $this->categoryRepository->update($id, $updateData);

        if ($success) {
            // 활동 로그 기록
            $oldData = $existingCategory->toArray();
            $newData = array_merge($oldData, $updateData);
            $this->activityLogger->logSupplyCategoryUpdate($id, $oldData, $newData);
        }

        return $success;
    }

    /**
     * 분류를 삭제합니다.
     */
    public function deleteCategory(int $id): bool
    {
        $category = $this->categoryRepository->findById($id);
        if (!$category) {
            throw new \InvalidArgumentException('존재하지 않는 분류입니다.');
        }

        // 연관 데이터 검증
        if ($this->hasAssociatedData($id)) {
            throw new \InvalidArgumentException('연관된 데이터가 있어 삭제할 수 없습니다.');
        }

        // 분류 삭제
        $success = $this->categoryRepository->delete($id);

        if ($success) {
            // 활동 로그 기록
            $this->activityLogger->logSupplyCategoryDelete($id, $category->toArray());
        }

        return $success;
    }

    /**
     * 분류 상태를 변경합니다 (활성/비활성).
     */
    public function toggleCategoryStatus(int $id): bool
    {
        $category = $this->categoryRepository->findById($id);
        if (!$category) {
            throw new \InvalidArgumentException('존재하지 않는 분류입니다.');
        }

        $currentStatus = $category->getAttribute('is_active');
        $newStatus = $currentStatus ? 0 : 1;
        $statusText = $newStatus ? '활성' : '비활성';

        $success = $this->categoryRepository->update($id, ['is_active' => $newStatus]);

        if ($success) {
            // 활동 로그 기록
            $oldData = $category->toArray();
            $newData = array_merge($oldData, ['is_active' => $newStatus]);
            $this->activityLogger->logSupplyCategoryUpdate($id, $oldData, $newData);
        }

        return $success;
    }

    /**
     * 분류 코드를 자동 생성합니다.
     */
    public function generateCategoryCode(int $level, ?int $parentId = null): string
    {
        if ($level === 1) {
            // 대분류 코드 생성 (MC001, MC002, ...)
            $existingCodes = $this->categoryRepository->findByLevel(1);
            $maxNumber = 0;
            
            foreach ($existingCodes as $category) {
                $code = $category->getAttribute('category_code');
                if (preg_match('/^MC(\d{3})$/', $code, $matches)) {
                    $maxNumber = max($maxNumber, (int)$matches[1]);
                }
            }
            
            return 'MC' . str_pad($maxNumber + 1, 3, '0', STR_PAD_LEFT);
        } else {
            // 소분류 코드 생성 (상위분류코드 + SC001, SC002, ...)
            if (!$parentId) {
                throw new \InvalidArgumentException('소분류는 상위 분류가 필요합니다.');
            }
            
            $parentCategory = $this->categoryRepository->findById($parentId);
            if (!$parentCategory) {
                throw new \InvalidArgumentException('유효하지 않은 상위 분류입니다.');
            }
            
            $parentCode = $parentCategory->getAttribute('category_code');
            $existingCodes = $this->categoryRepository->findByParentId($parentId);
            $maxNumber = 0;
            
            foreach ($existingCodes as $category) {
                $code = $category->getAttribute('category_code');
                if (preg_match('/^' . preg_quote($parentCode) . 'SC(\d{3})$/', $code, $matches)) {
                    $maxNumber = max($maxNumber, (int)$matches[1]);
                }
            }
            
            return $parentCode . 'SC' . str_pad($maxNumber + 1, 3, '0', STR_PAD_LEFT);
        }
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
}