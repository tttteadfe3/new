<?php

namespace App\Services;

use App\Repositories\HumanResourceRepository;
use App\Repositories\EmployeeRepository;
use App\Repositories\DepartmentRepository;
use App\Repositories\PositionRepository;
use App\Repositories\EmployeeChangeLogRepository;
use App\Core\SessionManager;

class HumanResourceService
{
    private HumanResourceRepository $hrRepository;
    private EmployeeRepository $employeeRepository;
    private DepartmentRepository $departmentRepository;
    private PositionRepository $positionRepository;
    private EmployeeChangeLogRepository $logRepository;
    private SessionManager $sessionManager;

    public function __construct(
        HumanResourceRepository $hrRepository,
        EmployeeRepository $employeeRepository,
        DepartmentRepository $departmentRepository,
        PositionRepository $positionRepository,
        EmployeeChangeLogRepository $logRepository,
        SessionManager $sessionManager
    ) {
        $this->hrRepository = $hrRepository;
        $this->employeeRepository = $employeeRepository;
        $this->departmentRepository = $departmentRepository;
        $this->positionRepository = $positionRepository;
        $this->logRepository = $logRepository;
        $this->sessionManager = $sessionManager;
    }

    /**
     * 인사 발령(부서/직급 변경)을 처리합니다.
     *
     * @param int $employeeId 대상 직원 ID
     * @param int|null $newDepartmentId 새 부서 ID
     * @param int|null $newPositionId 새 직급 ID
     * @param string $orderDate 발령일
     * @return bool
     * @throws \InvalidArgumentException
     */
    public function issueOrder(int $employeeId, ?int $newDepartmentId, ?int $newPositionId, string $orderDate): bool
    {
        $employee = $this->employeeRepository->findById($employeeId);
        if (!$employee) {
            throw new \InvalidArgumentException('직원을 찾을 수 없습니다.');
        }

        $adminUser = $this->sessionManager->get('user');
        if (!$adminUser) {
            // 또는 적절한 예외 처리
            return false;
        }

        $changerId = $adminUser['employee_id'] ?? $adminUser['id'];

        $updates = [];
        if ($newDepartmentId !== null && $employee['department_id'] != $newDepartmentId) {
            $updates['department_id'] = $newDepartmentId;
            $oldDept = $this->departmentRepository->findById($employee['department_id']);
            $newDept = $this->departmentRepository->findById($newDepartmentId);
            $this->logRepository->insert(
                $employeeId,
                $changerId,
                '부서',
                $oldDept ? $oldDept->name : $employee['department_id'],
                $newDept ? $newDept->name : $newDepartmentId,
                $orderDate
            );
        }

        if ($newPositionId !== null && $employee['position_id'] != $newPositionId) {
            $updates['position_id'] = $newPositionId;
            $oldPos = $this->positionRepository->findById($employee['position_id']);
            $newPos = $this->positionRepository->findById($newPositionId);
            $this->logRepository->insert(
                $employeeId,
                $changerId,
                '직급',
                $oldPos ? $oldPos->name : $employee['position_id'],
                $newPos ? $newPos->name : $newPositionId,
                $orderDate
            );
        }

        if (empty($updates)) {
            // 변경 사항이 없으면 아무것도 하지 않음
            return true;
        }

        return $this->hrRepository->updateEmployeeAssignment($employeeId, $updates);
    }

    /**
     * 특정 직원의 인사 발령 기록을 가져옵니다.
     *
     * @param int $employeeId
     * @return array
     */
    public function getHistory(int $employeeId): array
    {
        return $this->logRepository->findAssignmentHistoryByEmployeeId($employeeId);
    }
}
