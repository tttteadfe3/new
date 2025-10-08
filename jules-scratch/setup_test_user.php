<?php

// This script sets up a test user with admin permissions for automated testing.
// It connects directly to the DB without bootstrapping the entire web application.

require_once __DIR__ . '/../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();
foreach ($_ENV as $key => $value) {
    putenv("$key=$value");
}

// Define only necessary constants for DB connection
define('ROOT_PATH', dirname(__DIR__));
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'erp');
define('DB_USER', getenv('DB_USER') ?: 'erp');
define('DB_PASS', getenv('DB_PASS') ?: 'Dnjstlf!23');

// Manually include the DB class
require_once ROOT_PATH . '/app/Core/DB.php';

try {
    $pdo = App\Core\DB::getInstance();
    $pdo->beginTransaction();

    echo "Setting up test user...\n";

    // 1. Create 'holiday_admin' permission if it doesn't exist
    $pdo->exec("INSERT IGNORE INTO sys_permissions (`key`, `description`) VALUES ('holiday_admin', 'Holiday management permission')");
    $stmt = $pdo->query("SELECT id FROM sys_permissions WHERE `key` = 'holiday_admin'");
    $permissionId = $stmt->fetchColumn();
    echo "Permission 'holiday_admin' (ID: $permissionId) is set up.\n";

    // 2. Create 'TestAdmin' role if it doesn't exist
    $pdo->exec("INSERT IGNORE INTO sys_roles (`name`, `description`) VALUES ('TestAdmin', 'Role for automated testing')");
    $stmt = $pdo->query("SELECT id FROM sys_roles WHERE `name` = 'TestAdmin'");
    $roleId = $stmt->fetchColumn();
    echo "Role 'TestAdmin' (ID: $roleId) is set up.\n";

    // 3. Link permission to role
    $stmt = $pdo->prepare("INSERT IGNORE INTO sys_role_permissions (role_id, permission_id) VALUES (?, ?)");
    $stmt->execute([$roleId, $permissionId]);
    echo "Linked permission to role.\n";

    // 4. Create a test employee if not exists
    $employeeNumber = 'TEST-001';
    $stmt = $pdo->prepare("SELECT id FROM hr_employees WHERE employee_number = ?");
    $stmt->execute([$employeeNumber]);
    $employeeId = $stmt->fetchColumn();

    if (!$employeeId) {
        $stmt = $pdo->prepare("INSERT INTO hr_employees (name, employee_number, hire_date) VALUES (?, ?, ?)");
        $stmt->execute(['Test Employee', $employeeNumber, '2024-01-01']);
        $employeeId = $pdo->lastInsertId();
        echo "Created test employee (ID: $employeeId).\n";
    } else {
        echo "Test employee (ID: $employeeId) already exists.\n";
    }

    // 5. Create a test user if not exists
    $kakaoId = 'test-user-12345';
    $stmt = $pdo->prepare("SELECT id FROM sys_users WHERE kakao_id = ?");
    $stmt->execute([$kakaoId]);
    $userId = $stmt->fetchColumn();

    if (!$userId) {
        $stmt = $pdo->prepare(
            "INSERT INTO sys_users (kakao_id, email, nickname, status, employee_id)
             VALUES (?, ?, ?, 'active', ?)"
        );
        $stmt->execute([$kakaoId, 'testuser@example.com', 'TestUser', $employeeId]);
        $userId = $pdo->lastInsertId();
        echo "Created test user (ID: $userId).\n";
    } else {
        $stmt = $pdo->prepare("UPDATE sys_users SET employee_id = ?, status = 'active' WHERE id = ?");
        $stmt->execute([$employeeId, $userId]);
        echo "Test user (ID: $userId) already exists, updated link.\n";
    }

    // 6. Assign role to user
    $stmt = $pdo->prepare("INSERT IGNORE INTO sys_user_roles (user_id, role_id) VALUES (?, ?)");
    $stmt->execute([$userId, $roleId]);
    echo "Assigned role to user.\n";

    $pdo->commit();
    echo "Test user setup complete!\n";

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "An error occurred: " . $e->getMessage() . "\n";
    exit(1);
}