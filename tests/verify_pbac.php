<?php
// tests/verify_pbac.php
require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Database;
use App\Core\SessionManager;
use App\Services\PolicyEngine;
use App\Services\DepartmentHierarchyService;
use App\Repositories\LeaveRepository;
use App\Repositories\EmployeeRepository;
use App\Repositories\VehicleRepository;
use App\Repositories\UserRepository;
use App\Repositories\DepartmentRepository;


// Mock SessionManager
class MockSessionManager extends SessionManager {
    private $data = [];
    public function get($key, $default = null) { return $this->data[$key] ?? $default; }
    public function set($key, $value) { $this->data[$key] = $value; }
    public static function getInstance() { return new self(); }
}

try {
    // 1. Setup Dependencies
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
    $dotenv->load();

    require_once __DIR__ . '/../config/config.php'; // Defines DB constants
    $db = new Database();
    $sessionManager = new MockSessionManager();
    $deptService = new DepartmentHierarchyService($db);
    $policyEngine = new PolicyEngine($db, $sessionManager, $deptService);
    
    // Repositories
    $leaveRepo = new LeaveRepository($db, $policyEngine, $sessionManager);
    $employeeRepo = new EmployeeRepository($db, $policyEngine, $sessionManager);
    $vehicleRepo = new VehicleRepository($db, $policyEngine, $sessionManager);
    $userRepo = new UserRepository($db, $policyEngine, $sessionManager);
    $deptRepo = new DepartmentRepository($db, $policyEngine, $sessionManager);

    echo "--- PBAC Verification Start ---\n";

    // 2. Test Case: Admin User (Global Access)
    echo "\n[Test Case 1] Admin User (Global Access)\n";
    
    // Fetch a real admin user (ID 1)
    $adminUser = $db->fetchOne("SELECT * FROM sys_users WHERE id = 1");
    if ($adminUser) {
        $adminUser['permissions'] = ['employee.manage', 'leave.manage', 'vehicle.manage']; 
        $sessionManager->set('user', $adminUser);
        
        // Test 1: Employee List (Should see all)
        $employees = $employeeRepo->getAll();
        echo "Employees count (Admin): " . count($employees) . " (Expected: > 0)\n";

        // Test 2: Vehicle List (Should see all)
        $vehicles = $vehicleRepo->findAll();
        echo "Vehicles count (Admin): " . count($vehicles) . " (Expected: > 0)\n";

        // Test 3: Leave Applications of another employee
        // Find an employee who is NOT the admin
        $targetEmployee = $db->fetchOne("SELECT * FROM hr_employees WHERE id != ?", [$adminUser['employee_id']]);
        if ($targetEmployee) {
            $leaves = $leaveRepo->getEmployeeApplications($targetEmployee['id']);
            echo "Leaves count for {$targetEmployee['name']} (Admin): " . count($leaves) . " (Expected: >= 0, but no error)\n";
        }
    } else {
        echo "Skipping Admin test (User ID 1 not found)\n";
    }

    // 3. Test Case: Staff User (Limited Access)
    echo "\n[Test Case 2] Staff User (Limited Access)\n";
    // Find a non-admin user (e.g., ID 2)
    $staffUser = $db->fetchOne("SELECT * FROM sys_users WHERE id = 2"); 
    if ($staffUser) {
        $staffUser['permissions'] = []; 
        $employee = $db->fetchOne("SELECT * FROM hr_employees WHERE id = ?", [$staffUser['employee_id']]);
        $staffUser['employee'] = $employee;
        $sessionManager->set('user', $staffUser);

        // Test 1: Employee List (Should see limited or none depending on policy)
        $employees = $employeeRepo->getAll();
        echo "Employees count (Staff): " . count($employees) . "\n";
        
        // Test 2: Leave Applications of ANOTHER employee
        if (isset($targetEmployee) && $targetEmployee['id'] != $staffUser['employee_id']) {
             $leaves = $leaveRepo->getEmployeeApplications($targetEmployee['id']);
             echo "Leaves count for {$targetEmployee['name']} (Staff): " . count($leaves) . " (Expected: 0 if strict policy)\n";
        }
    } else {
        echo "Skipping Staff test (User ID 2 not found)\n";
    }

    echo "\n--- PBAC Verification Complete ---\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
