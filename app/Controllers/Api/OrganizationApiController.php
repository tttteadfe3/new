<?php

namespace App\Controllers\Api;

use App\Services\OrganizationService;
use App\Repositories\PositionRepository;
use Exception;
use App\Core\Request;
use App\Services\AuthService;
use App\Services\ViewDataService;
use App\Services\ActivityLogger;
use App\Repositories\EmployeeRepository;
use App\Core\JsonResponse;
use App\Services\DataScopeService;
use App\Repositories\DepartmentRepository;

class OrganizationApiController extends BaseApiController
{
    private OrganizationService $organizationService;
    private PositionRepository $positionRepository;
    private DataScopeService $dataScopeService;
    private DepartmentRepository $departmentRepository;

    public function __construct(
        Request $request,
        AuthService $authService,
        ViewDataService $viewDataService,
        ActivityLogger $activityLogger,
        EmployeeRepository $employeeRepository,
        JsonResponse $jsonResponse,
        OrganizationService $organizationService,
        PositionRepository $positionRepository,
        DataScopeService $dataScopeService,
        DepartmentRepository $departmentRepository
    ) {
        parent::__construct($request, $authService, $viewDataService, $activityLogger, $employeeRepository, $jsonResponse);
        $this->organizationService = $organizationService;
        $this->positionRepository = $positionRepository;
        $this->dataScopeService = $dataScopeService;
        $this->departmentRepository = $departmentRepository;
    }

    /**
     * 조직도 데이터를 계층 구조로 반환합니다.
     * GET /api/organization/chart
     */
    public function getChart(): void
    {
        try {
            $chartData = $this->organizationService->getOrganizationChartData();
            $this->apiSuccess($chartData);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    public function getEligibleViewerEmployees(int $departmentId): void
    {
        try {
            $data = $this->organizationService->getEligibleViewerEmployees($departmentId);
            $this->apiSuccess($data);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    public function getDepartmentViewPermissions(int $departmentId): void
    {
        try {
            $data = $this->organizationService->getDepartmentViewPermissionIds($departmentId);
            $this->apiSuccess($data);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    public function getManagableDepartments(): void
    {
        try {
            $departments = $this->departmentRepository->getAll();
            $this->apiSuccess($departments);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    public function index(): void
    {
        try {
            $type = $_GET['type'] ?? '';
            $data = [];
            if ($type === 'department') {
                $data = $this->organizationService->getFormattedDepartmentListWithHierarchy();
            } elseif ($type === 'position') {
                $data = $this->positionRepository->getAll();
            } else {
                 throw new Exception('잘못된 엔티티 유형이 지정되었습니다.');
            }
            $this->apiSuccess($data);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    public function store(): void
    {
        try {
            $input = $this->getJsonInput();
            $type = $input['type'] ?? '';
            $name = trim($input['name'] ?? '');

            if (empty($name)) {
                $this->apiBadRequest('이름은 필수입니다.');
            }

            $entityName = '';

            if ($type === 'department') {
                $entityName = '부서';
                $newId = $this->organizationService->createDepartment($input); // 전체 페이로드 전달
            } elseif ($type === 'position') {
                $entityName = '직급';
                $newId = $this->positionRepository->create($name);
            } else {
                throw new Exception('잘못된 엔티티 유형이 지정되었습니다.');
            }

            $this->apiSuccess(['new_id' => $newId], '새 ' . $entityName . '(이)가 생성되었습니다.');

        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    public function update(int $id): void
    {
        try {
            $input = $this->getJsonInput();
            $type = $input['type'] ?? '';
            $name = trim($input['name'] ?? '');

            if (empty($name)) {
                $this->apiBadRequest('이름은 필수입니다.');
            }

            $entityName = '';
            if ($type === 'department') {
                $entityName = '부서';
                $this->organizationService->updateDepartment($id, $input); // 전체 페이로드 전달
            } elseif ($type === 'position') {
                $entityName = '직급';
                $this->positionRepository->update($id, $name);
            } else {
                throw new Exception('잘못된 엔티티 유형이 지정되었습니다.');
            }

            $this->apiSuccess(null, $entityName . ' 정보가 수정되었습니다.');

        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    public function destroy(int $id): void
    {
        try {
            $type = $this->getJsonInput()['type'] ?? $_GET['type'] ?? '';
            $entityName = '';
            $result = false;

            if ($type === 'department') {
                $entityName = '부서';
                $result = $this->organizationService->deleteDepartment($id);
            } elseif ($type === 'position') {
                $entityName = '직급';
                $result = $this->positionRepository->delete($id);
            } else {
                throw new Exception('잘못된 엔티티 유형이 지정되었습니다.');
            }

            if ($result) {
                $this->apiSuccess(null, $entityName . '(이)가 삭제되었습니다.');
            } else {
                $this->apiError('직원이 할당된 ' . $entityName . '은(는) 삭제할 수 없습니다.');
            }
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }
}
