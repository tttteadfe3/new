<?php
require_once __DIR__ . '/vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();
require_once __DIR__ . '/config/config.php';

use App\Core\Database;

$db = new Database();

echo "=== ROLES IN SYSTEM ===\n";
$roles = $db->query('SELECT * FROM sys_roles ORDER BY name');
foreach($roles as $role) {
    echo sprintf("%d: %s - %s\n", $role['id'], $role['name'], $role['description']);
}

echo "\n=== POLICIES IN SYSTEM ===\n";
$policies = $db->query('
    SELECT p.id, p.name, r.name as resource, a.name as action, p.scope_type 
    FROM permission_policies p 
    JOIN permission_resource_types r ON p.resource_type_id = r.id 
    JOIN permission_actions a ON p.action_id = a.id 
    ORDER BY r.name, p.priority
');
foreach($policies as $pol) {
    echo sprintf("%d: %s [%s.%s] - %s\n", 
        $pol['id'], 
        $pol['name'], 
        $pol['resource'], 
        $pol['action'], 
        $pol['scope_type']
    );
}

echo "\n=== ROLE-POLICY MAPPINGS ===\n";
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
