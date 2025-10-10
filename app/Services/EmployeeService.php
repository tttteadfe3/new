<?php

namespace App\Services;

use App\Repositories\EmployeeRepository;
use App\Repositories\EmployeeChangeLogRepository;
use App\Repositories\DepartmentRepository;
use App\Repositories\PositionRepository;
use App\Repositories\LogRepository;
use App\Core\SessionManager;
use App\Models\Employee;

class EmployeeService
{
    private EmployeeRepository $employeeRepository;

    public function __construct()
    {
        $this->employeeRepository = new EmployeeRepository();
    }

    /**
     * Get employees not linked to a user account
     */
    public function getUnlinkedEmployees(): array
    {
        return EmployeeRepository::findUnlinked();
    }

    /**
     * Get all employees with optional filters
     */
    public function getAllEmployees(array $filters = []): array
    {
        return EmployeeRepository::getAll($filters);
    }

    /**
     * Get a single employee by ID
     */
    public function getEmployee(int $id): ?array
    {
        return EmployeeRepository::findById($id);
    }

    /**
     * Get all active employees
     */
    public function getActiveEmployees(): array
    {
        return EmployeeRepository::findAllActive();
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

        return EmployeeRepository::save($data);
    }

    /**
     * Update an existing employee
     */
    public function updateEmployee(int $id, array $data): ?string
    {
        $oldData = EmployeeRepository::findById($id);
        if (!$oldData) {
            throw new \InvalidArgumentException('Employee not found');
        }

        // Validate data using Employee model
        $employee = Employee::make($data);
        if (!$employee->validate()) {
            throw new \InvalidArgumentException('Invalid employee data');
        }

        $data['id'] = $id;
        $savedId = EmployeeRepository::save($data);

        // Log changes if update was successful
        if ($savedId && $oldData) {
            $adminUser = SessionManager::get('user');
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
        $employee = EmployeeRepository::findById($id);
        if (!$employee) {
            throw new \InvalidArgumentException('Employee not found');
        }

        return EmployeeRepository::delete($id);
    }

    /**
     * Approve profile update request
     */
    public function approveProfileUpdate(int $employeeId): bool
    {
        // Get current data before update
        $oldData = EmployeeRepository::findById($employeeId);
        if (!$oldData || $oldData['profile_update_status'] !== 'pending' || empty($oldData['pending_profile_data'])) {
            return false;
        }
        
        // Get requested changes
        $newDataFromRequest = json_decode($oldData['pending_profile_data'], true);
        
        // Merge current data with requested changes
        $fullNewData = array_merge($oldData, $newDataFromRequest);

        // Apply the update
        $success = EmployeeRepository::applyProfileUpdate($employeeId, $fullNewData);
        
        // Log changes if successful
        if ($success) {
            $adminUser = SessionManager::get('user');
            if ($adminUser) {
                $this->logChanges($employeeId, $oldData, $newDataFromRequest, $adminUser['id']);
                
                LogRepository::insert([
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
        return EmployeeRepository::rejectProfileUpdate($employeeId, $reason);
    }

    /**
     * Request profile update (for users)
     */
    public function requestProfileUpdate(int $userId, array $data): bool
    {
        return EmployeeRepository::requestProfileUpdate($userId, $data);
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
                    $department = DepartmentRepository::findById($newValue);
                    $newValue = $department['name'] ?? $newValue;
                } elseif ($key === 'position_id') {
                    $oldValue = $oldData['position_name'] ?? $oldValue;
                    $position = PositionRepository::findById($newValue);
                    $newValue = $position['name'] ?? $newValue;
                }

                EmployeeChangeLogRepository::insert(
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