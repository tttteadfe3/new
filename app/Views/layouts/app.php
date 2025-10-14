<?php
use App\Core\View;

// Data is now passed from ViewDataService, no need for static calls here.
$currentUserNickname = $user['nickname'] ?? '사용자';
$profileImageUrl = $user['profile_image_url'] ?? BASE_ASSETS_URL . '/assets/images/users/avatar.png';
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
    <?= \App\Core\View::getInstance()->yieldSection('css') ?>

    <!-- Default CSS -->
    <!-- toastify-js Css -->
    <link href="<?= BASE_ASSETS_URL ?>/assets/libs/toastify-js/src/toastify.css" rel="stylesheet" type="text/css" />
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
    <!-- Custom Layout CSS -->
    <link href="<?= BASE_ASSETS_URL ?>/assets/css/custom-layout.css" rel="stylesheet" type="text/css" />
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
    <script src="<?= BASE_ASSETS_URL ?>/assets/js/utils/ui-helpers.js"></script>

    <!-- Common Application JS -->
    <script src="<?= BASE_ASSETS_URL ?>/assets/js/services/api-service.js"></script>
    <script src="<?= BASE_ASSETS_URL ?>/assets/js/core/base-page.js"></script>

    <!-- Conditionally Rendered JS Config from Controller -->
    <?php if (isset($jsConfig)): ?>
    <script>
        window.AppConfig = <?= json_encode($jsConfig) ?>;
    </script>
    <?php endif; ?>

    <!-- Dynamic JS Section -->
    <?= \App\Core\View::getInstance()->yieldSection('js') ?>

    <!-- App js -->
    <script src="<?= BASE_ASSETS_URL ?>/assets/js/app.js"></script>

</body>
</html>

<!-- Leave Request Modal -->
<div class="modal fade" id="leave-request-modal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">연차 신청</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="leave-request-form">
                    <div class="mb-3">
                        <label for="leave_type" class="form-label">휴가 종류</label>
                        <select class="form-select" id="leave_type" name="leave_type">
                            <option value="annual">연차 (하루)</option>
                            <option value="half_day">연차 (반차)</option>
                            <option value="sick">병가</option>
                            <option value="special">특별휴가</option>
                            <option value="other">기타</option>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="start_date" class="form-label">시작일</label>
                            <input type="date" class="form-control" id="start_date" name="start_date" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="end_date" class="form-label">종료일</label>
                            <input type="date" class="form-control" id="end_date" name="end_date" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="days_count" class="form-label">사용 일수</label>
                        <input type="number" step="0.5" class="form-control" id="days_count" name="days_count" readonly required>
                        <div id="leave-date-feedback" class="form-text">시작일과 종료일을 선택하면 사용일수가 자동으로 계산됩니다.</div>
                    </div>
                    <div class="mb-3">
                        <label for="reason" class="form-label">사유</label>
                        <textarea class="form-control" id="reason" name="reason" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">닫기</button>
                <button type="submit" form="leave-request-form" class="btn btn-primary">제출하기</button>
            </div>
        </div>
    </div>
</div>
