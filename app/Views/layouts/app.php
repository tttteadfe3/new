<?php
use App\Core\View;
use App\Core\SessionManager;

// Get user info from session
$currentUserNickname = SessionManager::get('user')['nickname'] ?? '사용자';
$profileImageUrl = SessionManager::get('user')['profile_image_url'] ?? BASE_ASSETS_URL . '/assets/images/users/avatar.png';
?>
<!doctype html>
<html lang="ko" data-layout="vertical" data-sidebar="dark" data-sidebar-size="lg" data-preloader="enable" data-theme="default">
<head>
    <meta charset="utf-8" />
    <title>원진실업(주)<?= isset($pageTitle) ? ' | ' . e($pageTitle) : '' ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- App favicon -->
    <link rel="shortcut icon" href="<?= BASE_ASSETS_URL ?>/assets/images/favicon.ico">

    <!-- Dynamic CSS Section -->
    <?= View::yieldSection('css') ?>

    <!-- Default CSS -->
    <!-- sweetalert2 Css -->
    <link href="<?= BASE_ASSETS_URL ?>/assets/libs/sweetalert2/sweetalert2.min.css" rel="stylesheet" type="text/css" />
    <!-- Bootstrap Css -->
    <link href="<?= BASE_ASSETS_URL ?>/assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
    <!-- Icons Css -->
    <link href="<?= BASE_ASSETS_URL ?>/assets/css/icons.min.css" rel="stylesheet" type="text/css" />
    <!-- App Css-->
    <link href="<?= BASE_ASSETS_URL ?>/assets/css/app.min.css" rel="stylesheet" type="text/css" />
    <!-- custom Css-->
    <link href="<?= BASE_ASSETS_URL ?>/assets/css/custom.min.css" rel="stylesheet" type="text/css" />
</head>

<body>
    <!-- Begin page -->
    <div id="layout-wrapper">

        <?php include __DIR__ . '/header.php'; ?>
        <?php include __DIR__ . '/sidebar.php'; ?>
        
        <!-- Vertical Overlay-->
        <div class="vertical-overlay"></div>

        <!-- ============================================================== -->
        <!-- Start right Content here -->
        <!-- ============================================================== -->
        <div class="main-content">
            <div class="page-content">
                <div class="container-fluid">
                    <?= View::yieldSection('content') ?>
                </div>
                <!-- container-fluid -->
            </div>
            <!-- End Page-content -->

            <?php include __DIR__ . '/footer.php'; ?>
        </div>
        <!-- end main content-->

    </div>
    <!-- END layout-wrapper -->

    <!--start back-to-top-->
    <button onclick="topFunction()" class="btn btn-danger btn-icon" id="back-to-top">
        <i class="ri-arrow-up-line"></i>
    </button>
    <!--end back-to-top-->

    <!--preloader-->
    <div id="preloader">
        <div id="status">
            <div class="spinner-border text-primary avatar-sm" role="status">
                <span class="visually-hidden">Loading...</span>
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
    <!-- Custom UI js -->
    <script src="<?= BASE_ASSETS_URL ?>/assets/js/utils/ui.js"></script>

    <!-- Dynamic JS Section -->
    <?= View::yieldSection('js') ?>

    <!-- App js -->
    <script src="<?= BASE_ASSETS_URL ?>/assets/js/app.js"></script>
</body>
</html>