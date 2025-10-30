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

    /**
     * 연차 부여/차감/조정의 핵심 트랜잭션 메소드
     */
    private function executeLeaveTransaction(int $employeeId, int $year, float $days, string $column, string $logType, string $reason, ?int $processorId, ?int $requestId = null): bool
    {
        if ($days == 0) return true;

        $this->leaveRepository->beginTransaction();
        try {
            // 1. Balance 테이블 업데이트
            $this->leaveRepository->updateLeaveBalance($employeeId, $year, $days, $column);
            // 2. Log 테이블 기록
            $this->leaveRepository->createLeaveLog($employeeId, $logType, $days, $reason, $processorId, $requestId);
            $this->leaveRepository->commit();
            return true;
        } catch (Exception $e) {
            $this->leaveRepository->rollBack();
            throw $e; // 에러를 상위로 전파하여 컨트롤러에서 처리
        }
    }

    public function grantAnnualLeaveToAllEmployees(int $year, int $processorId): array
    {
        $failedEmployees = [];
        $employees = $this->leaveRepository->getAllActiveEmployees();

        foreach ($employees as $employee) {
            try {
                $baseLeave = 15;
                $this->executeLeaveTransaction($employee['id'], $year, $baseLeave, 'base_leave', 'grant_base', "{$year}년 정기 연차 부여", $processorId);

                $seniorityLeave = $this->leaveCalculationService->calculateSeniorityLeave($employee['hire_date'], $year);
                if ($seniorityLeave > 0) {
                    $this->executeLeaveTransaction($employee['id'], $year, $seniorityLeave, 'seniority_leave', 'grant_seniority', "{$year}년 근속 가산 연차 부여", $processorId);
                }
            } catch (Exception $e) {
                $failedEmployees[] = $employee['id'];
            }
        }
        return ['failed_ids' => $failedEmployees];
    }

    public function previewAnnualLeaveGrant(int $year): array
    {
        $employees = $this->leaveRepository->getAllActiveEmployeesWithDetails();
        $previewData = [];

        foreach ($employees as $employee) {
            $baseLeave = 15; // Assuming a flat rate for preview
            $seniorityLeave = $this->leaveCalculationService->calculateSeniorityLeave($employee['hire_date'], $year);

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

    public function approveLeaveRequest(int $requestId, int $adminId): bool
    {
        $request = $this->leaveRepository->findRequestById($requestId);
        if (!$request || $request['status'] !== 'pending') {
            throw new Exception('이미 처리되었거나 유효하지 않은 신청입니다.');
        }

        $year = (int)date('Y', strtotime($request['start_date']));
        $daysToUse = (float)$request['days_count'];

        // 트랜잭션 시작
        $this->leaveRepository->beginTransaction();
        try {
            // 연차 사용 기록 (used_leave 증가)
            $this->leaveRepository->updateLeaveBalance($request['employee_id'], $year, $daysToUse, 'used_leave');
            // 로그 기록
            $this->leaveRepository->createLeaveLog($request['employee_id'], 'use', -$daysToUse, "연차 사용 승인", $adminId, $requestId);
            // 신청 상태 변경
            $this->leaveRepository->updateRequestStatus($requestId, 'approved', ['approver_id' => $adminId]);

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
        return $this->leaveRepository->updateRequestStatus($requestId, 'rejected', ['approver_id' => $adminId, 'rejection_reason' => $reason]);
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
            // 연차 복구 (used_leave 감소)
            $this->leaveRepository->updateLeaveBalance($request['employee_id'], $year, -$daysToRestore, 'used_leave');
            // 로그 기록
            $this->leaveRepository->createLeaveLog($request['employee_id'], 'cancel_use', $daysToRestore, "연차 사용 취소 승인", $adminId, $requestId);
            // 신청 상태 변경
            $this->leaveRepository->updateRequestStatus($requestId, 'cancelled', ['approver_id' => $adminId]);

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
        // 취소 요청을 반려하면 다시 'approved' 상태로 되돌림
        return $this->leaveRepository->updateRequestStatus($requestId, 'approved', ['approver_id' => $adminId]);
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
                    // 미사용 연차만큼 used_leave를 증가시켜 잔여 연차를 0으로 만듦
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
            return false; // 해당 연도의 연차 정보가 없으면 신청 불가
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
