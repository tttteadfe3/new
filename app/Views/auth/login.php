<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
    <!-- You should link your CSS files here -->
    <link href="/assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
    <link href="/assets/css/icons.min.css" rel="stylesheet" type="text/css" />
    <link href="/assets/css/app.min.css" rel="stylesheet" type="text/css" />
    <link href="/assets/css/custom.min.css" rel="stylesheet" type="text/css" />
    <style>
        .btn-kakao {
            background-color: #FEE500;
            color: #191919;
            border-color: #FEE500;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .kakao-icon {
            width: 24px;
            height: 24px;
            margin-right: 8px;
        }
    </style>
</head>
<body>

    <div class="auth-page-wrapper pt-5">
        <!-- auth page bg -->
        <div class="auth-one-bg-position auth-one-bg" id="auth-particles">
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
                                <img src="/assets/images/logo-sm-1.png" alt="" height="100">
                            </div>
                            <div class="text-center mt-2">
                                <img src="/assets/images/logo-light.png" alt="" height="20">
                            </div>
                            <p class="mt-3 fs-15 fw-medium">생활폐기물 수집운반 관리 프로그램</p>
                        </div>
                    </div>
                </div>
                <!-- end row -->

                <div class="row justify-content-center">
                    <div class="col-md-8 col-lg-6 col-xl-5">
                        <div class="card mt-4 card-bg-fill">
                            <div class="card-body p-4 text-center">
                                <div class="pt-2">
                                    <h4>로그인 후 사용 가능합니다.</h4>
                                    <?php if (isset($_GET['error'])): ?>
                                        <div class="alert alert-danger mt-3">
                                            Login failed: <?= htmlspecialchars($_GET['error']) ?>
                                        </div>
                                    <?php endif; ?>
                                    <div class="mt-4">
                                    <a href="<?= htmlspecialchars($kakaoLoginUrl) ?>" class="btn btn-lg btn-kakao btn-block">
                                        <svg class="kakao-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                                            <path fill="#000" d="M12 3C6.48 3 2 6.92 2 11.5c0 2.77 1.73 5.22 4.45 6.77-.15.57-.83 3.15-.86 3.36 0 0-.02.14.07.19.09.05.19.01.19.01.25-.04 2.9-1.9 3.36-2.22.91.14 1.84.21 2.79.21 5.52 0 10-3.92 10-8.5S17.52 3 12             3z"/>
                                        </svg>
                                        카카오 계정으로 로그인
                                    </a>
                                </div>
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

    </div>
    <!-- end auth-page-wrapper -->
    <!-- JAVASCRIPT -->
    <script src="/assets/libs/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="/assets/libs/simplebar/simplebar.min.js"></script>
    <script src="/assets/libs/node-waves/waves.min.js"></script>
    <script src="/assets/libs/feather-icons/feather.min.js"></script>
    <script src="/assets/js/pages/plugins/lord-icon-2.1.0.js"></script>
    <script src="/assets/js/plugins.js"></script>

    <!-- particles js -->
    <script src="/assets/libs/particles.js/particles.js"></script>
    <!-- particles app js -->
    <script src="/assets/js/pages/particles.app.js"></script>
</body>
</html>