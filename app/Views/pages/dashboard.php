<?php \App\Core\\App\Core\View::getInstance()->startSection('content'); ?>
    <h1>Welcome, <?= htmlspecialchars($nickname ?? 'User') ?>!</h1>
    <p>This is the dashboard.</p>
<?php \App\Core\\App\Core\View::getInstance()->endSection(); ?>