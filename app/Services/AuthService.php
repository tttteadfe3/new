<?php

namespace App\Services;

use App\Core\SessionManager;
use App\Repositories\UserRepository;
use App\Repositories\RoleRepository;
use App\Repositories\LogRepository;
use App\Repositories\DepartmentRepository;
use App\Repositories\EmployeeRepository;
use Exception;

class AuthService {
    private SessionManager $sessionManager;
    private UserRepository $userRepository;
    private RoleRepository $roleRepository;
    private LogRepository $logRepository;
    private DepartmentRepository $departmentRepository;
    private EmployeeRepository $employeeRepository;
    private ?array $departmentMap = null;

    public function __construct(
        SessionManager $sessionManager,
        UserRepository $userRepository,
        RoleRepository $roleRepository,
        LogRepository $logRepository,
        DepartmentRepository $departmentRepository,
        EmployeeRepository $employeeRepository
    ) {
        $this->sessionManager = $sessionManager;
        $this->userRepository = $userRepository;
        $this->roleRepository = $roleRepository;
        $this->logRepository = $logRepository;
        $this->departmentRepository = $departmentRepository;
        $this->employeeRepository = $employeeRepository;
    }

    public function user(): ?array
    {
        return $this->sessionManager->get('user');
    }

    public function isLoggedIn(): bool
    {
        return $this->sessionManager->has('user');
    }

    public function login(array $user) {
        // ... (existing login logic)
    }

    public function logout() {
        // ... (existing logout logic)
    }

    public function check(string $permission_key): bool {
        // ... (existing check logic)
    }

    /**
     * Checks if the current user has permission to manage a target employee.
     * Permission is granted if:
     * 1. They have a global 'employee.manage_all' permission.
     * 2. They are the manager of the target employee's department.
     * 3. They are the manager of any parent department of the target employee's department.
     *
     * @param int $targetEmployeeId The ID of the employee to be managed.
     * @return bool True if the user has management permission, false otherwise.
     */
    public function canManageEmployee(int $targetEmployeeId): bool
    {
        if (!$this->isLoggedIn()) {
            return false;
        }

        // Global admin/manager permission overrides hierarchical checks.
        if ($this->check('employee.manage_all')) {
            return true;
        }

        $currentUser = $this->user();
        $managerEmployeeId = $currentUser['employee_id'] ?? null;

        if (!$managerEmployeeId) {
            return false; // Current user is not linked to an employee.
        }

        if ($managerEmployeeId === $targetEmployeeId) {
            return true; // Users can always manage themselves.
        }

        $targetEmployee = $this->employeeRepository->findById($targetEmployeeId);
        if (!$targetEmployee || !$targetEmployee['department_id']) {
            return false; // Target employee not found or not in a department.
        }

        $this->loadDepartmentMap();

        $targetDeptId = $targetEmployee['department_id'];
        $currentDeptId = $targetDeptId;

        // Traverse up the department hierarchy from the target employee's department.
        while ($currentDeptId) {
            $department = $this->departmentMap[$currentDeptId] ?? null;
            if (!$department) {
                break; // Should not happen in consistent data.
            }

            if ($department->manager_id === $managerEmployeeId) {
                return true; // Found a manager in the hierarchy.
            }

            $currentDeptId = $department->parent_id;
        }

        return false;
    }

    /**
     * Loads all departments into a map for efficient hierarchy traversal.
     */
    private function loadDepartmentMap(): void
    {
        if ($this->departmentMap === null) {
            $allDepartments = $this->departmentRepository->getAll();
            $this->departmentMap = [];
            foreach ($allDepartments as $dept) {
                $this->departmentMap[$dept->id] = $dept;
            }
        }
    }

    // ... (rest of the existing methods like checkAccess, checkStatus, _refreshSessionPermissions)
    private function checkAccess() {
        // ...
    }
    private function checkStatus(): string {
       // ...
    }
    private function _refreshSessionPermissions(array $user): void {
        // ...
    }
}
