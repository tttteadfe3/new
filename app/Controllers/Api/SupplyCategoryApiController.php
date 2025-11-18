<?php

namespace App\Controllers\Api;

use App\Services\SupplyCategoryService;
use App\Core\Request;
use App\Services\AuthService;
use App\Services\ViewDataService;
use App\Services\ActivityLogger;
use App\Repositories\EmployeeRepository;
use App\Core\JsonResponse;
use Exception;

class SupplyCategoryApiController extends BaseApiController
{
    private SupplyCategoryService $supplyCategoryService;

    public function __construct(
        Request $request,
        AuthService $authService,
        ViewDataService $viewDataService,
        ActivityLogger $activityLogger,
        EmployeeRepository $employeeRepository,
        JsonResponse $jsonResponse,
        SupplyCategoryService $supplyCategoryService
    ) {
        parent::__construct($request, $authService, $viewDataService, $activityLogger, $employeeRepository, $jsonResponse);
        $this->supplyCategoryService = $supplyCategoryService;
    }

    /**
     * 모든 분류 목록을 조회합니다.
     */
    public function index(): void
    {
        try {
            $hierarchical = $this->request->input('hierarchical', false);
            $active = $this->request->input('active', false);
            
            if ($hierarchical) {
                $categories = $this->supplyCategoryService->getHierarchicalCategories();
            } elseif ($active) {
                $categories = $this->supplyCategoryService->getActiveCategories();
            } else {
                $categories = $this->supplyCategoryService->getAllCategories();
            }
            
            $this->apiSuccess($categories);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * 특정 분류의 상세 정보를 조회합니다.
     */
    public function show(int $id): void
    {
        try {
            $category = $this->supplyCategoryService->getCategoryById($id);
            if (!$category) {
                $this->apiNotFound('분류를 찾을 수 없습니다.');
                return;
            }

            // 추가 정보 포함
            $categoryData = $category->toArray();
            
            // 대분류인 경우 하위 분류 포함
            if ($category->isMainCategory()) {
                $subCategories = $this->supplyCategoryService->getSubCategories($id);
                // SupplyCategory 객체 배열을 배열로 변환
                $categoryData['sub_categories'] = array_map(function($subCategory) {
                    return $subCategory->toArray();
                }, $subCategories);
            }
            
            // 소분류인 경우 상위 분류 정보 포함
            if ($category->isSubCategory()) {
                $parentId = $category->getAttribute('parent_id');
                if ($parentId) {
                    $parentCategory = $this->supplyCategoryService->getCategoryById($parentId);
                    $categoryData['parent_category'] = $parentCategory ? $parentCategory->toArray() : null;
                }
            }

            $this->apiSuccess($categoryData);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * 새로운 분류를 생성합니다.
     */
    public function store(): void
    {
        try {
            $data = $this->getJsonInput();
            
            // 필수 필드 검증
            $requiredFields = ['category_code', 'category_name', 'level'];
            if (!$this->validateRequired($data, $requiredFields)) {
                return;
            }

            // 레벨 검증
            if (!in_array($data['level'], [1, 2])) {
                $this->apiBadRequest('레벨은 1(대분류) 또는 2(소분류)만 가능합니다.');
                return;
            }

            // 소분류인 경우 parent_id 필수
            if ($data['level'] == 2 && empty($data['parent_id'])) {
                $this->apiBadRequest('소분류는 상위 분류를 선택해야 합니다.');
                return;
            }

            $categoryId = $this->supplyCategoryService->createCategory($data);
            
            $this->apiSuccess([
                'id' => $categoryId,
                'message' => '분류가 성공적으로 생성되었습니다.'
            ]);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * 분류 정보를 수정합니다.
     */
    public function update(int $id): void
    {
        try {
            $data = $this->getJsonInput();
            
            if (empty($data)) {
                $this->apiBadRequest('수정할 데이터가 없습니다.');
                return;
            }

            $success = $this->supplyCategoryService->updateCategory($id, $data);
            
            if ($success) {
                $this->apiSuccess(null, '분류가 성공적으로 수정되었습니다.');
            } else {
                $this->apiError('분류 수정에 실패했습니다.');
            }
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * 분류를 삭제합니다.
     */
    public function destroy(int $id): void
    {
        try {
            $success = $this->supplyCategoryService->deleteCategory($id);
            
            if ($success) {
                $this->apiSuccess(null, '분류가 성공적으로 삭제되었습니다.');
            } else {
                $this->apiError('분류 삭제에 실패했습니다.');
            }
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * 레벨별 분류를 조회합니다.
     */
    public function getByLevel(int $level): void
    {
        try {
            if (!in_array($level, [1, 2])) {
                $this->apiBadRequest('레벨은 1(대분류) 또는 2(소분류)만 가능합니다.');
                return;
            }

            $categories = $this->supplyCategoryService->getCategoriesByLevel($level);
            $categoriesArray = array_map(function($category) {
                return $category->toArray();
            }, $categories);
            $this->apiSuccess($categoriesArray);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * 상위 분류의 하위 분류들을 조회합니다.
     */
    public function getSubCategories(int $parentId): void
    {
        try {
            $subCategories = $this->supplyCategoryService->getSubCategories($parentId);
            $subCategoriesArray = array_map(function($category) {
                return $category->toArray();
            }, $subCategories);
            $this->apiSuccess($subCategoriesArray);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * 분류 상태를 토글합니다 (활성/비활성).
     */
    public function toggleStatus(int $id): void
    {
        try {
            $success = $this->supplyCategoryService->toggleCategoryStatus($id);
            
            if ($success) {
                $category = $this->supplyCategoryService->getCategoryById($id);
                $status = $category->isActive() ? '활성' : '비활성';
                $this->apiSuccess([
                    'is_active' => $category->isActive(),
                    'message' => "분류가 {$status} 상태로 변경되었습니다."
                ]);
            } else {
                $this->apiError('분류 상태 변경에 실패했습니다.');
            }
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * 분류 코드를 자동 생성합니다.
     */
    public function generateCode(): void
    {
        try {
            $level = $this->request->input('level', 1);
            $parentId = $this->request->input('parent_id');

            if (!in_array($level, [1, 2])) {
                $this->apiBadRequest('레벨은 1(대분류) 또는 2(소분류)만 가능합니다.');
                return;
            }

            $generatedCode = $this->supplyCategoryService->generateCategoryCode(
                (int)$level,
                $parentId ? (int)$parentId : null
            );

            $this->apiSuccess([
                'code' => $generatedCode
            ]);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * 분류 통계 정보를 조회합니다.
     */
    public function getStatistics(): void
    {
        try {
            $allCategories = $this->supplyCategoryService->getAllCategories();
            $activeCategories = $this->supplyCategoryService->getActiveCategories();
            $mainCategories = $this->supplyCategoryService->getCategoriesByLevel(1);
            $subCategories = $this->supplyCategoryService->getCategoriesByLevel(2);

            $statistics = [
                'total_categories' => count($allCategories),
                'active_categories' => count($activeCategories),
                'inactive_categories' => count($allCategories) - count($activeCategories),
                'main_categories' => count($mainCategories),
                'sub_categories' => count($subCategories),
                'active_main_categories' => count(array_filter($mainCategories, function($cat) {
                    return $cat->isActive();
                })),
                'active_sub_categories' => count(array_filter($subCategories, function($cat) {
                    return $cat->isActive();
                }))
            ];

            $this->apiSuccess($statistics);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * 분류 검색을 수행합니다.
     */
    public function search(): void
    {
        try {
            $query = $this->request->input('q', '');
            $level = $this->request->input('level');
            $activeOnly = $this->request->input('active_only', false);

            if (empty($query)) {
                $this->apiBadRequest('검색어를 입력해주세요.');
                return;
            }

            // 기본 분류 목록 조회
            if ($level) {
                $categories = $this->supplyCategoryService->getCategoriesByLevel((int)$level);
            } elseif ($activeOnly) {
                $categories = $this->supplyCategoryService->getActiveCategories();
            } else {
                $categories = $this->supplyCategoryService->getAllCategories();
            }

            // 검색 필터링
            $filteredCategories = array_filter($categories, function($category) use ($query) {
                $categoryName = $category->getAttribute('category_name');
                $categoryCode = $category->getAttribute('category_code');
                
                return stripos($categoryName, $query) !== false || 
                       stripos($categoryCode, $query) !== false;
            });

            // SupplyCategory 객체 배열을 배열로 변환
            $filteredCategoriesArray = array_map(function($category) {
                return $category->toArray();
            }, array_values($filteredCategories));

            $this->apiSuccess($filteredCategoriesArray);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * 분류 유효성을 검증합니다.
     */
    public function validate(): void
    {
        try {
            $data = $this->getJsonInput();
            
            // 필수 필드 검증
            $requiredFields = ['category_code', 'category_name', 'level'];
            if (!$this->validateRequired($data, $requiredFields)) {
                return;
            }

            // 분류 코드 중복 검사
            $excludeId = $data['id'] ?? null;
            $isDuplicate = $this->supplyCategoryService->getCategoryRepository()->isDuplicateCategoryCode(
                $data['category_code'], 
                $excludeId
            );

            if ($isDuplicate) {
                $this->apiError('이미 존재하는 분류 코드입니다.', 'DUPLICATE_CODE');
                return;
            }

            $this->apiSuccess([
                'valid' => true,
                'message' => '유효한 분류 데이터입니다.'
            ]);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * 예외를 처리합니다.
     */
    protected function handleException(Exception $e): void
    {
        if ($e instanceof \InvalidArgumentException) {
            $this->apiBadRequest($e->getMessage());
        } else {
            $this->apiError('서버 오류가 발생했습니다: ' . $e->getMessage());
        }
    }
}
