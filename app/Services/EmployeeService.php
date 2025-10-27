<?php

namespace App\Services;

use App\Core\SessionManager;
use App\Repositories\EmployeeRepository;
use App\Repositories\EmployeeChangeLogRepository;
use App\Repositories\DepartmentRepository;
use App\Repositories\PositionRepository;
use App\Repositories\LogRepository;
use App\Models\Employee;
use App\Services\OrganizationService;

class EmployeeService
{
    private EmployeeRepository $employeeRepository;
    private EmployeeChangeLogRepository $employeeChangeLogRepository;
    private DepartmentRepository $departmentRepository;
    private PositionRepository $positionRepository;
    private LogRepository $logRepository;
    private SessionManager $sessionManager;
    private OrganizationService $organizationService;

    public function __construct(
        EmployeeRepository $employeeRepository,
        EmployeeChangeLogRepository $employeeChangeLogRepository,
        DepartmentRepository $departmentRepository,
        PositionRepository $positionRepository,
        LogRepository $logRepository,
        SessionManager $sessionManager,
        OrganizationService $organizationService
    ) {
        $this->employeeRepository = $employeeRepository;
        $this->employeeChangeLogRepository = $employeeChangeLogRepository;
        $this->departmentRepository = $departmentRepository;
        $this->positionRepository = $positionRepository;
        $this->logRepository = $logRepository;
        $this->sessionManager = $sessionManager;
        $this->organizationService = $organizationService;
    }

    /**
     * 사용자 계정에 연결되지 않은 직원을 가져옵니다.
     * @return array
     */
    public function getUnlinkedEmployees(?int $departmentId = null): array
    {
        return $this->employeeRepository->findUnlinked($departmentId);
    }

    /**
     * 중앙 집중식 부서 가시성 로직을 적용하여 선택적 필터가 있는 모든 직원을 가져옵니다.
     * @param array $filters
     * @return array
     */
    public function getAllEmployees(array $filters = []): array
    {
        $visibleDeptIds = $this->organizationService->getVisibleDepartmentIdsForCurrentUser();
        return $this->employeeRepository->getAll($filters, $visibleDeptIds);
    }

    /**
     * ID로 단일 직원을 가져옵니다.
     * @param int $id
     * @return array|null
     */
    public function getEmployee(int $id): ?array
    {
        return $this->employeeRepository->findById($id);
    }

    /**
     * 모든 활성 직원을 가져옵니다.
     * @return array
     */
    public function getActiveEmployees(): array
    {
        return $this->employeeRepository->findAllActive();
    }

    /**
     * 새 직원을 만듭니다.
     * @param array $data
     * @return string|null
     * @throws \InvalidArgumentException
     */
    public function createEmployee(array $data): ?string
    {
        // Employee 모델을 사용하여 데이터 유효성 검사
        $employee = Employee::make($data);
        if (!$employee->validate()) {
            throw new \InvalidArgumentException('잘못된 직원 데이터');
        }

        return $this->employeeRepository->save($data);
    }

    /**
     * 기존 직원을 업데이트합니다.
     * @param int $id
     * @param array $data
     * @return string|null
     * @throws \InvalidArgumentException
     */
    public function updateEmployee(int $id, array $data): ?string
    {
        $oldData = $this->employeeRepository->findById($id);
        if (!$oldData) {
            throw new \InvalidArgumentException('직원을 찾을 수 없습니다');
        }

        // Employee 모델을 사용하여 데이터 유효성 검사
        $employee = Employee::make($data);
        if (!$employee->validate()) {
            throw new \InvalidArgumentException('잘못된 직원 데이터');
        }

        $data['id'] = $id;
        $savedId = $this->employeeRepository->save($data);

        // 업데이트가 성공하면 변경 사항 기록
        if ($savedId && $oldData) {
            $adminUser = $this->sessionManager->get('user');
            if ($adminUser) {
                $this->logChanges($id, $oldData, $data, $adminUser['id']);
            }
        }

        return $savedId;
    }

    /**
     * 직원을 삭제합니다.
     * @param int $id
     * @return bool
     * @throws \InvalidArgumentException
     */
    public function deleteEmployee(int $id): bool
    {
        $employee = $this->employeeRepository->findById($id);
        if (!$employee) {
            throw new \InvalidArgumentException('직원을 찾을 수 없습니다');
        }

        return $this->employeeRepository->delete($id);
    }

    /**
     * 프로필 업데이트 요청을 승인합니다.
     * @param int $employeeId
     * @return bool
     */
    public function approveProfileUpdate(int $employeeId): bool
    {
        // 업데이트 전 현재 데이터 가져오기
        $oldData = $this->employeeRepository->findById($employeeId);
        if (!$oldData || $oldData['profile_update_status'] !== '대기' || empty($oldData['pending_profile_data'])) {
            return false;
        }
        
        // 요청된 변경 사항 가져오기
        $newDataFromRequest = json_decode($oldData['pending_profile_data'], true);
        
        // 현재 데이터를 요청된 변경 사항과 병합
        $fullNewData = array_merge($oldData, $newDataFromRequest);

        // 업데이트 적용
        $success = $this->employeeRepository->applyProfileUpdate($employeeId, $fullNewData);
        
        // 성공 시 변경 사항 기록
        if ($success) {
            $adminUser = $this->sessionManager->get('user');
            if ($adminUser) {
                $this->logChanges($employeeId, $oldData, $newDataFromRequest, $adminUser['id']);
                
                $this->logRepository->insert([
                    ':user_id' => $adminUser['id'],
                    ':user_name' => $adminUser['nickname'],
                    ':action' => '프로필 변경 승인',
                    ':details' => "직원 '{$oldData['name']}'(id:{$employeeId})의 프로필 변경 요청을 승인했습니다.",
                    ':ip_address' => $_SERVER['REMOTE_ADDR']
                ]);
            }
        }
        
        return $success;
    }

    /**
     * 프로필 업데이트 요청을 거부합니다.
     * @param int $employeeId
     * @param string $reason
     * @return bool
     */
    public function rejectProfileUpdate(int $employeeId, string $reason): bool
    {
        return $this->employeeRepository->rejectProfileUpdate($employeeId, $reason);
    }

    /**
     * 프로필 업데이트를 요청합니다 (사용자용).
     * @param int $userId
     * @param array $data
     * @return bool
     */
    public function requestProfileUpdate(int $userId, array $data): bool
    {
        return $this->employeeRepository->requestProfileUpdate($userId, $data);
    }

    /**
     * 직원 변경 이력을 가져옵니다.
     * @param int $employeeId
     * @return array
     */
    public function getEmployeeChangeHistory(int $employeeId): array
    {
        return $this->employeeChangeLogRepository->findByEmployeeId($employeeId);
    }

    /**
     * 이전 직원 데이터와 새 직원 데이터 간의 변경 사항을 기록합니다.
     * @param int $employeeId
     * @param array $oldData
     * @param array $newData
     * @param int $changerId
     * @return void
     */
    private function logChanges(int $employeeId, array $oldData, array $newData, int $changerId): void
    {
        $fields = [
            'name' => '이름', 
            'employee_number' => '사번', 
            'hire_date' => '입사일',
            'phone_number' => '연락처', 
            'address' => '주소',
            'emergency_contact_name' => '비상연락처', 
            'emergency_contact_relation' => '관계',
            'clothing_top_size' => '상의', 
            'clothing_bottom_size' => '하의', 
            'shoe_size' => '신발',
            'department_id' => '부서', 
            'position_id' => '직급'
        ];

        foreach ($fields as $key => $label) {
            $oldValue = $oldData[$key] ?? null;
            $newValue = $newData[$key] ?? null;
            
            if (isset($newData[$key]) && (string)$oldValue !== (string)$newValue) {
                // 로깅을 위해 부서 및 직책 ID를 이름으로 변환
                if ($key === 'department_id') {
                    $oldValue = $oldData['department_name'] ?? $oldValue;
                    $department = $this->departmentRepository->findById($newValue);
                    $newValue = $department ? $department->name : $newValue;
                } elseif ($key === 'position_id') {
                    $oldValue = $oldData['position_name'] ?? $oldValue;
                    $position = $this->positionRepository->findById($newValue);
                    $newValue = $position ? $position->name : $newValue;
                }

                $this->employeeChangeLogRepository->insert(
                    $employeeId, 
                    $changerId, 
                    $label, 
                    $oldValue, 
                    $newValue
                );
            }
        }
    }
}
