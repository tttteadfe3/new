<?php
// simulate_grant_targets.php

// Define necessary constants
define('ROOT_PATH', __DIR__);

// Autoload
require_once __DIR__ . '/vendor/autoload.php';

// Mock $_SERVER and $_SESSION
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['REQUEST_URI'] = '/api/leaves_admin/calculate-grant-targets';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';

// Initialize SessionManager
$sessionManager = new \App\Core\SessionManager();
$sessionManager->start();

// Mock User in Session (Assume User ID 1 is an admin)
// We need to set up a user that SHOULD be restricted but might be getting global access.
// Let's assume User 1 has 'leave.manage' but should be restricted by policy.
$user = [
    'id' => 1,
    'nickname' => 'AdminUser',
    'employee_id' => 1,
    'permissions' => ['leave.manage', 'employee.view'], // Has leave.manage
    'roles' => ['admin']
];
$sessionManager->set('user', $user);

// Mock Input Data
// Simulate "All" departments selected (department_id = null)
$inputData = [
    'year' => 2024,
    'department_id' => null,
    'preview_mode' => true
];

// We need to mock php://input for getJsonInput()
// Since we can't easily mock php://input in CLI without a wrapper, 
// we might need to modify Request class or use a different approach.
// Alternatively, we can manually instantiate the controller and call the method.

// Let's instantiate the app components manually to control the environment.

use App\Core\Database;
use App\Core\Request;
use App\Services\AuthService;
use App\Services\ViewDataService;
use App\Services\ActivityLogger;
use App\Repositories\EmployeeRepository;
use App\Core\JsonResponse;
use App\Services\LeaveService;
use App\Services\LeaveAdminService;
use App\Repositories\LeaveRepository;
use App\Repositories\LeaveAdminRepository;
use App\Repositories\DepartmentRepository;
use App\Repositories\PositionRepository;
use App\Repositories\UserRepository;
use App\Repositories\RoleRepository;
use App\Repositories\LogRepository;
use App\Repositories\EmployeeChangeLogRepository;
use App\Services\PolicyEngine;
use App\Services\DepartmentHierarchyService;
use App\Controllers\Api\LeaveAdminApiController;

// Database connection
$config = require __DIR__ . '/config/config.php';
$db = new Database($config);

// Repositories & Services
$deptRepo = new DepartmentRepository($db, new PolicyEngine($db, $sessionManager, new DepartmentHierarchyService($db)), $sessionManager);
$deptHierarchyService = new DepartmentHierarchyService($db);
$policyEngine = new PolicyEngine($db, $sessionManager, $deptHierarchyService);

// Re-instantiate repositories that depend on PolicyEngine
$deptRepo = new DepartmentRepository($db, $policyEngine, $sessionManager);
$employeeRepo = new EmployeeRepository($db, $policyEngine, $sessionManager);

$leaveRepo = new LeaveRepository($db, $policyEngine, $sessionManager);
$leaveAdminRepo = new LeaveAdminRepository($db, $policyEngine, $sessionManager);
$userRepo = new UserRepository($db, $policyEngine, $sessionManager);
$roleRepo = new RoleRepository($db);
$logRepo = new LogRepository($db);
$employeeChangeLogRepo = new EmployeeChangeLogRepository($db);
$positionRepo = new PositionRepository($db);

$holidayRepo = new \App\Repositories\HolidayRepository($db, $policyEngine, $sessionManager);
$holidayService = new \App\Services\HolidayService($holidayRepo, $deptRepo);
$leaveService = new LeaveService($leaveRepo, $employeeRepo, $deptRepo, $holidayService);
$leaveAdminService = new LeaveAdminService($leaveRepo, $leaveAdminRepo, $employeeRepo, $deptRepo, $leaveService);

$authService = new AuthService($sessionManager, $userRepo, $roleRepo, $logRepo, $employeeRepo);
$viewDataService = new ViewDataService($deptRepo, $positionRepo);
$activityLogger = new ActivityLogger($logRepo, $authService);
$jsonResponse = new JsonResponse();

// Mock Request
$request = new class extends Request {
    public function getJsonInput(): array {
        return [
            'year' => 2024,
            'department_id' => null,
            'preview_mode' => true
        ];
    }
    public function input($key, $default = null) {
        return $default;
    }
};

// Controller
$controller = new LeaveAdminApiController(
    $request,
    $authService,
    $viewDataService,
    $activityLogger,
    $employeeRepo,
    $jsonResponse,
    $leaveService,
    $leaveAdminService,
    $leaveRepo
);

// Run
echo "Running calculateGrantTargets...\n";
$controller->calculateGrantTargets();
echo "\nDone.\n";
