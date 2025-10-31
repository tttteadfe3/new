<?php

namespace App\Services;

use App\Repositories\LeaveRepository;
use App\Services\LeaveCalculationService;
use Exception;

class LeaveManagementService
{
    protected LeaveRepository $leaveRepository;
    protected LeaveCalculationService $leaveCalculationService;
    protected EmployeeRepository $employeeRepository;

    public function __construct(LeaveRepository $leaveRepository, LeaveCalculationService $leaveCalculationService, EmployeeRepository $employeeRepository)
    {
        $this->leaveRepository = $leaveRepository;
        $this->leaveCalculationService = $leaveCalculationService;
        $this->employeeRepository = $employeeRepository;
    }

    private function executeLeaveTransaction(int $employeeId, int $year, float $days, string $column, string $logType, string $reason, ?int $processorId, ?int $requestId = null): bool
    {
        if ($days <= 0) return true;

        return $this->leaveRepository->grantLeave($employeeId, $year, $days, $column, $logType, $reason, $processorId, $requestId);
    }

    /**
     * 신규 직원이 생성될 때 초기 연차(월차)를 부여합니다.
     */
    public function grantInitialLeaveForNewEmployee(int $employeeId, ?int $processorId = null): void
    {
        $employee = $this->employeeRepository->findById($employeeId);
        if (!$employee) return;

        $hireYear = (int)date('Y', strtotime($employee['hire_date']));

        $entitlement = $this->leaveCalculationService->calculateLeaveEntitlementForYear($employee['hire_date'], $hireYear);
        $monthlyLeave = $entitlement['monthly'];

        if ($monthlyLeave > 0) {
            $this->executeLeaveTransaction($employeeId, $hireYear, $monthlyLeave, 'monthly_leave', 'grant_monthly', "신규 입사자 월차 부여", $processorId);
        }
    }

    public function grantAnnualLeaveToAllEmployees(int $year, int $processorId): array
    {
        $failedEmployees = [];
        $employees = $this->leaveRepository->getAllActiveEmployees();

        foreach ($employees as $employee) {
            try {
                $entitlement = $this->leaveCalculationService->calculateLeaveEntitlementForYear($employee['hire_date'], $year);

                $this->executeLeaveTransaction($employee['id'], $year, $entitlement['base'], 'base_leave', 'grant_base', "{$year}년 연차 부여", $processorId);
                $this->executeLeaveTransaction($employee['id'], $year, $entitlement['seniority'], 'seniority_leave', 'grant_seniority', "{$year}년 근속 가산 연차 부여", $processorId);
                $this->executeLeaveTransaction($employee['id'], $year, $entitlement['monthly'], 'monthly_leave', 'grant_monthly', "{$year}년 월차 부여", $processorId);

            } catch (Exception $e) {
                $failedEmployees[] = ['id' => $employee['id'], 'error' => $e->getMessage()];
            }
        }
        return ['failed_ids' => $failedEmployees];
    }

    public function previewAnnualLeaveGrant(int $year, ?int $departmentId = null): array
    {
        $employees = $this->leaveRepository->getAllActiveEmployeesWithDetails($departmentId);
        $previewData = [];

        foreach ($employees as $employee) {
            $entitlement = $this->leaveCalculationService->calculateLeaveEntitlementForYear($employee['hire_date'], $year);

            $previewData[] = [
                'employee_id' => $employee['id'],
                'employee_name' => $employee['name'],
                'department_name' => $employee['department_name'],
                'hire_date' => $employee['hire_date'],
                'base_leave_to_grant' => $entitlement['base'],
                'monthly_leave_to_grant' => $entitlement['monthly'],
                'seniority_leave_to_grant' => $entitlement['seniority'],
                'total_to_grant' => $entitlement['base'] + $entitlement['seniority'] + $entitlement['monthly']
            ];
        }
        return $previewData;
    }

    // ... (other methods remain unchanged)
    public function approveLeaveRequest(int $requestId, int $adminId): bool
    {
        // ...
    }
    public function rejectLeaveRequest(int $requestId, int $adminId, string $reason): bool
    {
        // ...
    }
    public function approveCancellationRequest(int $requestId, int $adminId): bool
    {
        // ...
    }
    public function rejectCancellationRequest(int $requestId, int $adminId): bool
    {
        // ...
    }
    public function expireUnusedLeaveForAll(int $year, int $adminId): array
    {
        // ...
    }
    public function manualAdjustment(int $employeeId, int $year, float $days, string $reason, int $adminId): bool
    {
        // ...
    }
    public function canRequestLeave(int $employeeId, string $startDate, float $requestDays): bool
    {
        // ...
    }
}
