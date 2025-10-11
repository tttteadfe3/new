<?php
use App\Core\View;

// User info is now passed from the controller/middleware via ViewDataService as the $user variable.
$currentUserNickname = $user['nickname'] ?? '사용자';
$profileImageUrl = $user['profile_image_url'] ?? BASE_ASSETS_URL . '/assets/images/users/avatar.png';
?>
<!doctype html>
<html lang="ko" data-layout="vertical" data-sidebar="dark" data-sidebar-size="lg" data-preloader="enable" data-theme="default">

<head>

    <meta charset="utf-8" />
    <title>원진실업(주)</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- App favicon -->
    <link rel="shortcut icon" href="assets/images/favicon.ico">

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

    <!-- auth-page wrapper -->
    <div class="auth-page-wrapper auth-bg-cover py-5 d-flex justify-content-center align-items-center min-vh-100">
        <div class="bg-overlay"></div>
        <!-- auth-page content -->
        <div class="auth-page-content overflow-hidden pt-lg-5">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-xl-5">
                        <div class="card overflow-hidden card-bg-fill galaxy-border-none">
                            <div class="card-body p-4">
                                <?= \App\Core\View::getInstance()->yieldSection('content') ?>
                            </div>
                        </div>
                        <!-- end card -->
                    </div>
                    <!-- end col -->

                </div>
                <!-- end row -->
            </div>
            <!-- end container -->
        </div>
        <!-- end auth page content -->
    </div>
    <!-- end auth-page-wrapper -->

    <!-- JAVASCRIPT -->
    <script src="<?= BASE_ASSETS_URL ?>/assets/libs/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="<?= BASE_ASSETS_URL ?>/assets/libs/simplebar/simplebar.min.js"></script>
    <script src="<?= BASE_ASSETS_URL ?>/assets/libs/node-waves/waves.min.js"></script>
    <script src="<?= BASE_ASSETS_URL ?>/assets/libs/feather-icons/feather.min.js"></script>
    <script src="<?= BASE_ASSETS_URL ?>/assets/js/pages/plugins/lord-icon-2.1.0.js"></script>
    <script src="<?= BASE_ASSETS_URL ?>/assets/js/plugins.js"></script>

</body>
</html>
