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


// 2. 경로 및 URL 상수
define('BASE_URL', 'https://wonsil.kr/s/n4');
define('BASE_ASSETS_URL', '/s/n4');
define('ROOT_PATH', dirname(__DIR__));

// 3. 데이터베이스 설정
define('DB_HOST', 'localhost');
define('DB_NAME', 'erp'); // 생성한 데이터베이스 이름
define('DB_USER', 'erp');
define('DB_PASS', 'Dnjstlf!23'); // DB 비밀번호
define('DB_CHARSET', 'utf8mb4');

// 4. 카카오 인증 설정
define('KAKAO_CLIENT_ID', '42f32b3a748e93c5ac949d79243a526f'); // 발급받은 REST API 키
define('KAKAO_REDIRECT_URI', BASE_URL . '/auth/kakao_callback.php');

// 5. 파일 업로드 설정
define('UPLOAD_DIR', ROOT_PATH . '/storage/'); // 절대 경로 사용
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_MIMES', ['image/jpeg', 'image/png', 'image/gif']);

// 6. 중앙화된 예외 처리기 (구조 개선)
set_exception_handler(function(Throwable $exception) {
    // 모든 에러는 일단 파일에 상세히 기록합니다.
	/*
    $logPath = ROOT_PATH . '/logs/error.log';
    $logMessage = "[" . date('Y-m-d H:i:s') . "] Uncaught Exception: " . $exception->getMessage() . "\n" .
                  "in " . $exception->getFile() . ":" . $exception->getLine() . "\n" .
                  $exception->getTraceAsString() . "\n\n";
    error_log($logMessage, 3, $logPath);
*/
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


// 7. 네임스페이스 기반 클래스 오토로더
spl_autoload_register(function ($class_name) {
    $prefix = 'App\\';
    $len = strlen($prefix);
    if (strncmp($prefix, $class_name, $len) !== 0) return;
    $relative_class = substr($class_name, $len);
    $file = ROOT_PATH . '/app/' . str_replace('\\', '/', $relative_class) . '.php';
    if (file_exists($file)) require_once $file;
});


// 8. 전역 함수 로드
require_once ROOT_PATH . '/app/Views/layouts/functions.php';


// 9. 세션 시작 및 관리
App\Core\SessionManager::start();
App\Core\SessionManager::regenerate();

// 10. 공통 페이지 변수 설정 (메뉴, 브레드크럼 등)
// 이 로직은 모든 페이지 컨트롤러가 실행되기 전에 한 번만 실행되어야 합니다.
use App\Repositories\MenuRepository;
use App\Repositories\UserRepository;
use App\Core\SessionManager;

$user_id = SessionManager::get('user')['id'] ?? 0;
$userPermissions = [];
if ($user_id) {
    $userPermissions = array_column(UserRepository::getPermissions($user_id), 'key');
}

$currentUrlPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
// BASE_ASSETS_URL (e.g., '/s/n4') is the prefix for all app routes. 
// We remove it to get the application-level path.
if (BASE_ASSETS_URL !== '/') {
    $currentUrlPath = str_replace(BASE_ASSETS_URL, '', $currentUrlPath);
}


$currentTopMenuId = MenuRepository::getCurrentTopMenuId($userPermissions, $currentUrlPath);
$sideMenuItems = [];
if ($currentTopMenuId) {
    $sideMenuItems = MenuRepository::getSubMenus($currentTopMenuId, $userPermissions, $currentUrlPath);
}