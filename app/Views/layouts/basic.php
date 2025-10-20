<!doctype html>
<html lang="ko">

<head>

    <meta charset="utf-8" />
    <title>원진실업(주)</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- App favicon -->
    <link rel="shortcut icon" href="<?= BASE_ASSETS_URL ?>/assets/images/favicon.ico">

    <!-- Layout config Js -->
    <script src="<?= BASE_ASSETS_URL ?>/assets/js/layout.js"></script>
    <!-- Bootstrap Css -->
    <link href="<?= BASE_ASSETS_URL ?>/assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
    <!-- Icons Css -->
    <link href="<?= BASE_ASSETS_URL ?>/assets/css/icons.min.css" rel="stylesheet" type="text/css" />
    <!-- App Css-->
    <link href="<?= BASE_ASSETS_URL ?>/assets/css/app.min.css" rel="stylesheet" type="text/css" />
    <!-- custom Css-->
    <link href="<?= BASE_ASSETS_URL ?>/assets/css/custom.min.css" rel="stylesheet" type="text/css" />

    <!-- Dynamic CSS Section -->
    <?= \App\Core\View::getInstance()->yieldSection('css') ?>

</head>

<body>

    <div class="auth-page-wrapper align-items-center pt-5">
        <!-- auth page bg -->
        <div class="auth-one-bg-position auth-one-bg">
            <div class="bg-overlay"></div>

            <div class="shape">
                <svg xmlns="http://www.w3.org/2000/svg" version="1.1" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 1440 120">
                    <path d="M 0,36 C 144,53.6 432,123.2 720,124 C 1008,124.8 1296,56.8 1440,40L1440 140L0 140z"></path>
                </svg>
            </div>
        </div>

        <!-- auth page content -->
        <div class="auth-page-content">
            <div class="container">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="text-center mt-sm-5 mb-4 text-white-50">
                            <div>
                                <a href="index.html" class="d-inline-block auth-logo">
                                    <img src="<?= BASE_ASSETS_URL ?>/assets/images/logo-light.png" alt="" height="20">
                                </a>
                            </div>
                            <p class="mt-3 fs-15 fw-medium">생활폐기물 수집운반 관리 프로그램</p>
                        </div>
                    </div>
                </div>
                <!-- end row -->

                <div class="row justify-content-center">
                    <div class="col-md-8 col-lg-6 col-xl-5">
                        <div class="card mt-4 card-bg-fill">
                            <div class="card-body p-4">
                                <?= \App\Core\View::getInstance()->yieldSection('content') ?>
                            </div>
                            <!-- end card body -->
                        </div>
                        <!-- end card -->
                    </div>
                </div>
                <!-- end row -->
            </div>
            <!-- end container -->
        </div>
        <!-- end auth page content -->

        <!-- footer -->
        <footer class="footer galaxy-border-none">
            <div class="container">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="text-center">
                            <p class="mb-0 text-muted">&copy;2025 원진실업(주)
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </footer>
        <!-- end Footer -->
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