<?php
require_once __DIR__ . '/vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();
require_once __DIR__ . '/config/config.php';

use App\Core\Database;

$db = new Database();

echo "Updating role-policy mappings...\n";

$sql = file_get_contents(__DIR__ . '/database/seeds/20251127_04_role_policies.sql');
$statements = explode(';', $sql);

foreach ($statements as $stmt) {
    $stmt = trim($stmt);
    if (empty($stmt)) continue;
    if (strpos($stmt, '--') === 0) continue;  // Skip comments
    
    try {
        $db->execute($stmt);
    } catch (Exception $e) {
        echo "Error executing statement: " . substr($stmt, 0, 50) . "...\n";
        echo "Error: " . $e->getMessage() . "\n";
    }
}

echo "Done!\n\n";

// Verify results
echo "=== ROLE-POLICY MAPPINGS ===\n";
$mappings = $db->query('
    SELECT r.name as role_name, p.name as policy_name
    FROM role_policies rp
    JOIN sys_roles r ON rp.role_id = r.id
    JOIN permission_policies p ON rp.policy_id = p.id
    ORDER BY r.name, p.name
');

$currentRole = null;
foreach($mappings as $map) {
    if ($currentRole !== $map['role_name']) {
        $currentRole = $map['role_name'];
        echo "\n{$currentRole}:\n";
    }
    echo "  - {$map['policy_name']}\n";
}
