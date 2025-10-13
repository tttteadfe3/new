<?php
// install/run.php

@set_time_limit(0); // 스크립트 실행 시간 제한 없음

// 서버-전송 이벤트(SSE)를 위한 헤더 설정
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');

// 프로젝트 루트 경로 정의
define('ROOT_PATH', dirname(__DIR__));

/**
 * 진행 상황 메시지를 클라이언트로 전송하는 함수
 * @param string $message 전송할 메시지
 * @param string $type 메시지 타입 (info, success, error)
 * @param bool $completed 설치 완료 여부
 * @param string|null $details 추가 정보
 */
function sendMessage(string $message, string $type = 'info', bool $completed = false, ?string $details = null) {
    $data = ['message' => $message, 'type' => $type, 'completed' => $completed, 'details' => $details];
    echo "data: " . json_encode($data) . "\n\n";
    // 버퍼를 비워 즉시 클라이언트에 전송
    if (ob_get_level() > 0) {
        ob_flush();
    }
    flush();
}

/**
 * 에러 메시지를 전송하고 스크립트를 종료하는 함수
 * @param string $message
 * @param string|null $details
 */
function sendErrorAndExit(string $message, ?string $details = null) {
    sendMessage($message, 'error', true, $details);
    exit();
}

// --- 실제 설치 로직 ---

try {
    // 1단계: 환경 검사
    sendMessage('1. 환경 검사를 시작합니다...');
    sleep(1);

    // PHP 버전 검사
    if (version_compare(PHP_VERSION, '8.0.0', '<')) {
        sendErrorAndExit('PHP 버전 오류', 'PHP 8.0.0 이상이 필요합니다. 현재 버전: ' . PHP_VERSION);
    }
    sendMessage('PHP 버전 (' . PHP_VERSION . ') ... 확인', 'success');

    // PHP 확장 프로그램 검사
    $requiredExtensions = ['pdo_mysql', 'curl', 'mbstring'];
    $missingExtensions = [];
    foreach ($requiredExtensions as $ext) {
        if (!extension_loaded($ext)) {
            $missingExtensions[] = $ext;
        }
    }
    if (!empty($missingExtensions)) {
        sendErrorAndExit('필수 PHP 확장 프로그램 누락', '다음 확장 프로그램 설치가 필요합니다: ' . implode(', ', $missingExtensions));
    }
    sendMessage('필수 PHP 확장 프로그램 ... 확인', 'success');

    // .env 파일 존재 여부 검사
    $envFile = ROOT_PATH . '/.env';
    if (!file_exists($envFile)) {
        sendErrorAndExit('.env 파일 없음', '.env.example 파일을 .env 로 복사하고 데이터베이스 정보를 입력해주세요.');
    }
    sendMessage('.env 파일 존재 ... 확인', 'success');

    // .env 파일 로드
    require_once ROOT_PATH . '/vendor/autoload.php';
    $dotenv = Dotenv\Dotenv::createImmutable(ROOT_PATH);
    $dotenv->load();

    // 디렉토리 쓰기 권한 검사
    $writableDirs = [ROOT_PATH . '/storage/logs'];
    $uploadsDir = ROOT_PATH . '/public/uploads';
    if (!is_dir($uploadsDir)) {
        if (!@mkdir($uploadsDir, 0775, true)) { // 권한을 775로 설정
            sendErrorAndExit('디렉토리 생성 실패', $uploadsDir . ' 디렉토리를 생성할 수 없습니다. 상위 디렉토리의 권한을 확인해주세요.');
        }
    }
    $writableDirs[] = $uploadsDir;

    foreach ($writableDirs as $dir) {
        if (!is_writable($dir)) {
            sendErrorAndExit('디렉토리 쓰기 권한 오류', $dir . ' 디렉토리에 웹 서버의 쓰기 권한(chmod 775)이 필요합니다.');
        }
    }
    sendMessage('디렉토리 쓰기 권한 ... 확인', 'success');
    sendMessage('환경 검사 완료!', 'success');
    sleep(1);

    // 2단계: 데이터베이스 설정
    sendMessage('2. 데이터베이스 설정을 시작합니다...');

    $dbHost = $_ENV['DB_HOST'] ?? '';
    $dbPort = $_ENV['DB_PORT'] ?? '3306';
    $dbName = $_ENV['DB_DATABASE'] ?? '';
    $dbUser = $_ENV['DB_USERNAME'] ?? '';
    $dbPass = $_ENV['DB_PASSWORD'] ?? '';

    if (empty($dbHost) || empty($dbName) || empty($dbUser)) {
        sendErrorAndExit('DB 정보 부족', '.env 파일에 DB_HOST, DB_DATABASE, DB_USERNAME 을 올바르게 설정해주세요.');
    }

    sendMessage($dbName . ' 데이터베이스에 연결 시도 중...');
    try {
        $dsn = "mysql:host={$dbHost};port={$dbPort};charset=utf8mb4";
        $pdo = new PDO($dsn, $dbUser, $dbPass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);

        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE `{$dbName}`");

    } catch (PDOException $e) {
        sendErrorAndExit('데이터베이스 연결 실패', $e->getMessage());
    }
    sendMessage('데이터베이스 연결 및 선택 성공!', 'success');
    sleep(1);

    /**
     * SQL 파일을 읽어 실행하는 함수
     * @param PDO $pdo
     * @param string $filePath
     * @throws Exception
     */
    function importSqlFile(PDO $pdo, string $filePath) {
        $sql = file_get_contents($filePath);
        if ($sql === false) {
            throw new Exception("SQL 파일을 읽을 수 없습니다: " . $filePath);
        }
        // 주석 제거 및 문장 분리
        $sql = preg_replace('/--.*/m', '', $sql);
        $sql = preg_replace('#/\*.*?\*/#s', '', $sql);
        $statements = explode(';', $sql);

        foreach ($statements as $statement) {
            $statement = trim($statement);
            if (!empty($statement)) {
                $pdo->exec($statement);
            }
        }
    }

    // 스키마 임포트
    $schemaFile = ROOT_PATH . '/database/schema.sql';
    sendMessage('데이터베이스 스키마 임포트 중...');
    if (!file_exists($schemaFile)) {
        sendErrorAndExit('스키마 파일 없음', $schemaFile . ' 파일을 찾을 수 없습니다.');
    }
    importSqlFile($pdo, $schemaFile);
    sendMessage('스키마 임포트 완료!', 'success');
    sleep(1);

    // 시드 데이터 임포트
    sendMessage('초기 데이터(Seed) 임포트 중...');
    $seedDir = ROOT_PATH . '/database/seeds';
    $seedFiles = glob($seedDir . '/*.sql');
    sort($seedFiles, SORT_NATURAL); // 01, 02 순서로 정렬

    if (empty($seedFiles)) {
        sendMessage('시드 파일이 없습니다. 건너뜁니다.', 'info');
    } else {
        foreach ($seedFiles as $file) {
            importSqlFile($pdo, $file);
            sendMessage(basename($file) . ' ... 임포트 완료', 'success');
            usleep(100000); // 0.1초 대기
        }
        sendMessage('모든 시드 데이터 임포트 완료!', 'success');
    }
    sleep(1);

    // 최종 완료 메시지
    sendMessage('3. 설치 완료!');
    sendMessage('설치가 성공적으로 완료되었습니다. 보안을 위해 install 디렉토리를 반드시 삭제해주세요.', 'success', true);

} catch (Exception $e) {
    sendErrorAndExit('치명적인 오류 발생', $e->getMessage() . ' (File: ' . $e->getFile() . ' on line ' . $e->getLine() . ')');
}
