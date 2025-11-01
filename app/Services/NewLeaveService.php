<?php

namespace App\Services;

use App\Repositories\LeaveRepository;
use App\Repositories\EmployeeRepository;
use App\Services\DataScopeService;
use DateTime;

class NewLeaveService
{
    private $leaveRepository;
    private $employeeRepository;
    private $dataScopeService;

    public function __construct(LeaveRepository $leaveRepository, EmployeeRepository $employeeRepository, DataScopeService $dataScopeService)
    {
        $this->leaveRepository = $leaveRepository;
        $this->employeeRepository = $employeeRepository;
        $this->dataScopeService = $dataScopeService;
    }

    public function getLeaveBalance(int $employeeId): array
    {
        $logs = $this->leaveRepository->getLogsByEmployeeId($employeeId);
        $balance = ['annual' => 0.0, 'monthly' => 0.0];
        foreach ($logs as $log) {
            $balance[$log['leave_type']] += $log['amount'];
        }
        return $balance;
    }

    public function grantInitialMonthlyLeave(int $employeeId): bool
    {
        $employee = $this->employeeRepository->findById($employeeId);
        if (!$employee || !$employee['hire_date']) return false;
        $hireDate = new DateTime($employee['hire_date']);
        $monthlyLeaveAmount = 12 - (int)$hireDate->format('m');
        if ($monthlyLeaveAmount > 0) {
            $this->leaveRepository->createLog(['employee_id' => $employeeId, 'leave_type' => 'monthly', 'transaction_type' => 'grant_initial', 'amount' => $monthlyLeaveAmount, 'reason' => $hireDate->format('Y') . '년 신규 입사자 월차 부여']);
        }
        return true;
    }

    public function getEntitlements(int $year, ?int $departmentId): array
    {
        $allEmployees = $this->employeeRepository->findAllActive();

        $employees = array_filter($allEmployees, function($employee) use ($departmentId) {
            if ($departmentId) {
                return isset($employee['department_id']) && $employee['department_id'] == $departmentId;
            }
            return true;
        });

        $results = [];
        foreach ($employees as $employee) {
            $logs = $this->leaveRepository->getLogsByEmployeeId($employee['id']);

            $grantLog = null;
            $adjustments = 0.0;
            $used = 0.0;

            foreach($logs as $log) {
                if ($log['transaction_type'] === 'grant_annual' && str_starts_with($log['reason'], (string)$year)) {
                    $grantLog = $log;
                }
                if (str_starts_with($log['transaction_type'], 'adjust')) {
                    $adjustments += (float)$log['amount'];
                }
                if ($log['transaction_type'] === 'use') {
                    $used += abs((float)$log['amount']);
                }
            }

            $grantedDays = $grantLog ? (float)$grantLog['amount'] : 0.0;
            $totalDays = $grantedDays + $adjustments;

            $breakdown = $grantLog ? $this->calculateAnnualLeaveForEmployee($employee, $year) : null;

            $results[] = [
                'employee_id' => $employee['id'],
                'employee_name' => $employee['name'],
                'department_name' => $employee['department_name'] ?? 'N/A',
                'hire_date' => $employee['hire_date'],
                'entitlement_id' => $grantLog ? $grantLog['id'] : null,
                'total_days' => $totalDays,
                'used_days' => $used,
                'adjusted_days' => $adjustments,
                'leave_breakdown' => $breakdown
            ];
        }
        return $results;
    }

    public function calculateAnnualLeaveForAll(int $year): array
    {
        $employees = $this->employeeRepository->findAllActive();
        $results = [];
        foreach ($employees as $employee) {
            $leaveData = $this->calculateAnnualLeaveForEmployee($employee, $year);
            if ($leaveData['total_days'] > 0) {
                 $results[] = [
                    'id' => $employee['id'],
                    'name' => $employee['name'],
                    'leave_data' => $leaveData
                ];
            }
        }
        return $results;
    }

    public function grantAnnualLeaveForYear(int $year, array $employees, int $actorEmployeeId): void
    {
        $this->leaveRepository->beginTransaction();
        try {
            foreach ($employees as $empData) {
                $employee = $this->employeeRepository->findById($empData['id']);
                if (!$employee) continue;

                $grantData = $this->calculateAnnualLeaveForEmployee($employee, $year);
                $grantAmount = $grantData['total_days'];

                if ($grantAmount > 0) {
                    $reason = $year . '년 정기 연차 부여';

                    $logs = $this->leaveRepository->getLogsByEmployeeId($employee['id']);
                    $alreadyGranted = false;
                    foreach ($logs as $log) {
                        if ($log['transaction_type'] === 'grant_annual' && $log['reason'] === $reason) {
                            $alreadyGranted = true;
                            break;
                        }
                    }

                    if (!$alreadyGranted) {
                        $this->leaveRepository->createLog([
                            'employee_id' => $employee['id'],
                            'leave_type' => 'annual',
                            'transaction_type' => 'grant_annual',
                            'amount' => $grantAmount,
                            'reason' => $reason,
                            'actor_employee_id' => $actorEmployeeId
                        ]);
                    }
                }
            }
            $this->leaveRepository->commit();
        } catch (\Exception $e) {
            $this->leaveRepository->rollBack();
            throw $e;
        }
    }

    public function createLeaveRequest(int $employeeId, array $data): array
    {
        $balance = $this->getLeaveBalance($employeeId);
        $leaveType = $data['leave_type'];
        $daysCount = (float)$data['days_count'];
        if ($balance[$leaveType] < $daysCount) {
            return ['success' => false, 'message' => 'Not enough leave balance.'];
        }
        $this->leaveRepository->createRequest(array_merge($data, ['employee_id' => $employeeId, 'status' => 'pending']));
        return ['success' => true, 'message' => 'Leave request submitted successfully.'];
    }

    public function approveRequest(int $requestId, int $actorEmployeeId): array
    {
        $this->leaveRepository->beginTransaction();
        try {
            $request = $this->leaveRepository->findRequestById($requestId);
            if (!$request || $request['status'] !== 'pending') {
                $this->leaveRepository->rollBack();
                return ['success' => false, 'message' => 'Request is not pending approval.'];
            }
            $this->leaveRepository->createLog(['employee_id' => $request['employee_id'], 'leave_request_id' => $requestId, 'leave_type' => $request['leave_type'], 'transaction_type' => 'use', 'amount' => -$request['days_count'], 'reason' => 'Leave used', 'actor_employee_id' => $actorEmployeeId]);
            $this->leaveRepository->updateRequestStatus($requestId, 'approved', $actorEmployeeId);
            $this->leaveRepository->commit();
            return ['success' => true, 'message' => 'Leave request approved.'];
        } catch (\Exception $e) {
            $this->leaveRepository->rollBack();
            return ['success' => false, 'message' => 'An error occurred.'];
        }
    }

    public function rejectRequest(int $requestId, int $actorEmployeeId, string $reason): array
    {
        $request = $this->leaveRepository->findRequestById($requestId);
        if (!$request || $request['status'] !== 'pending') {
            return ['success' => false, 'message' => 'Request is not pending approval.'];
        }
        if (empty($reason)) return ['success' => false, 'message' => 'Rejection reason is required.'];
        $this->leaveRepository->updateRequestStatusAndReason($requestId, 'rejected', $actorEmployeeId, $reason);
        return ['success' => true, 'message' => 'Leave request rejected.'];
    }

    public function requestCancellation(int $requestId, int $employeeId): array
    {
        $request = $this->leaveRepository->findRequestById($requestId);
        if (!$request || $request['employee_id'] != $employeeId) return ['success' => false, 'message' => 'Permission denied.'];
        if ($request['status'] !== 'approved') return ['success' => false, 'message' => 'Only approved leave can be cancelled.'];
        $this->leaveRepository->updateRequestStatus($requestId, 'cancellation_requested');
        return ['success' => true, 'message' => 'Cancellation requested.'];
    }

    public function approveCancellation(int $requestId, int $actorEmployeeId): array
    {
        $this->leaveRepository->beginTransaction();
        try {
            $request = $this->leaveRepository->findRequestById($requestId);
            if (!$request || $request['status'] !== 'cancellation_requested') {
                $this->leaveRepository->rollBack();
                return ['success' => false, 'message' => 'Request is not pending cancellation approval.'];
            }
            $this->leaveRepository->createLog(['employee_id' => $request['employee_id'], 'leave_request_id' => $requestId, 'leave_type' => $request['leave_type'], 'transaction_type' => 'cancel_use', 'amount' => $request['days_count'], 'reason' => 'Leave cancellation approved', 'actor_employee_id' => $actorEmployeeId]);
            $this->leaveRepository->updateRequestStatus($requestId, 'cancelled', $actorEmployeeId);
            $this->leaveRepository->commit();
            return ['success' => true, 'message' => 'Leave cancellation approved.'];
        } catch (\Exception $e) {
            $this->leaveRepository->rollBack();
            return ['success' => false, 'message' => 'An error occurred.'];
        }
    }

    public function adjustLeave(array $data, int $actorEmployeeId): array
    {
        $employeeId = $data['employee_id'];
        $leaveType = $data['leave_type'];
        $amount = (float)$data['amount'];
        $reason = $data['reason'];
        if (empty($employeeId) || empty($leaveType) || empty($amount) || empty($reason)) {
            return ['success' => false, 'message' => 'All fields are required.'];
        }
        $transactionType = $amount > 0 ? 'adjust_add' : 'adjust_subtract';
        $this->leaveRepository->createLog(['employee_id' => $employeeId, 'leave_type' => $leaveType, 'transaction_type' => $transactionType, 'amount' => $amount, 'reason' => $reason, 'actor_employee_id' => $actorEmployeeId]);
        return ['success' => true, 'message' => 'Leave adjusted successfully.'];
    }

    public function getRequestsByEmployeeId(int $employeeId): array
    {
        return $this->leaveRepository->findRequestsByFilters(['employee_id' => $employeeId]);
    }

    public function getRequestsForAdmin(array $filters): array
    {
        $visibleDeptIds = $this->dataScopeService->getVisibleDepartmentIdsForCurrentUser();
        $filters['department_ids'] = $visibleDeptIds;
        return $this->leaveRepository->findRequestsByFilters($filters);
    }

    private function calculateAnnualLeaveForEmployee(array $employee, int $year): array
    {
        $hireDate = new DateTime($employee['hire_date']);
        $grantDate = new DateTime($year . "-01-01");
        $baseDays = 0.0;
        $longServiceDays = 0.0;

        if ($hireDate >= $grantDate) {
             return ['base_days' => 0, 'long_service_days' => 0, 'total_days' => 0];
        }

        // 입사 첫 해 (중도입사자)
        if ($hireDate->format('Y') == $year - 1) {
            $monthsWorked = 12 - (int)$hireDate->format('m');
            if ($hireDate->format('d') > 1) {
                $monthsWorked--;
            }
             $baseDays = round((15 / 12) * $monthsWorked, 2);

        } else { // 1년 이상 근무
            $baseDays = 15.0;
            $serviceYears = $this->calculateServiceYears($hireDate, $grantDate);
            if ($serviceYears >= 3) {
                $longServiceDays = floor(($serviceYears - 1) / 2);
            }
        }

        $totalDays = $baseDays + $longServiceDays;
        $totalDays = min($totalDays, 25);

        return [
            'base_days' => $baseDays,
            'long_service_days' => $longServiceDays,
            'total_days' => $totalDays,
        ];
    }

    private function calculateServiceYears(DateTime $hireDate, DateTime $grantDate): int
    {
        if ($hireDate->format('m-d') !== '01-01') {
            $hireDate = new DateTime(((int)$hireDate->format('Y') + 1) . '-01-01');
        }
        return $grantDate->diff($hireDate)->y;
    }

    public function expireLeaveForYear(int $year, int $actorEmployeeId): void
    {
        $activeEmployees = $this->employeeRepository->findAllActive();
        foreach ($activeEmployees as $employee) {
            $balance = $this->getLeaveBalance($employee['id']);

            // Expire annual leave
            if ($balance['annual'] > 0) {
                $this->leaveRepository->createLog([
                    'employee_id' => $employee['id'],
                    'leave_type' => 'annual',
                    'transaction_type' => 'expire',
                    'amount' => -$balance['annual'],
                    'reason' => $year . '년 미사용 연차 소멸',
                    'actor_employee_id' => $actorEmployeeId,
                ]);
            }

            // Expire monthly leave
            if ($balance['monthly'] > 0) {
                $this->leaveRepository->createLog([
                    'employee_id' => $employee['id'],
                    'leave_type' => 'monthly',
                    'transaction_type' => 'expire',
                    'amount' => -$balance['monthly'],
                    'reason' => $year . '년 미사용 월차 소멸',
                    'actor_employee_id' => $actorEmployeeId,
                ]);
            }
        }
    }
}
