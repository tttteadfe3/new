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

    public function grantLeave(int $employeeId, int $year, float $days, string $changeType, string $reason, ?int $processorId = null): bool
    {
        $isDecrement = in_array($changeType, ['expire']); // used_leave를 증가시키는 경우
        $actualDays = $isDecrement ? abs($days) : $days;

        if ($actualDays == 0) return true;

        $columnMap = [
            'grant_base' => 'base_leave',
            'grant_seniority' => 'seniority_leave',
            'grant_monthly' => 'monthly_leave',
            'adjust_reward' => 'adjustment_leave',
            'adjust_disciplinary' => 'adjustment_leave',
            'adjust_etc' => 'adjustment_leave',
            'expire' => 'used_leave',
        ];
        $columnToUpdate = $columnMap[$changeType] ?? null;
        if (!$columnToUpdate) throw new Exception("Invalid change type: $changeType");

        $this->leaveRepository->beginTransaction();
        try {
            $this->leaveRepository->updateLeaveBalance($employeeId, $year, $actualDays, $columnToUpdate);
            $this->leaveRepository->createLeaveLog($employeeId, $changeType, -$actualDays, $reason, $processorId);
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
                // 로직 생략... (이전과 동일)
            } catch (Exception $e) {
                $failedEmployees[] = $employee['id'];
            }
        }
        return ['failed_ids' => $failedEmployees];
    }

    public function approveLeaveRequest(int $requestId, int $adminId): bool
    {
        // 로직 생략... (이전과 동일)
        return true;
    }

    public function rejectLeaveRequest(int $requestId, int $adminId, string $reason): bool
    {
        // 로직 생략... (이전과 동일)
        return true;
    }

    public function approveCancellationRequest(int $requestId, int $adminId): bool
    {
        // 로직 생략... (이전과 동일)
        return true;
    }

    public function rejectCancellationRequest(int $requestId, int $adminId): bool
    {
        // 로직 생략... (이전과 동일)
        return true;
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
                    $this->grantLeave($balance['employee_id'], $year, $remaining, 'expire', "{$year}년 미사용 연차 소멸", $adminId);
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

        $columnMap = [
            'adjust_reward' => 'adjustment_leave',
            'adjust_disciplinary' => 'adjustment_leave',
            'adjust_etc' => 'adjustment_leave',
        ];
        $columnToUpdate = $columnMap[$changeType];

        $this->leaveRepository->beginTransaction();
        try {
            $this->leaveRepository->updateLeaveBalance($employeeId, $year, $days, $columnToUpdate);
            $this->leaveRepository->createLeaveLog($employeeId, $changeType, $days, $reason, $adminId);
            $this->leaveRepository->commit();
            return true;
        } catch (Exception $e) {
            $this->leaveRepository->rollBack();
            throw $e;
        }
    }
}
