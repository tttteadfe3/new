<?php

namespace App\Services;

use App\Core\Database;
use App\Core\SessionManager;
use App\Repositories\EmployeeRepository;
use App\Repositories\EmployeeChangeLogRepository;
use App\Repositories\DepartmentRepository;
use App\Repositories\PositionRepository;
use App\Repositories\LogRepository;
use App\Models\Employee;

class EmployeeService
{
    private EmployeeRepository $employeeRepository;
    private EmployeeChangeLogRepository $employeeChangeLogRepository;
    private DepartmentRepository $departmentRepository;
    private PositionRepository $positionRepository;
    private LogRepository $logRepository;
    private SessionManager $sessionManager;

    public function __construct()
    {
        $db = Database::getInstance();
        $this->employeeRepository = new EmployeeRepository($db);
        $this->employeeChangeLogRepository = new EmployeeChangeLogRepository($db);
        $this->departmentRepository = new DepartmentRepository($db);
        $this->positionRepository = new PositionRepository($db);
        $this->logRepository = new LogRepository($db);
        $this->sessionManager = new SessionManager();
    }

    /**
     * Get employees not linked to a user account
     */
    public function getUnlinkedEmployees(): array
    {
        return $this->employeeRepository->findUnlinked();
    }

    /**
     * Get all employees with optional filters
     */
    public function getAllEmployees(array $filters = []): array
    {
        return $this->employeeRepository->getAll($filters);
    }

    /**
     * Get a single employee by ID
     */
    public function getEmployee(int $id): ?array
    {
        return $this->employeeRepository->findById($id);
    }

    /**
     * Get all active employees
     */
    public function getActiveEmployees(): array
    {
        return $this->employeeRepository->findAllActive();
    }

    /**
     * Create a new employee
     */
    public function createEmployee(array $data): ?string
    {
        // Validate data using Employee model
        $employee = Employee::make($data);
        if (!$employee->validate()) {
            throw new \InvalidArgumentException('Invalid employee data');
        }

        return $this->employeeRepository->save($data);
    }

    /**
     * Update an existing employee
     */
    public function updateEmployee(int $id, array $data): ?string
    {
        $oldData = $this->employeeRepository->findById($id);
        if (!$oldData) {
            throw new \InvalidArgumentException('Employee not found');
        }

        // Validate data using Employee model
        $employee = Employee::make($data);
        if (!$employee->validate()) {
            throw new \InvalidArgumentException('Invalid employee data');
        }

        $data['id'] = $id;
        $savedId = $this->employeeRepository->save($data);

        // Log changes if update was successful
        if ($savedId && $oldData) {
            $adminUser = $this->sessionManager->get('user');
            if ($adminUser) {
                $this->logChanges($id, $oldData, $data, $adminUser['id']);
            }
        }

        return $savedId;
    }

    /**
     * Delete an employee
     */
    public function deleteEmployee(int $id): bool
    {
        $employee = $this->employeeRepository->findById($id);
        if (!$employee) {
            throw new \InvalidArgumentException('Employee not found');
        }

        return $this->employeeRepository->delete($id);
    }

    /**
     * Approve profile update request
     */
    public function approveProfileUpdate(int $employeeId): bool
    {
        // Get current data before update
        $oldData = $this->employeeRepository->findById($employeeId);
        if (!$oldData || $oldData['profile_update_status'] !== 'pending' || empty($oldData['pending_profile_data'])) {
            return false;
        }
        
        // Get requested changes
        $newDataFromRequest = json_decode($oldData['pending_profile_data'], true);
        
        // Merge current data with requested changes
        $fullNewData = array_merge($oldData, $newDataFromRequest);

        // Apply the update
        $success = $this->employeeRepository->applyProfileUpdate($employeeId, $fullNewData);
        
        // Log changes if successful
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
     * Reject profile update request
     */
    public function rejectProfileUpdate(int $employeeId, string $reason): bool
    {
        return $this->employeeRepository->rejectProfileUpdate($employeeId, $reason);
    }

    /**
     * Request profile update (for users)
     */
    public function requestProfileUpdate(int $userId, array $data): bool
    {
        return $this->employeeRepository->requestProfileUpdate($userId, $data);
    }

    /**
     * Log changes between old and new employee data
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
                // Convert department and position IDs to names for logging
                if ($key === 'department_id') {
                    $oldValue = $oldData['department_name'] ?? $oldValue;
                    $department = $this->departmentRepository->findById($newValue);
                    $newValue = $department['name'] ?? $newValue;
                } elseif ($key === 'position_id') {
                    $oldValue = $oldData['position_name'] ?? $oldValue;
                    $position = $this->positionRepository->findById($newValue);
                    $newValue = $position['name'] ?? $newValue;
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
