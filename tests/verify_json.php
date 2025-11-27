<?php
require_once __DIR__ . '/../vendor/autoload.php';

use App\Models\Department;

// Mock Department object
$dept = new Department();
$dept->id = 1;
$dept->name = 'Test Department';
$dept->parent_id = null;
$dept->path = '/1/';
$dept->created_at = '2025-01-01 00:00:00';
$dept->updated_at = '2025-01-01 00:00:00';

// Serialize
$json = json_encode($dept, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

echo "Serialized JSON:\n";
echo $json . "\n";

// Check for internal properties
if (strpos($json, 'attributes') !== false || strpos($json, '\u0000*\u0000') !== false) {
    echo "FAIL: Internal properties detected!\n";
    exit(1);
}

echo "PASS: JSON is clean.\n";
