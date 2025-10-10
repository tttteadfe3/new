<?php

namespace App\Controllers\Api;

use App\Services\MenuManagementService;
use Exception;

class MenuApiController extends BaseApiController
{
    private MenuManagementService $menuManagementService;

    public function __construct()
    {
        parent::__construct();
        $this->menuManagementService = new MenuManagementService();
    }

    /**
     * Get all menus
     */
    public function index(): void
    {
        try {
            $menus = $this->menuManagementService->getAllMenusForAdmin();
            $this->apiSuccess($menus);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * Get a single menu by ID
     */
    public function show(int $id): void
    {
        try {
            if (empty($id)) {
                $this->apiBadRequest('ID가 필요합니다.');
                return;
            }

            $menu = $this->menuManagementService->getMenu($id);

            if ($menu) {
                $this->apiSuccess($menu);
            } else {
                $this->apiNotFound('메뉴를 찾을 수 없습니다.');
            }
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * Create a new menu
     */
    public function store(): void
    {
        try {
            $data = $this->getJsonInput();
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->apiBadRequest('잘못된 JSON 형식입니다.');
                return;
            }
            
            $name = $data['name'] ?? '';
            if (empty($name)) {
                $this->apiBadRequest('메뉴 이름은 필수입니다.');
                return;
            }

            $newId = $this->menuManagementService->createMenu($data);
            $this->apiSuccess(['message' => '메뉴가 생성되었습니다.', 'id' => $newId], 201);
            
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * Update an existing menu
     */
    public function update(int $id): void
    {
        try {
            if (empty($id)) {
                $this->apiBadRequest('ID가 필요합니다.');
                return;
            }

            $data = $this->getJsonInput();
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->apiBadRequest('잘못된 JSON 형식입니다.');
                return;
            }

            $name = $data['name'] ?? '';
            if (empty($name)) {
                $this->apiBadRequest('메뉴 이름은 필수입니다.');
                return;
            }

            $this->menuManagementService->updateMenu($id, $data);
            $this->apiSuccess(['message' => '메뉴가 업데이트되었습니다.']);
            
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * Update menu order and hierarchy
     */
    public function updateOrder(): void
    {
        try {
            $data = $this->getJsonInput();
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->apiBadRequest('잘못된 JSON 형식입니다.');
                return;
            }

            // Frontend sends data wrapped in an 'updates' key
            $updates = $data['updates'] ?? null;
            if (!is_array($updates)) {
                $this->apiBadRequest('메뉴 업데이트 데이터가 필요합니다.');
                return;
            }

            $this->menuManagementService->updateOrderAndHierarchy($updates);
            $this->apiSuccess(['message' => '메뉴 순서가 업데이트되었습니다.']);

        } catch (Exception $e) {
            // Service layer handles rollback, controller just reports error
            $this->handleException($e);
        }
    }

    /**
     * Delete a menu
     */
    public function destroy(int $id): void
    {
        try {
            if (!$id) {
                $this->apiBadRequest('ID가 필요합니다.');
                return;
            }
            
            $success = $this->menuManagementService->deleteMenu($id);
            
            if ($success) {
                $this->apiSuccess(['message' => '메뉴가 삭제되었습니다.']);
            } else {
                $this->apiBadRequest('하위 메뉴가 있는 메뉴는 삭제할 수 없습니다.');
            }
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }
}