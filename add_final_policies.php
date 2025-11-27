<?php
require_once __DIR__ . '/vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();
require_once __DIR__ . '/config/config.php';

use App\Core\Database;

$db = new Database();

echo "=== 테이블 스키마 업데이트 ===\n";

// ALTER TABLE to add 'global' to enum
try {
    $db->execute("ALTER TABLE permission_policies MODIFY scope_type ENUM('own','department','managed_departments','parent_department_tree','global','custom') NOT NULL COMMENT '스코프 타입'");
    echo "✓ scope_type enum에 'global' 추가\n\n";
} catch (Exception $e) {
    echo "⚠️  " . $e->getMessage() . "\n\n";
}

echo "=== 추가 정책 삽입 ===\n";

// Insert policies directly
$policiesToAdd = [
    // Department policies
    ['본인 부서 조회', '소속 부서 정보만 조회 가능', 'department', 'view', 'department', 10],
    ['관리 부서 조회', '관리하는 부서 정보 조회 가능', 'department', 'view', 'managed_departments', 20],
    // User policies
    ['본인 계정 조회', '자신의 사용자 계정만 조회 가능', 'user', 'view', 'own', 10],
    ['관리 부서 사용자 조회', '관리하는 부서의 사용자 계정 조회 가능', 'user', 'view', 'managed_departments', 20],
    // Holiday policy
    ['휴일 조회', '모든 휴일 정보 조회 가능', 'holiday', 'view', 'global', 10],
];

foreach ($policiesToAdd as $policy) {
    list($name, $desc, $resource, $action, $scope, $priority) = $policy;
    
    try {
        $db->execute("
            INSERT INTO permission_policies (name, description, resource_type_id, action_id, scope_type, priority)
            VALUES (?, ?, 
                (SELECT id FROM permission_resource_types WHERE name = ?),
                (SELECT id FROM permission_actions WHERE name = ?),
                ?, ?)
        ", [$name, $desc, $resource, $action, $scope, $priority]);
        echo "✓ {$name}\n";
    } catch (Exception $e) {
        echo "✗ {$name}: " . $e->getMessage() . "\n";
    }
}

echo "\n=== 역할-정책 매핑 추가 ===\n";

// Get policy IDs
$holidayPolicy = $db->fetchOne("SELECT id FROM permission_policies WHERE name = '휴일 조회'");
$deptViewOwn = $db->fetchOne("SELECT id FROM permission_policies WHERE name = '본인 부서 조회'");
$deptViewManaged = $db->fetchOne("SELECT id FROM permission_policies WHERE name = '관리 부서 조회'");
$userViewOwn = $db->fetchOne("SELECT id FROM permission_policies WHERE name = '본인 계정 조회'");
$userViewManaged = $db->fetchOne("SELECT id FROM permission_policies WHERE name = '관리 부서 사용자 조회'");

// All roles get holiday and own department/user view
$allRoles = $db->query("SELECT id FROM sys_roles");
foreach ($allRoles as $role) {
    if ($holidayPolicy) {
        $db->execute("INSERT IGNORE INTO role_policies (role_id, policy_id) VALUES (?, ?)", 
            [$role['id'], $holidayPolicy['id']]);
    }
    if ($deptViewOwn) {
        $db->execute("INSERT IGNORE INTO role_policies (role_id, policy_id) VALUES (?, ?)", 
            [$role['id'], $deptViewOwn['id']]);
    }
    if ($userViewOwn) {
        $db->execute("INSERT IGNORE INTO role_policies (role_id, policy_id) VALUES (?, ?)", 
            [$role['id'], $userViewOwn['id']]);
    }
}
echo "✓ 모든 역할에 기본 정책 추가\n";

// Team leaders and managers get managed dept/user view
$managers = $db->query("SELECT id FROM sys_roles WHERE name LIKE '%팀장%' OR name LIKE '%현장대리인%'");
foreach ($managers as $role) {
    if ($deptViewManaged) {
        $db->execute("INSERT IGNORE INTO role_policies (role_id, policy_id) VALUES (?, ?)", 
            [$role['id'], $deptViewManaged['id']]);
    }
    if ($userViewManaged) {
        $db->execute("INSERT IGNORE INTO role_policies (role_id, policy_id) VALUES (?, ?)", 
            [$role['id'], $userViewManaged['id']]);
    }
}
echo "✓ 팀장/현장대리인에 관리 정책 추가\n";

echo "\n=== 결과 ===\n";
$policyCount = $db->fetchOne("SELECT COUNT(*) as cnt FROM permission_policies");
$mappingCount = $db->fetchOne("SELECT COUNT(*) as cnt FROM role_policies");
echo "총 정책: {$policyCount['cnt']}개\n";
echo "총 매핑: {$mappingCount['cnt']}개\n";

echo "\n완료!\n";
