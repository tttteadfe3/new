<?php
use App\Core\View;

// 데이터는 이제 ViewDataService에서 전달되므로 여기서 정적 호출이 필요하지 않습니다.
$currentUserNickname = $user['nickname'] ?? '사용자';
$profileImageUrl = $user['profile_image_url'] ?? BASE_ASSETS_URL . '/assets/images/users/avatar.png';
?>
<!doctype html>
<html lang="ko" data-layout="vertical" data-sidebar="dark" data-sidebar-size="lg" data-preloader="enable" data-theme="default">
<head>
    <meta charset="utf-8" />
    <title>원진실업(주)<?= isset($pageTitle) ? ' | ' . e($pageTitle) : '' ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- 앱 파비콘 -->
    <link rel="shortcut icon" href="<?= BASE_ASSETS_URL ?>/assets/images/favicon.ico">

    <!-- 동적 CSS 섹션 -->
    <?= \App\Core\View::getInstance()->yieldSection('css') ?>

    <!-- 기본 CSS -->
    <!-- toastify-js Css -->
    <link href="<?= BASE_ASSETS_URL ?>/assets/libs/toastify-js/src/toastify.css" rel="stylesheet" type="text/css" />
    <!-- sweetalert2 Css -->
    <link href="<?= BASE_ASSETS_URL ?>/assets/libs/sweetalert2/sweetalert2.min.css" rel="stylesheet" type="text/css" />
    <!-- Bootstrap Css -->
    <link href="<?= BASE_ASSETS_URL ?>/assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
    <!-- 아이콘 Css -->
    <link href="<?= BASE_ASSETS_URL ?>/assets/css/icons.min.css" rel="stylesheet" type="text/css" />
    <!-- 앱 Css-->
    <link href="<?= BASE_ASSETS_URL ?>/assets/css/app.min.css" rel="stylesheet" type="text/css" />
    <!-- 사용자 정의 Css-->
    <link href="<?= BASE_ASSETS_URL ?>/assets/css/custom.min.css" rel="stylesheet" type="text/css" />
    <!-- 사용자 정의 레이아웃 CSS -->
    <link href="<?= BASE_ASSETS_URL ?>/assets/css/custom-layout.css" rel="stylesheet" type="text/css" />
</head>

<body>
    <!-- 페이지 시작 -->
    <div id="layout-wrapper">

        <?php include __DIR__ . '/header.php'; ?>
        <?php include __DIR__ . '/sidebar.php'; ?>
        
        <!-- 수직 오버레이-->
        <div class="vertical-overlay"></div>

        <!-- ============================================================== -->
        <!-- 여기에서 오른쪽 콘텐츠 시작 -->
        <!-- ============================================================== -->
        <div class="main-content">
            <div class="page-content">
                <div class="container-fluid">
                    <?php if (isset($flash_success) && $flash_success): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?= e($flash_success) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    <?php if (isset($flash_error) && $flash_error): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?= e($flash_error) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <?= \App\Core\View::getInstance()->yieldSection('content') ?>
                </div>
                <!-- container-fluid -->
            </div>
            <!-- 페이지-콘텐츠 끝 -->

            <?php include __DIR__ . '/footer.php'; ?>
        </div>
        <!-- 메인 콘텐츠 끝-->

    </div>
    <!-- 레이아웃-래퍼 끝 -->

    <!-- 맨 위로 가기 시작-->
    <button onclick="topFunction()" class="btn btn-danger btn-icon" id="back-to-top">
        <i class="ri-arrow-up-line"></i>
    </button>
    <!-- 맨 위로 가기 끝-->

    <!-- 사전 로더-->
    <div id="preloader">
        <div id="status">
            <div class="spinner-border text-primary avatar-sm" role="status">
                <span class="visually-hidden">로딩 중...</span>
            </div>
        </div>
    </div>

    <!-- JAVASCRIPT -->
    <script src="<?= BASE_ASSETS_URL ?>/assets/libs/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="<?= BASE_ASSETS_URL ?>/assets/libs/simplebar/simplebar.min.js"></script>
    <script src="<?= BASE_ASSETS_URL ?>/assets/libs/node-waves/waves.min.js"></script>
    <script src="<?= BASE_ASSETS_URL ?>/assets/libs/feather-icons/feather.min.js"></script>
    <script src="<?= BASE_ASSETS_URL ?>/assets/js/pages/plugins/lord-icon-2.1.0.js"></script>
    <script src="<?= BASE_ASSETS_URL ?>/assets/js/plugins.js"></script>

    <!-- toastify js -->
    <script src="<?= BASE_ASSETS_URL ?>/assets/libs/toastify-js/src/toastify.js"></script>
    <!-- sweetalert2 js -->
    <script src="<?= BASE_ASSETS_URL ?>/assets/libs/sweetalert2/sweetalert2.min.js"></script>
    <!-- 사용자 정의 UI js -->
    <script src="<?= BASE_ASSETS_URL ?>/assets/js/utils/ui-helpers.js"></script>

    <!-- 공통 애플리케이션 JS -->
    <script src="<?= BASE_ASSETS_URL ?>/assets/js/services/api-service.js"></script>
    <script src="<?= BASE_ASSETS_URL ?>/assets/js/core/base-page.js"></script>

    <!-- 컨트롤러에서 조건부로 렌더링된 JS 구성 -->
    <?php if (isset($jsConfig)): ?>
    <script>
        window.AppConfig = <?= json_encode($jsConfig) ?>;
    </script>
    <?php endif; ?>

    <!-- 동적 JS 섹션 -->
    <?= \App\Core\View::getInstance()->yieldSection('js') ?>

    <!-- 앱 js -->
    <script src="<?= BASE_ASSETS_URL ?>/assets/js/app.js"></script>

</body>
</html>
