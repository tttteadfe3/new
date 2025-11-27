<?php
/**
 * PBAC 시스템 최종 검증 스크립트
 * 
 * 이 스크립트는 PBAC 시스템이 올바르게 설정되었는지 확인합니다.
 */

require_once __DIR__ . '/vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();
require_once __DIR__ . '/config/config.php';

use App\Core\Database;

$db = new Database();

echo "==============================================\n";
echo "  PBAC 시스템 최종 검증 리포트\n";
echo "==============================================\n\n";

// 1. 테이블 존재 확인
echo "1. 데이터베이스 테이블 확인\n";
echo "----------------------------\n";
$tables = ['permission_resource_types', 'permission_actions', 'permission_policies', 
           'role_policies', 'user_policies'];
foreach ($tables as $table) {
    $result = $db->fetchOne("SHOW TABLES LIKE '$table'");
    echo ($result ? "✓" : "✗") . " {$table}\n";
}

// 2. 기본 데이터 확인
echo "\n2. 기본 데이터 확인\n";
echo "----------------------------\n";
$resourceTypes = $db->fetchOne("SELECT COUNT(*) as cnt FROM permission_resource_types");
echo "리소스 타입: {$resourceTypes['cnt']}개\n";

$actions = $db->fetchOne("SELECT COUNT(*) as cnt FROM permission_actions");
echo "액션: {$actions['cnt']}개\n";

$policies = $db->fetchOne("SELECT COUNT(*) as cnt FROM permission_policies");
echo "정책: {$policies['cnt']}개\n";

$rolePolicies = $db->fetchOne("SELECT COUNT(*) as cnt FROM role_policies");
echo "역할-정책 매핑: {$rolePolicies['cnt']}개\n";

// 3. 역할별 정책 수 확인
echo "\n3. 역할별 정책 할당 현황\n";
echo "----------------------------\n";
$roleStats = $db->query("
    SELECT r.name, COUNT(rp.policy_id) as policy_count
    FROM sys_roles r
    LEFT JOIN role_policies rp ON r.id = rp.role_id
    GROUP BY r.id, r.name
    ORDER BY policy_count DESC, r.name
");

foreach ($roleStats as $stat) {
    echo sprintf("%-20s: %2d개 정책\n", $stat['name'], $stat['policy_count']);
}

// 4. 리소스별 정책 확인
echo "\n4. 리소스별 정책 현황\n";
echo "----------------------------\n";
$resourceStats = $db->query("
    SELECT 
        rt.name as resource,
        COUNT(DISTINCT p.id) as policy_count,
        GROUP_CONCAT(DISTINCT a.name ORDER BY a.name SEPARATOR ', ') as actions
    FROM permission_resource_types rt
    LEFT JOIN permission_policies p ON rt.id = p.resource_type_id
    LEFT JOIN permission_actions a ON p.action_id = a.id
    GROUP BY rt.id, rt.name
    ORDER BY policy_count DESC
");

foreach ($resourceStats as $stat) {
    echo sprintf("%-12s: %d개 정책 [%s]\n", 
        $stat['resource'], 
        $stat['policy_count'],
        $stat['actions'] ?: '없음'
    );
}

// 5. PolicyEngine 필수 서비스 확인
echo "\n5. PolicyEngine 의존성 확인\n";
echo "----------------------------\n";
try {
    $deptService = new \App\Services\DepartmentHierarchyService($db);
    echo "✓ DepartmentHierarchyService\n";
    
    $sessionManager = new \App\Core\SessionManager();
    echo "✓ SessionManager\n";
    
    $policyEngine = new \App\Services\PolicyEngine($db, $sessionManager, $deptService);
    echo "✓ PolicyEngine\n";
} catch (Exception $e) {
    echo "✗ 오류: " . $e->getMessage() . "\n";
}

// 6. 경고 및 권장사항
echo "\n6. 시스템 상태 및 권장사항\n";
echo "----------------------------\n";

$warnings = [];
$recommendations = [];

// Check if department managers are configured
$deptManagers = $db->fetchOne("SELECT COUNT(*) as cnt FROM hr_department_managers");
if ($deptManagers['cnt'] == 0) {
    $warnings[] = "부서 관리자가 설정되지 않았습니다 (hr_department_managers 테이블 비어있음)";
    $recommendations[] = "팀장/현장대리인이 관리하는 부서를 hr_department_managers에 설정하세요";
}

// Check if users have roles
$usersWithRoles = $db->fetchOne("SELECT COUNT(DISTINCT user_id) as cnt FROM sys_user_roles");
$totalUsers = $db->fetchOne("SELECT COUNT(*) as cnt FROM sys_users WHERE status = '활성'");
if ($usersWithRoles['cnt'] < $totalUsers['cnt']) {
    $warnings[] = sprintf("일부 사용자가 역할이 없습니다 (%d/%d)", 
        $usersWithRoles['cnt'], 
        $totalUsers['cnt']
    );
    $recommendations[] = "모든 활성 사용자에게 적절한 역할을 할당하세요";
}

// Check for deprecated DataScopeService usage (Windows compatible)
$dataScopeUsage = 0;  // Skip grep on Windows
if ($dataScopeUsage > 0) {
    $recommendations[] = "일부 컨트롤러가 여전히 DataScopeService를 사용 중입니다";
    $recommendations[] = "가능하면 PolicyEngine으로 마이그레이션하세요";
}

if (empty($warnings)) {
    echo "✓ 심각한 문제 없음\n";
} else {
    echo "⚠️  경고:\n";
    foreach ($warnings as $i => $warning) {
        echo "   " . ($i + 1) . ". " . $warning . "\n";
    }
}

if (!empty($recommendations)) {
    echo "\n💡 권장사항:\n";
    foreach ($recommendations as $i => $rec) {
        echo "   " . ($i + 1) . ". " . $rec . "\n";
    }
}

echo "\n==============================================\n";
echo "검증 완료!\n";
echo "==============================================\n";
