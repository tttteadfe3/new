<?php
require_once dirname(__DIR__) . '/config/config.php';

$code = $_GET['code'] ?? '500';

$title = '오류 발생';
$message = '알 수 없는 오류가 발생했습니다.';
$icon = 'bi-exclamation-diamond-fill';
$color = 'text-danger';
$return_url = BASE_URL;
$return_text = '홈으로 돌아가기';

switch ($code) {
    case '403':
        $title = '접근 권한 없음 (403 Forbidden)';
        $message = '이 페이지에 접근할 수 있는 권한이 없습니다.';
        $icon = 'bi-hand-thumbs-down-fill';
        $return_url = BASE_URL . '/auth/logout.php';
        $return_text = '로그아웃';
        break;
    case '500':
        $title = '시스템 오류 (500)';
        $message = '요청 처리 중 예기치 않은 오류가 발생했습니다.<br>문제가 지속되면 관리자에게 문의해주세요.';
        $icon = 'bi-bug-fill';
        break;
    case 'blocked':
        $title = '계정이 차단되었습니다';
        $message = '시스템 관리자에게 문의해주세요.';
        $icon = 'bi-slash-circle-fill';
        $return_url = BASE_URL . '/index.php';
        $return_text = '로그인 페이지로';
        break;
    case 'invalid_status':
        $title = '비정상 계정 상태';
        $message = '계정 상태값이 유효하지 않습니다. 관리자에게 문의하여 계정을 확인해주세요.';
        $icon = 'bi-person-exclamation-fill';
        $color = 'text-danger';
        $return_url = BASE_URL . '/auth/logout.php';
        $return_text = '로그아웃';
        break;
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title><?= e($title) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style> body { display: flex; align-items: center; justify-content: center; height: 100vh; text-align: center; } </style>
</head>
<body>
    <div class="container">
        <h1 class="display-1 <?= e($color) ?>"><i class="bi <?= e($icon) ?>"></i></h1>
        <p class="lead fw-bold mt-4"><?= e($title) ?></p>
        <p class="text-muted"><?= nl2br($message) ?></p>
        <a href="<?= e($return_url) ?>" class="btn btn-primary mt-3"><?= e($return_text) ?></a>
    </div>
</body>
</html>