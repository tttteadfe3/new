<?php
// This view is now rendered within the main 'app' layout.
// The layout will handle the <head>, <body>, and general page structure.
// We only need to define the content specific to the 403 error.

// Variables like $pageTitle and $message are passed from the PermissionMiddleware.
?>

<div class="container-fluid">
    <!-- Page-specific content for 403 error -->
    <div class="d-flex align-items-center justify-content-center vh-100">
        <div class="text-center">
            <h1 class="display-1 fw-bold">403</h1>
            <p class="fs-3"> <span class="text-danger">Opps!</span> Forbidden.</p>
            <p class="lead">
                <?= htmlspecialchars($message ?? '이 페이지에 접근할 권한이 없습니다.', ENT_QUOTES, 'UTF-8') ?>
            </p>
            <a href="/" class="btn btn-primary">Go Home</a>
        </div>
    </div>
</div>