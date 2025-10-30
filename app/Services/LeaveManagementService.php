<?php

namespace App\Services;

use App\Repositories\LeaveRepository;
use App\Services\LeaveCalculationService;
use Exception;

class LeaveManagementService
{
    protected LeaveRepository $leaveRepository;
    protected LeaveCalculationService $leaveCalculationService;

    public function __construct(LeaveRepository $leaveRepository, LeaveCalculationService $leaveCalculationService)
    {
        $this->leaveRepository = $leaveRepository;
        $this->leaveCalculationService = $leaveCalculationService;
    }

    private function executeLeaveTransaction(int $employeeId, int $year, float $days, string $column, string $logType, string $reason, ?int $processorId, ?int $requestId = null): bool
    {
        if ($days == 0) return true;

        $this->leaveRepository->beginTransaction();
        try {
            $this->leaveRepository->updateLeaveBalance($employeeId, $year, $days, $column);
            $this->leaveRepository->createLeaveLog($employeeId, $logType, $days, $reason, $processorId, $requestId);
            $this->leaveRepository->commit();
            return true;
        } catch (Exception $e) {
            $this->leaveRepository->rollBack();
            throw $e;
        }
    }

    public function grantAnnualLeaveToAllEmployees(int $year, int $processorId): array
    {
        $failedEmployees = [];
        $employees = $this->leaveRepository->getAllActiveEmployees();

        foreach ($employees as $employee) {
            try {
                $entitlement = $this->leaveCalculationService->calculateLeaveEntitlementForYear($employee['hire_date'], $year);
                $baseLeave = $entitlement['base'];
                $seniorityLeave = $entitlement['seniority'];

                if ($baseLeave > 0) {
                    $this->executeLeaveTransaction($employee['id'], $year, $baseLeave, 'base_leave', 'grant_base', "{$year}년 연차 부여", $processorId);
                }
                if ($seniorityLeave > 0) {
                    $this->executeLeaveTransaction($employee['id'], $year, $seniorityLeave, 'seniority_leave', 'grant_seniority', "{$year}년 근속 가산 연차 부여", $processorId);
                }
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
            $baseLeave = $entitlement['base'];
            $seniorityLeave = $entitlement['seniority'];

            // 미리보기에서는 부여될 연차가 0인 경우도 포함하여 모두 보여줌
            $previewData[] = [
                'employee_id' => $employee['id'],
                'employee_name' => $employee['name'],
                'department_name' => $employee['department_name'],
                'hire_date' => $employee['hire_date'],
                'base_leave_to_grant' => $baseLeave,
                'seniority_leave_to_grant' => $seniorityLeave,
                'total_to_grant' => $baseLeave + $seniorityLeave
            ];
        }
        return $previewData;
    }

    // ... (approve, reject, etc. methods are unchanged)

    public function approveLeaveRequest(int $requestId, int $adminId): bool
    {
        $request = $this->leaveRepository->findRequestById($requestId);
        if (!$request || $request['status'] !== 'pending') {
            throw new Exception('이미 처리되었거나 유효하지 않은 신청입니다.');
        }

        $year = (int)date('Y', strtotime($request['start_date']));
        $daysToUse = (float)$request['days_count'];

        $this->leaveRepository->beginTransaction();
        try {
            $this->leaveRepository->updateLeaveBalance($request['employee_id'], $year, $daysToUse, 'used_leave');
            $this->leaveRepository->createLeaveLog($request['employee_id'], 'use', -$daysToUse, "연차 사용 승인", $adminId, $requestId);
            $this->leaveRepository->updateRequestStatus($requestId, 'approved');
            $this->leaveRepository->commit();
            return true;
        } catch (Exception $e) {
            $this->leaveRepository->rollBack();
            throw $e;
        }
    }

    public function rejectLeaveRequest(int $requestId, int $adminId, string $reason): bool
    {
        $request = $this->leaveRepository->findRequestById($requestId);
        if (!$request || $request['status'] !== 'pending') {
             throw new Exception('이미 처리되었거나 유효하지 않은 신청입니다.');
        }
        return $this->leaveRepository->updateRequestStatus($requestId, 'rejected', ['rejection_reason' => $reason]);
    }

    public function approveCancellationRequest(int $requestId, int $adminId): bool
    {
        $request = $this->leaveRepository->findRequestById($requestId);
        if (!$request || $request['status'] !== 'cancellation_requested') {
            throw new Exception('이미 처리되었거나 유효하지 않은 취소 요청입니다.');
        }

        $year = (int)date('Y', strtotime($request['start_date']));
        $daysToRestore = (float)$request['days_count'];

        $this->leaveRepository->beginTransaction();
        try {
            $this->leaveRepository->updateLeaveBalance($request['employee_id'], $year, -$daysToRestore, 'used_leave');
            $this->leaveRepository->createLeaveLog($request['employee_id'], 'cancel_use', $daysToRestore, "연차 사용 취소 승인", $adminId, $requestId);
            $this->leaveRepository->updateRequestStatus($requestId, 'cancelled');
            $this->leaveRepository->commit();
            return true;
        } catch (Exception $e) {
            $this->leaveRepository->rollBack();
            throw $e;
        }
    }

    public function rejectCancellationRequest(int $requestId, int $adminId): bool
    {
        $request = $this->leaveRepository->findRequestById($requestId);
        if (!$request || $request['status'] !== 'cancellation_requested') {
            throw new Exception('이미 처리되었거나 유효하지 않은 취소 요청입니다.');
        }
        return $this->leaveRepository->updateRequestStatus($requestId, 'approved');
    }

    public function expireUnusedLeaveForAll(int $year, int $adminId): array
    {
        $failedEmployees = [];
        $balances = $this->leaveRepository->getUnusedBalancesByYear($year);

        foreach ($balances as $balance) {
            try {
                $totalLeave = $balance['base_leave'] + $balance['seniority_leave'] + $balance['monthly_leave'] + $balance['adjustment_leave'];
                $remaining = $totalLeave - $balance['used_leave'];

                if ($remaining > 0) {
                    $this->executeLeaveTransaction($balance['employee_id'], $year, $remaining, 'used_leave', 'expire', "{$year}년 미사용 연차 소멸", $adminId);
                }
            } catch (Exception $e) {
                $failedEmployees[] = $balance['employee_id'];
            }
        }
        return ['failed_ids' => $failedEmployees];
    }

    public function manualAdjustment(int $employeeId, int $year, float $days, string $reason, int $adminId): bool
    {
        $changeType = 'adjust_etc';
        if (str_contains($reason, '포상')) $changeType = 'adjust_reward';
        if (str_contains($reason, '징계')) $changeType = 'adjust_disciplinary';

        return $this->executeLeaveTransaction($employeeId, $year, $days, 'adjustment_leave', $changeType, $reason, $adminId);
    }

    public function canRequestLeave(int $employeeId, string $startDate, float $requestDays): bool
    {
        $year = (int)date('Y', strtotime($startDate));
        $balance = $this->leaveRepository->findBalanceByEmployeeAndYear($employeeId, $year);

        if (!$balance) {
            return false;
        }

        $totalGranted = ($balance['base_leave'] ?? 0) +
                        ($balance['seniority_leave'] ?? 0) +
                        ($balance['monthly_leave'] ?? 0) +
                        ($balance['adjustment_leave'] ?? 0);

        $used = $balance['used_leave'] ?? 0;
        $remaining = $totalGranted - $used;

        return $remaining >= $requestDays;
    }
}
