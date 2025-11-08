<?php

namespace App\Controllers\Api;

use App\Services\ItemCategoryService;
use Exception;
use InvalidArgumentException;
use App\Core\Request;
use App\Services\AuthService;
use App\Services\ViewDataService;
use App\Services\ActivityLogger;
use App\Repositories\EmployeeRepository;
use App\Core\JsonResponse;

class ItemCategoryController extends BaseApiController
{
    private ItemCategoryService $itemCategoryService;

    public function __construct(
        // BaseApiController dependencies
        Request $request,
        AuthService $authService,
        ViewDataService $viewDataService,
        ActivityLogger $activityLogger,
        EmployeeRepository $employeeRepository,
        JsonResponse $jsonResponse,
        // ItemCategoryController specific dependencies
        ItemCategoryService $itemCategoryService
    ) {
        parent::__construct($request, $authService, $viewDataService, $activityLogger, $employeeRepository, $jsonResponse);
        $this->itemCategoryService = $itemCategoryService;
    }

    /**
     * 모든 지급품 분류 목록을 계층 구조로 가져옵니다.
     */
    public function index(): void
    {
        try {
            $categories = $this->itemCategoryService->getAllCategoriesAsHierarchy();
            $this->apiSuccess($categories);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * 새 지급품 분류를 생성합니다.
     */
    public function store(): void
    {
        try {
            $data = $this->getJsonInput();
            $newCategoryId = $this->itemCategoryService->createCategory($data);

            if ($newCategoryId) {
                // 감사 로그 추가
                $currentUser = $this->authService->user();
                $this->activityLogger->logAction('item_category_create', "지급품 분류 '{$data['name']}' 생성", ['id' => $newCategoryId, 'data' => $data], $currentUser['employee_id']);

                $this->apiSuccess(['id' => $newCategoryId], '분류가 성공적으로 생성되었습니다.');
            } else {
                $this->apiError('분류 생성에 실패했습니다.');
            }
        } catch (InvalidArgumentException $e) {
            $this->apiBadRequest($e->getMessage());
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * 특정 지급품 분류 정보를 업데이트합니다.
     * @param int $id 분류 ID
     */
    public function update(int $id): void
    {
        try {
            $data = $this->getJsonInput();
            $success = $this->itemCategoryService->updateCategory($id, $data);

            if ($success) {
                // 감사 로그 추가
                $currentUser = $this->authService->user();
                $this->activityLogger->logAction('item_category_update', "지급품 분류 ID:{$id} 수정", ['id' => $id, 'data' => $data], $currentUser['employee_id']);

                $this->apiSuccess(null, '분류가 성공적으로 수정되었습니다.');
            } else {
                $this->apiError('분류 수정에 실패했거나 변경된 내용이 없습니다.');
            }
        } catch (InvalidArgumentException $e) {
            $this->apiBadRequest($e->getMessage());
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * 특정 지급품 분류를 삭제합니다.
     * @param int $id 분류 ID
     */
    public function destroy(int $id): void
    {
        try {
            $success = $this->itemCategoryService->deleteCategory($id);
            if ($success) {
                // 감사 로그 추가
                $currentUser = $this->authService->user();
                $this->activityLogger->logAction('item_category_delete', "지급품 분류 ID:{$id} 삭제", ['id' => $id], $currentUser['employee_id']);

                $this->apiSuccess(null, '분류가 성공적으로 삭제되었습니다.');
            } else {
                $this->apiError('분류 삭제에 실패했습니다.');
            }
        } catch (InvalidArgumentException $e) {
            $this->apiBadRequest($e->getMessage());
        } catch (\RuntimeException $e) {
            $this->apiError($e->getMessage(), 'DELETE_FAILED');
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }
}
