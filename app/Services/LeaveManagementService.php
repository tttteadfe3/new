<?php

namespace App\Services;

use App\Repositories\LeaveRepository;
use App\Services\LeaveCalculationService;
use App\Repositories\EmployeeRepository; // 네임스페이스 추가
use Exception;

class LeaveManagementService
{
    protected LeaveRepository $leaveRepository;
    protected LeaveCalculationService $leaveCalculationService;
    protected EmployeeRepository $employeeRepository;

    public function __construct(
        LeaveRepository $leaveRepository,
        LeaveCalculationService $leaveCalculationService,
        EmployeeRepository $employeeRepository // 타입 힌팅 수정
    ) {
        $this->leaveRepository = $leaveRepository;
        $this->leaveCalculationService = $leaveCalculationService;
        $this->employeeRepository = $employeeRepository;
    }

    private function executeLeaveTransaction(int $employeeId, int $year, float $days, string $column, string $logType, string $reason, ?int $processorId, ?int $requestId = null): bool
    {
        if ($days <= 0) return true;

        // Delegate the transaction to the new repository method
        return $this->leaveRepository->grantLeave($employeeId, $year, $days, $column, $logType, $reason, $processorId);
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

    public function approveLeaveRequest(int $requestId, int $adminId): bool
    {
        $request = $this->leaveRepository->findRequestById($requestId);
        if (!$request || $request['status'] !== 'pending') {
            return false;
        }

        // Use a dedicated repository method to handle the transaction
        return $this->leaveRepository->processLeaveUsage($requestId, $adminId);
    }

    public function rejectLeaveRequest(int $requestId, int $adminId, string $reason): bool
    {
        $request = $this->leaveRepository->findRequestById($requestId);
        if (!$request || $request['status'] !== 'pending') {
            return false;
        }
        return $this->leaveRepository->updateRequestStatus($requestId, 'rejected', [
            'approver_id' => $adminId,
            'rejection_reason' => $reason
        ]);
    }

    public function approveCancellationRequest(int $requestId, int $adminId): bool
    {
        $request = $this->leaveRepository->findRequestById($requestId);
        if (!$request || $request['status'] !== 'cancellation_requested') {
            return false;
        }
        // Use a dedicated repository method to handle the transaction
        return $this->leaveRepository->processLeaveCancellation($requestId, $adminId);
    }

    public function rejectCancellationRequest(int $requestId, int $adminId): bool
    {
        $request = $this->leaveRepository->findRequestById($requestId);
        if (!$request || $request['status'] !== 'cancellation_requested') {
            return false;
        }
        // Revert status to 'approved'
        return $this->leaveRepository->updateRequestStatus($requestId, 'approved', ['approver_id' => $adminId]);
    }

    public function expireUnusedLeaveForAll(int $year, int $adminId): array
    {
        $failedEmployees = [];
        $balances = $this->leaveRepository->getRemainingBalancesForYear($year);

        foreach ($balances as $balance) {
            if ($balance['remaining_leave'] > 0) {
                try {
                    $this->leaveRepository->expireLeave($balance['employee_id'], $year, $balance['remaining_leave'], $adminId);
                } catch (Exception $e) {
                    $failedEmployees[] = ['id' => $balance['employee_id'], 'error' => $e->getMessage()];
                }
            }
        }
        return ['failed_ids' => $failedEmployees];
    }

    public function manualAdjustment(int $employeeId, int $year, float $days, string $reason, int $adminId): bool
    {
        if ($days == 0) return false;

        $logType = $days > 0 ? 'adjust_add' : 'adjust_deduct';
        // Adjustments are assumed to modify the 'base' leave bucket
        return $this->leaveRepository->applyAdjustment($employeeId, $year, $days, 'base_leave', $logType, $reason, $adminId);
    }

    public function canRequestLeave(int $employeeId, string $startDate, float $requestDays): bool
    {
        if ($requestDays <= 0) return true;

        $year = (int)date('Y', strtotime($startDate));
        $balance = $this->leaveRepository->findBalanceForEmployee($employeeId, $year);

        return $balance && $balance['remaining_leave'] >= $requestDays;
    }
}
