<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Database;
use App\Repositories\DepartmentRepository;

// 데이터베이스 연결 설정
$config = require __DIR__ . '/../config/database.php';
$db = new Database($config);
$departmentRepository = new DepartmentRepository($db);

function calculatePath(int $deptId, array &$departments, DepartmentRepository $repo): string
{
    if (!isset($departments[$deptId])) {
        return '';
    }

    $department = $departments[$deptId];
    if ($department['parent_id'] === null) {
        return '/' . $department['id'] . '/';
    }

    // 부모 경로를 재귀적으로 계산
    $parentPath = calculatePath($department['parent_id'], $departments, $repo);
    return rtrim($parentPath, '/') . '/' . $department['id'] . '/';
}

try {
    echo "Starting department path migration...\n";

    $allDepartments = $departmentRepository->getAll();
    $departmentsById = [];
    foreach ($allDepartments as $dept) {
        $departmentsById[$dept['id']] = (array)$dept;
    }

    foreach ($departmentsById as $deptId => &$department) {
        $path = calculatePath($deptId, $departmentsById, $departmentRepository);
        $department['path'] = $path;
    }
    unset($department);

    $db->beginTransaction();

    foreach ($departmentsById as $deptId => $department) {
        echo "Updating department ID {$deptId} with path: {$department['path']}\n";
        $sql = "UPDATE hr_departments SET path = :path WHERE id = :id";
        $db->execute($sql, [':path' => $department['path'], ':id' => $deptId]);
    }

    $db->commit();

    echo "Migration completed successfully!\n";

} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    echo "An error occurred during migration: " . $e->getMessage() . "\n";
}
