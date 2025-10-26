<?php

namespace App\Controllers\Api;

use App\Services\MenuManagementService;
use Exception;
use App\Core\Request;
use App\Services\AuthService;
use App\Services\ViewDataService;
use App\Services\ActivityLogger;
use App\Repositories\EmployeeRepository;
use App\Core\JsonResponse;

class MenuApiController extends BaseApiController
{
    private MenuManagementService $menuManagementService;

    public function __construct(
        Request $request,
        AuthService $authService,
        ViewDataService $viewDataService,
        ActivityLogger $activityLogger,
        EmployeeRepository $employeeRepository,
        JsonResponse $jsonResponse,
        MenuManagementService $menuManagementService
    ) {
        parent::__construct(
            $request,
            $authService,
            $viewDataService,
            $activityLogger,
            $employeeRepository,
            $jsonResponse
        );
        $this->menuManagementService = $menuManagementService;
    }

    /**
     * 모든 메뉴를 가져옵니다
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
     * ID로 단일 메뉴를 가져옵니다
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
     * 새 메뉴를 만듭니다
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
     * 기존 메뉴를 업데이트합니다
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
     * 메뉴 순서 및 계층 구조 업데이트
     */
    public function updateOrder(): void
    {
        try {
            $data = $this->getJsonInput();
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->apiBadRequest('잘못된 JSON 형식입니다.');
                return;
            }

            // 프론트엔드는 'updates' 키에 래핑된 데이터를 보냅니다
            $updates = $data['updates'] ?? null;
            if (!is_array($updates)) {
                $this->apiBadRequest('메뉴 업데이트 데이터가 필요합니다.');
                return;
            }

            $this->menuManagementService->updateOrderAndHierarchy($updates);
            $this->apiSuccess(['message' => '메뉴 순서가 업데이트되었습니다.']);

        } catch (Exception $e) {
            // 서비스 계층은 롤백을 처리하고 컨트롤러는 오류만 보고합니다
            $this->handleException($e);
        }
    }

    /**
     * 메뉴 삭제
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
