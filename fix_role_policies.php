<?php
require_once __DIR__ . '/vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();
require_once __DIR__ . '/config/config.php';

use App\Core\Database;

$db = new Database();

// Clear existing mappings
echo "Clearing existing role-policy mappings...\n";
$db->execute("DELETE FROM role_policies");

// Test 1: Check if roles exist
echo "\n=== Testing role existence ===\n";
$teamLeader = $db->fetchOne("SELECT * FROM sys_roles WHERE name LIKE '%팀장%' LIMIT 1");
echo "Found team leader role: " . ($teamLeader ? $teamLeader['name'] : 'NONE') . "\n";

// Test 2: Check if policies exist
echo "\n=== Testing policy existence ===\n";
$ownPolicy = $db->fetchOne("SELECT * FROM permission_policies WHERE name = '본인 직원정보 조회'");
echo "Found 'own' policy: " . ($ownPolicy ? "ID " . $ownPolicy['id'] : 'NONE') . "\n";

// Test 3: Try manual INSERT
echo "\n=== Manual INSERT test ===\n";
if ($teamLeader && $ownPolicy) {
    try {
        $db->execute("INSERT INTO role_policies (role_id, policy_id) VALUES (?, ?)", [
            $teamLeader['id'], $ownPolicy['id']
        ]);
        echo "✓ Successfully inserted test mapping!\n";
    } catch (Exception $e) {
        echo "✗ Error: " . $e->getMessage() . "\n";
    }
}

// Now insert all mappings properly
echo "\n=== Inserting all role-policy mappings ===\n";

// 1. Admin gets all policies
$admin = $db->fetchOne("SELECT id FROM sys_roles WHERE name = '최고관리자'");
$allPolicies = $db->query("SELECT id FROM permission_policies");
foreach ($allPolicies as $policy) {
    $db->execute("INSERT IGNORE INTO role_policies (role_id, policy_id) VALUES (?, ?)", [
        $admin['id'], $policy['id']
    ]);
}
echo "✓ Admin roles configured\n";

// 2. Team leaders
$teamLeaders = $db->query("SELECT id FROM sys_roles WHERE name LIKE '%팀장%'");
$teamLeaderPolicies = $db->query("
    SELECT id FROM permission_policies 
    WHERE name IN (
        '본인 직원정보 조회', '관리 부서 직원 조회',
        '본인 연차 조회', '같은 과 전체 연차 조회', '관리 부서 연차 조회',
        '본인 부서 차량 조회', '관리 부서 차량 조회',
        '본인 부서 지급품 조회', '관리 부서 지급품 조회'
    )
");
foreach ($teamLeaders as $role) {
    foreach ($teamLeaderPolicies as $policy) {
        $db->execute("INSERT IGNORE INTO role_policies (role_id, policy_id) VALUES (?, ?)", [
            $role['id'], $policy['id']
        ]);
    }
}
echo "✓ Team leader roles configured\n";

// 3. Field managers
$fieldManagers = $db->query("SELECT id FROM sys_roles WHERE name LIKE '%현장대리인%'");
foreach ($fieldManagers as $role) {
    foreach ($teamLeaderPolicies as $policy) {  // Same as team leaders
        $db->execute("INSERT IGNORE INTO role_policies (role_id, policy_id) VALUES (?, ?)", [
            $role['id'], $policy['id']
        ]);
    }
}
echo "✓ Field manager roles configured\n";

// 4. Team chiefs
$chiefs = $db->query("SELECT id FROM sys_roles WHERE name LIKE '%조장%'");
$chiefPolicies = $db->query("
    SELECT id FROM permission_policies 
    WHERE name IN (
        '본인 직원정보 조회', '본인 연차 조회', '같은 과 전체 연차 조회',
        '본인 부서 차량 조회', '본인 부서 지급품 조회'
    )
");
foreach ($chiefs as $role) {
    foreach ($chiefPolicies as $policy) {
        $db->execute("INSERT IGNORE INTO role_policies (role_id, policy_id) VALUES (?, ?)", [
            $role['id'], $policy['id']
        ]);
    }
}
echo "✓ Chief roles configured\n";

// 5. Regular workers
$workers = $db->query("SELECT id FROM sys_roles WHERE name LIKE '%상차원%' OR name LIKE '%청소원%'");
foreach ($workers as $role) {
    foreach ($chiefPolicies as $policy) {  // Same as chiefs
        $db->execute("INSERT IGNORE INTO role_policies (role_id, policy_id) VALUES (?, ?)", [
            $role['id'], $policy['id']
        ]);
    }
}
echo "✓ Worker roles configured\n";

// Verify
echo "\n=== VERIFICATION ===\n";
$count = $db->fetchOne("SELECT COUNT(*) as cnt FROM role_policies");
echo "Total role-policy mappings: " . $count['cnt'] . "\n";

$mappings = $db->query("
    SELECT r.name as role_name, COUNT(rp.policy_id) as policy_count
    FROM sys_roles r
    LEFT JOIN role_policies rp ON r.id = rp.role_id
    GROUP BY r.id, r.name
    ORDER BY r.name");

foreach ($mappings as $map) {
    echo sprintf("  %s: %d policies\n", $map['role_name'], $map['policy_count']);
}
