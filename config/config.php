<?php
// config/config.php

/**
 * ---------------------------------------------------------------
 * 환경 설정 (가장 중요)
 * ---------------------------------------------------------------
 *
 * 'development': 개발 중. 모든 에러를 화면에 상세히 표시합니다.
 * 'production': 실제 서비스 운영 중. 에러는 파일에만 기록하고,
 * 사용자에게는 친절한 표준 에러 페이지만 보여줍니다.
 *
 * !! 실제 서버에 배포할 때는 반드시 'production'으로 변경해야 합니다. !!
 */
define('ENVIRONMENT', 'development');

// 1. 에러 리포팅 설정
if (ENVIRONMENT === 'development') {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    error_reporting(0);
}
date_default_timezone_set('Asia/Seoul');


// 2. 경로 및 URL 상수 (.env 파일 사용 권장)
// .env 파일에 APP_URL=http://yourdomain.com/your-app-root 형식으로 설정하는 것을 권장합니다.
define('BASE_URL', rtrim($_ENV['APP_URL'] ?? 'http://localhost', '/'));
define('BASE_ASSETS_URL', BASE_URL);
define('ROOT_PATH', dirname(__DIR__));

// 3. 데이터베이스 설정 (.env 파일 사용)
define('DB_HOST', $_ENV['DB_HOST'] ?? 'localhost');
define('DB_NAME', $_ENV['DB_NAME'] ?? 'erp');
define('DB_USER', $_ENV['DB_USER'] ?? 'erp');
define('DB_PASS', $_ENV['DB_PASS'] ?? '');
define('DB_CHARSET', $_ENV['DB_CHARSET'] ?? 'utf8mb4');

// 4. 카카오 인증 설정 (.env 파일 사용)
define('KAKAO_CLIENT_ID', $_ENV['KAKAO_CLIENT_ID'] ?? '');
define('KAKAO_CLIENT_SECRET', $_ENV['KAKAO_CLIENT_SECRET'] ?? '');
define('KAKAO_REDIRECT_URI', $_ENV['KAKAO_REDIRECT_URI'] ?? '');
define('KAKAO_MAP_API_KEY', $_ENV['KAKAO_MAP_API_KEY'] ?? '');

// 5. 파일 업로드 설정
define('UPLOAD_DIR', ROOT_PATH . '/public/uploads/'); // 파일 시스템 절대 경로
define('UPLOAD_URL_PATH', '/uploads');                // 웹 루트 기준 상대 경로
define('UPLOAD_URL', BASE_URL . UPLOAD_URL_PATH);     // 전체 웹 URL
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_MIMES', ['image/jpeg', 'image/png', 'image/gif']);

// 6. 중앙화된 예외 처리기 (구조 개선)
set_exception_handler(function(Throwable $exception) {
    // 모든 에러는 일단 파일에 상세히 기록합니다.
    $logPath = ROOT_PATH . '/storage/logs/error.log';
    $logMessage = "[" . date('Y-m-d H:i:s') . "] Uncaught Exception: " . $exception->getMessage() . "\n" .
                  "in " . $exception->getFile() . ":" . $exception->getLine() . "\n" .
                  $exception->getTraceAsString() . "\n\n";
    error_log($logMessage, 3, $logPath);
    // 개발 모드일 경우, 화면에 상세 에러를 출력하고 즉시 종료합니다.
    if (ENVIRONMENT === 'development') {
        http_response_code(500);
        echo '<div style="font-family: monospace; border: 2px solid #dc3545; padding: 15px; margin: 15px; background-color: #f8d7da;">';
        echo '<h2 style="color: #721c24;">Fatal Error</h2>';
        echo '<p><strong>Message:</strong> ' . htmlspecialchars($exception->getMessage(), ENT_QUOTES, 'UTF-8') . '</p>';
        echo '<p><strong>File:</strong> ' . htmlspecialchars($exception->getFile(), ENT_QUOTES, 'UTF-8') . ' on line <strong>' . $exception->getLine() . '</strong></p>';
        echo '<h3>Stack Trace:</h3>';
        echo '<pre style="white-space: pre-wrap; word-wrap: break-word; background: #fff; padding: 10px; border-radius: 5px;">' . htmlspecialchars($exception->getTraceAsString(), ENT_QUOTES, 'UTF-8') . '</pre>';
        echo '</div>';
        exit;
    }

    // 운영 모드일 경우에만, 표준 에러 페이지로 리디렉션합니다.
    if (!headers_sent()) {
        http_response_code(500);
        header('Location: ' . BASE_URL . '/errors/error.php?code=500');
    }
    exit;
});


// 7. 네임스페이스 기반 클래스 오토로더 (제거됨)
// Composer의 PSR-4 오토로더가 이 역할을 담당합니다.


// 8. 전역 헬퍼 함수 로드
require_once ROOT_PATH . '/app/Core/helpers.php';


// 9. 세션 시작 및 관리 (public/index.php 로 이동)

// 10. 공통 페이지 변수 설정은 ViewDataService로 이전되었습니다.
// 이 파일은 이제 전역 설정 및 초기화만 담당합니다.

// 11. 지도 관련 설정
// .env 파일에서 ALLOWED_REGIONS 값을 읽어옵니다. 값이 없으면 기본값 '정왕1동'을 사용합니다.
$allowedRegionsStr = $_ENV['ALLOWED_REGIONS'] ?? '정왕1동';
$allowedRegionsArr = array_filter(array_map('trim', explode(',', $allowedRegionsStr)));
define('ALLOWED_REGIONS', $allowedRegionsArr);
