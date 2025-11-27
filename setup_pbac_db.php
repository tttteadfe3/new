<?php
require_once __DIR__ . '/vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/app/Core/Database.php';

use App\Core\Database;

$db = new Database();

$files = [
    __DIR__ . '/database/migrations/20251127_create_permission_system_tables.sql',
    __DIR__ . '/database/seeds/20251127_01_permission_resource_types.sql',
    __DIR__ . '/database/seeds/20251127_02_permission_actions.sql',
    __DIR__ . '/database/seeds/20251127_03_permission_policies.sql',
    __DIR__ . '/database/seeds/20251127_04_role_policies.sql'
];

foreach ($files as $file) {
    if (!file_exists($file)) {
        echo "File not found: $file\n";
        continue;
    }
    
    echo "Executing $file...\n";
    $sql = file_get_contents($file);
    
    // Split by semicolon to execute multiple statements
    $statements = explode(';', $sql);
    
    foreach ($statements as $stmt) {
        $stmt = trim($stmt);
        if (empty($stmt)) continue;
        
        try {
            $db->execute($stmt);
        } catch (Exception $e) {
            echo "Error executing statement: " . substr($stmt, 0, 50) . "... : " . $e->getMessage() . "\n";
        }
    }
    echo "Success.\n";
}

echo "PBAC DB Setup Complete.\n";
