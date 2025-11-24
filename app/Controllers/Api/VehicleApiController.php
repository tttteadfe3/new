<?php

namespace App\Controllers\Api;

use App\Services\VehicleService;
use App\Core\Request;
use App\Services\AuthService;
use App\Services\ViewDataService;
use App\Services\ActivityLogger;
use App\Repositories\EmployeeRepository;
use App\Core\JsonResponse;
use Exception;

class VehicleApiController extends BaseApiController
{
    private VehicleService $vehicleService;
    private \App\Services\DataScopeService $dataScopeService;

    public function __construct(
        Request $request,
        AuthService $authService,
        ViewDataService $viewDataService,
        ActivityLogger $activityLogger,
        EmployeeRepository $employeeRepository,
        JsonResponse $jsonResponse,
        VehicleService $vehicleService,
        \App\Services\DataScopeService $dataScopeService
    ) {
        parent::__construct($request, $authService, $viewDataService, $activityLogger, $employeeRepository, $jsonResponse);
        $this->vehicleService = $vehicleService;
        $this->dataScopeService = $dataScopeService;
    }

    public function index(): void
    {
        try {
            $filters = [
                'department_id' => $this->request->input('department_id'),
                'status_code' => $this->request->input('status_code'),
                'search' => $this->request->input('search')
            ];

            $user = $this->authService->user();
            
            // Visibility Logic
            // 1. Admin/Manager with full access -> DataScope returns null
            // 2. Department Manager -> DataScope returns list of department IDs
            // 3. Driver (Regular Employee) -> DataScope returns list of department IDs (viewable) OR empty.
            //    BUT we want to restrict Driver to ONLY their own car if they are not a manager.
            
            // How to distinguish Manager vs Driver?
            // DataScopeService doesn't explicitly say "is manager".
            // But we can check if the user has 'vehicle.manage' or 'vehicle.view.all' permission.
            // If not, we rely on DataScope + Own Car logic.
            
            // Let's assume:
            // - If DataScope returns NULL, it means full access (Admin).
            // - If DataScope returns IDs, we use them as 'visible_department_ids'.
            // - PLUS, we always add 'current_user_driver_id' to allow seeing own car even if not in visible departments (though usually it is).
            
            // Wait, the user requirement: "Department Manager sees department's cars. Driver sees only their own car."
            // This implies a Driver should NOT see other cars in the same department.
            // So if I am a Driver, I should NOT have 'visible_department_ids' populated with my department ID for the purpose of vehicle listing.
            
            // We need a way to know if the user is a "Department Manager" for the purpose of Vehicles.
            // Maybe we can check if the user is in `hr_department_managers` table?
            // DataScopeService::findDepartmentIdsWithEmployeeViewPermission($employeeId) does this.
            // But it's private.
            
            // Let's rely on `getVisibleDepartmentIdsForCurrentUser`.
            // If it returns IDs, does it mean I am a manager?
            // Not necessarily, it includes `hr_department_view_permissions` which might be granted to regular employees?
            // Usually `hr_department_view_permissions` is for cross-department access for managers.
            // Regular employees usually don't have entries there.
            
            // So, if `getVisibleDepartmentIdsForCurrentUser` returns non-empty, it's likely they have some extended view rights.
            // But for a basic driver, it might return empty (if they are not a manager).
            
            // However, `DataScopeService` logic (lines 54-66) only adds IDs if:
            // 1. User is in `hr_department_managers`.
            // 2. User's department has `hr_department_view_permissions`.
            
            // So, a normal driver (not manager, no special dept perms) will get `[]` (empty array) from `getVisibleDepartmentIdsForCurrentUser`.
            // In that case, we ONLY show their own car.
            
            // If they are a manager, they get `[dept_id, ...]`. We show cars in those depts + their own car.
            
            // So the logic in Repository (OR) works perfectly:
            // If `visible_department_ids` is empty, we only match `driver_employee_id`.
            // If `visible_department_ids` has values, we match those depts OR `driver_employee_id`.
            
            $visibleDeptIds = $this->dataScopeService->getVisibleDepartmentIdsForCurrentUser();
            
            if ($visibleDeptIds !== null) {
                // Not an admin (null means all access)
                $filters['visible_department_ids'] = $visibleDeptIds;
                
                if ($user && $user['employee_id']) {
                    $filters['current_user_driver_id'] = $user['employee_id'];
                }
            }

            $filters = array_filter($filters, function($value) {
                return $value !== null && $value !== '';
            });

            $vehicles = $this->vehicleService->getAllVehicles($filters);
            $this->apiSuccess($vehicles);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    public function show(int $id): void
    {
        try {
            $vehicle = $this->vehicleService->getVehicleById($id);
            if (!$vehicle) {
                $this->apiNotFound('차량 정보를 찾을 수 없습니다.');
                return;
            }
            $this->apiSuccess($vehicle);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    public function store(): void
    {
        try {
            $data = $this->getJsonInput();
            $id = $this->vehicleService->registerVehicle($data);
            $this->apiSuccess(['id' => $id, 'message' => '차량이 등록되었습니다.'], 201);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    public function update(int $id): void
    {
        try {
            $data = $this->getJsonInput();
            $success = $this->vehicleService->updateVehicle($id, $data);
            if ($success) {
                $this->apiSuccess(['message' => '차량 정보가 수정되었습니다.']);
            } else {
                $this->apiError('차량 정보 수정에 실패했습니다.');
            }
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    public function destroy(int $id): void
    {
        try {
            $success = $this->vehicleService->deleteVehicle($id);
            if ($success) {
                $this->apiSuccess(['message' => '차량이 삭제되었습니다.']);
            } else {
                $this->apiError('차량 삭제에 실패했습니다.');
            }
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    protected function handleException(Exception $e): void
    {
        if ($e instanceof \InvalidArgumentException) {
            $this->apiBadRequest($e->getMessage());
        } else {
            $this->apiError('서버 오류가 발생했습니다: ' . $e->getMessage());
        }
    }
}
