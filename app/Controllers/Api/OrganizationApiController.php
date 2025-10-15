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

class OrganizationApiController extends BaseApiController
{
    private OrganizationService $organizationService;
    private PositionRepository $positionRepository;

    public function __construct(
        Request $request,
        AuthService $authService,
        ViewDataService $viewDataService,
        ActivityLogger $activityLogger,
        EmployeeRepository $employeeRepository,
        JsonResponse $jsonResponse,
        OrganizationService $organizationService,
        PositionRepository $positionRepository
    ) {
        parent::__construct($request, $authService, $viewDataService, $activityLogger, $employeeRepository, $jsonResponse);
        $this->organizationService = $organizationService;
        $this->positionRepository = $positionRepository;
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

    public function getManagableDepartments(): void
    {
        try {
            $data = $this->organizationService->getManagableDepartments();
            $this->apiSuccess($data);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    public function index(): void
    {
        try {
            $type = $_GET['type'] ?? '';
            $context = $_GET['context'] ?? 'default'; // default, management
            $data = [];

            if ($type === 'department') {
                if ($context === 'management') {
                    $data = $this->organizationService->getFormattedDepartmentListForAll();
                } else {
                    $data = $this->organizationService->getAllDepartments();
                }
            } elseif ($type === 'position') {
                $data = $this->positionRepository->getAll();
            } else {
                throw new Exception('Invalid entity type specified.');
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
                $newId = $this->organizationService->createDepartment($input); // Pass the whole payload
            } elseif ($type === 'position') {
                $entityName = '직급';
                $newId = $this->positionRepository->create($name);
            } else {
                throw new Exception('Invalid entity type specified.');
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
                $this->organizationService->updateDepartment($id, $input); // Pass the whole payload
            } elseif ($type === 'position') {
                $entityName = '직급';
                $this->positionRepository->update($id, $name);
            } else {
                throw new Exception('Invalid entity type specified.');
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
                throw new Exception('Invalid entity type specified.');
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
