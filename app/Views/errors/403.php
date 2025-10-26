<?php
// 이 뷰는 이제 기본 'app' 레이아웃 내에서 렌더링됩니다.
// 레이아웃이 <head>, <body> 및 일반적인 페이지 구조를 처리합니다.
// 403 오류와 관련된 내용만 정의하면 됩니다.

// $pageTitle 및 $message와 같은 변수는 PermissionMiddleware에서 전달됩니다.
?>

<div class="container-fluid">
    <!-- 403 오류에 대한 페이지별 콘텐츠 -->
    <div class="d-flex align-items-center justify-content-center vh-100">
        <div class="text-center">
            <h1 class="display-1 fw-bold">403</h1>
            <p class="fs-3"> <span class="text-danger">이런!</span> 접근이 금지되었습니다.</p>
            <p class="lead">
                <?= htmlspecialchars($message ?? '이 페이지에 접근할 권한이 없습니다.', ENT_QUOTES, 'UTF-8') ?>
            </p>
            <a href="/" class="btn btn-primary">홈으로 이동</a>
        </div>
    </div>
</div>
