<?php

namespace App\Controllers\Api;

use App\Services\EmployeeService;
use App\Repositories\DepartmentRepository;
use App\Repositories\PositionRepository;
use Exception;
use App\Core\Request;
use App\Services\AuthService;
use App\Services\ViewDataService;
use App\Services\ActivityLogger;
use App\Repositories\EmployeeRepository;
use App\Core\JsonResponse;

class EmployeeApiController extends BaseApiController
{
    private EmployeeService $employeeService;
    private DepartmentRepository $departmentRepository;
    private PositionRepository $positionRepository;
    private \App\Services\DataScopeService $dataScopeService;

    public function __construct(
        Request $request,
        AuthService $authService,
        ViewDataService $viewDataService,
        ActivityLogger $activityLogger,
        EmployeeRepository $employeeRepository,
        JsonResponse $jsonResponse,
        EmployeeService $employeeService,
        DepartmentRepository $departmentRepository,
        PositionRepository $positionRepository,
        \App\Services\DataScopeService $dataScopeService
    ) {
        parent::__construct($request, $authService, $viewDataService, $activityLogger, $employeeRepository, $jsonResponse);
        $this->employeeService = $employeeService;
        $this->departmentRepository = $departmentRepository;
        $this->positionRepository = $positionRepository;
        $this->dataScopeService = $dataScopeService;
    }

    /**
     * 직원 관리 페이지에 필요한 초기 데이터를 가져옵니다. (부서 및 직급 목록)
     */
    public function getInitialData(): void
    {
        try {
            $visibleDeptIds = $this->dataScopeService->getVisibleDepartmentIdsForCurrentUser();

            if ($visibleDeptIds === null) {
                // 전체 조회 권한
                $departments = $this->departmentRepository->getAll();
            } elseif (empty($visibleDeptIds)) {
                // 조회 권한 부서 없음
                $departments = [];
            } else {
                // ID 목록으로 부서 정보 조회
                $departments = $this->departmentRepository->findByIds($visibleDeptIds);
            }

            $data = [
                'departments' => $departments,
                'positions' => $this->positionRepository->getAll(),
            ];
            $this->apiSuccess($data);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * 필터에 따라 모든 직원 목록을 가져옵니다.
     */
    public function index(): void
    {
        try {
            $filters = $this->request->all();
            $employees = $this->employeeService->getAllEmployees($filters);
            $this->apiSuccess($employees);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * 특정 직원의 상세 정보를 가져옵니다.
     * @param int $id 직원의 ID
     */
    public function show(int $id): void
    {
        if (!$this->dataScopeService->canManageEmployee($id)) {
            $this->apiForbidden('해당 직원의 정보를 조회할 권한이 없습니다.');
            return;
        }
        try {
            $employee = $this->employeeService->getEmployee($id);
            if ($employee) {
                $this->apiSuccess($employee);
            } else {
                $this->apiNotFound('직원을 찾을 수 없습니다.');
            }
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * 특정 직원의 정보를 업데이트합니다.
     * @param int $id 직원의 ID
     */
    public function update(int $id): void
    {
        if (!$this->dataScopeService->canManageEmployee($id)) {
            $this->apiForbidden('해당 직원의 정보를 수정할 권한이 없습니다.');
            return;
        }
        try {
            $data = $this->getJsonInput();
            $this->employeeService->updateEmployee($id, $data);
            $this->apiSuccess(null, '직원 정보가 성공적으로 수정되었습니다.');
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * 특정 직원을 삭제합니다.
     * @param int $id 직원의 ID
     */
    public function destroy(int $id): void
    {
        if (!$this->dataScopeService->canManageEmployee($id)) {
            $this->apiForbidden('해당 직원을 삭제할 권한이 없습니다.');
            return;
        }
        try {
            if ($this->employeeService->deleteEmployee($id)) {
                $this->apiSuccess(null, '직원이 성공적으로 삭제되었습니다.');
            } else {
                $this->apiError('직원 삭제에 실패했습니다.');
            }
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * 시스템 계정에 연결되지 않은 직원 목록을 가져옵니다.
     */
    public function unlinked(): void
    {
        try {
            $departmentId = $this->request->input('department_id', null);
            $departmentId = $departmentId ? (int)$departmentId : null;
            $unlinkedEmployees = $this->employeeService->getUnlinkedEmployees($departmentId);
            $this->apiSuccess($unlinkedEmployees);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * 새 직원을 생성합니다.
     */
    public function store(): void
    {
        if (!$this->authService->check('employee.create')) {
            $this->apiForbidden('직원을 생성할 권한이 없습니다.');
            return;
        }
        try {
            $data = $this->getJsonInput();
            $newEmployeeId = $this->employeeService->createEmployee($data);

            if ($newEmployeeId) {
                $this->apiSuccess(['id' => $newEmployeeId], '직원이 성공적으로 생성되었습니다.');
            } else {
                $this->apiError('직원 생성에 실패했습니다.');
            }
        } catch (\InvalidArgumentException $e) {
            $this->apiBadRequest($e->getMessage());
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * 특정 직원의 정보 변경 이력을 가져옵니다.
     * @param int $id 직원의 ID
     */
    public function getChangeHistory(int $id): void
    {
        if (!$this->dataScopeService->canManageEmployee($id)) {
            $this->apiForbidden('해당 직원의 변경 이력을 조회할 권한이 없습니다.');
            return;
        }
        try {
            $history = $this->employeeService->getEmployeeChangeHistory($id);
            $this->apiSuccess($history);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * 직원이 요청한 프로필 변경을 승인합니다.
     * @param int $id 직원의 ID
     */
    public function approveUpdate(int $id): void
    {
        if (!$this->authService->check('employee.approve')) {
            $this->apiForbidden('프로필 변경을 승인할 권한이 없습니다.');
            return;
        }
        try {
            if ($this->employeeService->approveProfileUpdate($id)) {
                $this->apiSuccess(null, '프로필 변경 요청이 승인되었습니다.');
            } else {
                $this->apiError('프로필 변경 요청 승인에 실패했습니다. 이미 처리되었거나 요청이 존재하지 않을 수 있습니다.');
            }
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * 직원이 요청한 프로필 변경을 반려합니다.
     * @param int $id 직원의 ID
     */
    public function rejectUpdate(int $id): void
    {
        if (!$this->authService->check('employee.approve')) {
            $this->apiForbidden('프로필 변경을 반려할 권한이 없습니다.');
            return;
        }
        try {
            $data = $this->getJsonInput();
            $reason = $data['reason'] ?? '';

            if (empty($reason)) {
                $this->apiBadRequest('반려 사유를 입력해야 합니다.');
                return;
            }

            if ($this->employeeService->rejectProfileUpdate($id, $reason)) {
                $this->apiSuccess(null, '프로필 변경 요청이 반려되었습니다.');
            } else {
                $this->apiError('프로필 변경 요청 반려에 실패했습니다.');
            }
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * 직원을 퇴사 처리합니다.
     * @param int $id 직원의 ID
     */
    public function terminate(int $id): void
    {
        if (!$this->authService->check('employee.terminate')) {
            $this->apiForbidden('직원을 퇴사 처리할 권한이 없습니다.');
            return;
        }
        try {
            $data = $this->getJsonInput();
            $terminationDate = $data['termination_date'] ?? null;

            if (empty($terminationDate)) {
                $this->apiBadRequest('퇴사일을 지정해야 합니다.');
                return;
            }

            if ($this->employeeService->terminateEmployee($id, $terminationDate)) {
                $this->apiSuccess(null, '직원이 성공적으로 퇴사 처리되었습니다.');
            } else {
                $this->apiError('직원 퇴사 처리에 실패했습니다.');
            }
        } catch (\InvalidArgumentException $e) {
            $this->apiBadRequest($e->getMessage());
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }
}
