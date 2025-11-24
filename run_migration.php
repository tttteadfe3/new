<?php

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/app/Core/Database.php';
require_once __DIR__ . '/database/migrations/2025_11_22_000000_create_vehicle_management_tables.php';

use App\Core\Database;

$db = new Database();
$migration = new CreateVehicleManagementTables($db);

try {
    $migration->down(); // Drop existing if any
    $migration->up();
    echo "Migration executed successfully.\n";
} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
}
