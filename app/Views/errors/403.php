<?php
$title = '접근 권한 없음 (403 Forbidden)';
$message = '이 페이지에 접근할 수 있는 권한이 없습니다.';
$icon = 'bi-hand-thumbs-down-fill';
$color = 'text-danger';
$return_url = (defined('BASE_URL') ? BASE_URL : '') . '/auth/logout.php';
$return_text = '로그아웃';
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style> body { display: flex; align-items: center; justify-content: center; height: 100vh; text-align: center; } </style>
</head>
<body>
    <div class="container">
        <h1 class="display-1 <?= htmlspecialchars($color, ENT_QUOTES, 'UTF-8') ?>"><i class="bi <?= htmlspecialchars($icon, ENT_QUOTES, 'UTF-8') ?>"></i></h1>
        <p class="lead fw-bold mt-4"><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></p>
        <p class="text-muted"><?= nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8')) ?></p>
        <a href="<?= htmlspecialchars($return_url, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-primary mt-3"><?= htmlspecialchars($return_text, ENT_QUOTES, 'UTF-8') ?></a>
    </div>
</body>
</html>