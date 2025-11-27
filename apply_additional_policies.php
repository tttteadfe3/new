<?php
require_once __DIR__ . '/vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();
require_once __DIR__ . '/config/config.php';

use App\Core\Database;

$db = new Database();

echo "=== 추가 정책 적용 ===\n\n";

$files = [
    'database/seeds/20251127_05_additional_policies.sql',
    'database/seeds/20251127_06_additional_role_policies.sql'
];

foreach ($files as $file) {
    $fullPath = __DIR__ . '/' . $file;
    if (!file_exists($fullPath)) {
        echo "✗ 파일 없음: $file\n";
        continue;
    }
    
    echo "실행 중: $file\n";
    $sql = file_get_contents($fullPath);
    $statements = explode(';', $sql);
    
    $success = 0;
    $errors = 0;
    
    foreach ($statements as $stmt) {
        $stmt = trim($stmt);
        if (empty($stmt) || strpos($stmt, '--') === 0) continue;
        
        try {
            $db->execute($stmt);
            $success++;
        } catch (Exception $e) {
            $errors++;
            echo "  경고: " . substr($e->getMessage(), 0, 80) . "...\n";
        }
    }
    
    echo "  ✓ 성공: {$success}개, 오류: {$errors}개\n\n";
}

// 결과 확인
echo "=== 결과 확인 ===\n";
$policies = $db->fetchOne("SELECT COUNT(*) as cnt FROM permission_policies");
echo "총 정책 수: {$policies['cnt']}개\n";

$mappings = $db->fetchOne("SELECT COUNT(*) as cnt FROM role_policies");
echo "총 역할-정책 매핑: {$mappings['cnt']}개\n";

echo "\n완료!\n";
