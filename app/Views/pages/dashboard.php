<?php \App\Core\View::startSection('content'); ?>
    <h1>Welcome, <?= htmlspecialchars($nickname ?? 'User') ?>!</h1>
    <p>This is the dashboard.</p>
<?php \App\Core\View::endSection(); ?>