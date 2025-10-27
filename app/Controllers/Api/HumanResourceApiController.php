<?php

namespace App\Controllers\Api;

use App\Core\Request;
use App\Services\AuthService;
use App\Services\ViewDataService;
use App\Services\ActivityLogger;
use App\Repositories\EmployeeRepository;
use App\Core\JsonResponse;
use App\Services\HumanResourceService;
use Exception;

class HumanResourceApiController extends BaseApiController
{
    private HumanResourceService $hrService;

    public function __construct(
        Request $request,
        AuthService $authService,
        ViewDataService $viewDataService,
        ActivityLogger $activityLogger,
        EmployeeRepository $employeeRepository,
        JsonResponse $jsonResponse,
        HumanResourceService $hrService
    ) {
        parent::__construct($request, $authService, $viewDataService, $activityLogger, $employeeRepository, $jsonResponse);
        $this->hrService = $hrService;
    }

    /**
     * 신규 인사 발령을 생성합니다.
     */
    public function store(): void
    {
        if (!$this->authService->check('employee.assign')) {
            $this->apiForbidden('인사 발령을 등록할 권한이 없습니다.');
            return;
        }

        try {
            $data = $this->getJsonInput();

            $employeeId = (int)($data['employee_id'] ?? 0);
            $departmentId = !empty($data['department_id']) ? (int)$data['department_id'] : null;
            $positionId = !empty($data['position_id']) ? (int)$data['position_id'] : null;
            $orderDate = $data['order_date'] ?? null;

            if (empty($employeeId) || empty($orderDate) || (empty($departmentId) && empty($positionId))) {
                $this->apiBadRequest('필수 입력 항목이 누락되었습니다. (직원, 발령일, 부서/직급 중 하나)');
                return;
            }

            $success = $this->hrService->issueOrder($employeeId, $departmentId, $positionId, $orderDate);

            if ($success) {
                $this->apiSuccess(null, '인사 발령이 성공적으로 등록되었습니다.');
            } else {
                $this->apiError('인사 발령 등록에 실패했습니다.');
            }
        } catch (\InvalidArgumentException $e) {
            $this->apiBadRequest($e->getMessage());
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }
}
