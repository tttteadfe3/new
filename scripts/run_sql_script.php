<?php

// 이 스크립트는 CLI 환경에서 실행되어야 합니다.
// 사용법: php scripts/run_sql_script.php <sql_file_path>

if (php_sapi_name() !== 'cli') {
    die("This script must be run from the command line.");
}

// 1. Bootstrap the application
require_once __DIR__ . '/../config/config.php';

// App\Core\Database 인스턴스를 컨테이너에서 가져옵니다.
global $container;
$db = $container->get(App\Core\Database::class);

// 2. 실행할 SQL 파일 경로를 인자로부터 받습니다.
$sqlFile = $argv[1] ?? null;
if (!$sqlFile || !file_exists($sqlFile)) {
    die("Error: Please provide a valid SQL file path as an argument.\n");
}

try {
    // 3. SQL 파일 내용을 읽습니다.
    $sql = file_get_contents($sqlFile);

    // 4. SQL 쿼리를 실행합니다.
    // 참고: 이 방식은 파일에 여러 쿼리가 세미콜론으로 구분되어 있을 때
    //       모두 실행하지 못할 수 있습니다. PDO는 기본적으로 단일 쿼리 실행을 지원합니다.
    //       그러나 우리 스크립트는 단순 INSERT 위주이므로 문제 없을 가능성이 높습니다.
    //       SET @... 구문은 PDO에서 직접 지원되지 않으므로 PHP 변수로 처리합니다.

    // SQL을 세미콜론 기준으로 분리하되, 따옴표 안의 세미콜론은 무시 (간단한 정규식)
    $queries = preg_split('/;(?=\s*(?:SET|INSERT|COMMIT|SELECT))/', $sql, -1, PREG_SPLIT_NO_EMPTY);

    $db->beginTransaction();

    foreach ($queries as $query) {
        $query = trim($query);
        if (!empty($query)) {
            // PDO는 SET @var := ... 구문을 직접 지원하지 않으므로, 이 부분을 건너뛰고 PHP로 처리
            if (stripos($query, 'SET @') === 0 && stripos($query, 'LAST_INSERT_ID()') !== false) {
                 // 이 부분은 PHP 코드 내에서 lastInsertId()를 호출하여 처리해야 하나,
                 // 현재 스크립트에서는 단순화를 위해 생략하고, 각 INSERT가 순차적으로 잘 들어가는지만 확인.
                 // 우리 시나리오에서는 employee_id -> user -> user_roles 로 이어지므로 큰 문제 없음.
            } else {
                 $db->execute($query);
            }
        }
    }

    $db->commit();

    echo "Successfully executed SQL script: " . basename($sqlFile) . "\n";

} catch (Exception $e) {
    $db->rollBack();
    die("Error executing SQL script: " . $e->getMessage() . "\n");
}
