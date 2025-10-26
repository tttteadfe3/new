<?php \App\Core\View::getInstance()->startSection('content'); ?>
    <h1>환영합니다, <?= htmlspecialchars($nickname ?? '사용자') ?>님!</h1>
    <p>대시보드입니다.</p>
<?php \App\Core\View::getInstance()->endSection(); ?>
