<?php \App\Core\View::getInstance()->startSection('content'); ?>
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0"><?= e($pageTitle) ?></h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="/">홈</a></li>
                    <li class="breadcrumb-item active">지급품 관리</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title mb-4">지급품 관리 메뉴</h5>
                <div class="row g-4">
                    <!-- 분류 관리 -->
                    <div class="col-xl-3 col-md-6">
                        <div class="card card-animate border">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1">
                                        <h5 class="fs-15 mb-3">분류 관리</h5>
                                        <p class="text-muted mb-3">지급품 분류 체계 관리</p>
                                        <a href="/supply/categories" class="btn btn-sm btn-primary">
                                            <i class="ri-folder-line me-1"></i> 바로가기
                                        </a>
                                    </div>
                                    <div class="flex-shrink-0">
                                        <div class="avatar-sm">
                                            <span class="avatar-title bg-primary-subtle rounded fs-3">
                                                <i class="ri-folder-line text-primary"></i>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 연간 계획 -->
                    <div class="col-xl-3 col-md-6">
                        <div class="card card-animate border">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1">
                                        <h5 class="fs-15 mb-3">연간 계획</h5>
                                        <p class="text-muted mb-3">연도별 지급품 계획 수립</p>
                                        <a href="/supply/plans" class="btn btn-sm btn-success">
                                            <i class="ri-calendar-line me-1"></i> 바로가기
                                        </a>
                                    </div>
                                    <div class="flex-shrink-0">
                                        <div class="avatar-sm">
                                            <span class="avatar-title bg-success-subtle rounded fs-3">
                                                <i class="ri-calendar-line text-success"></i>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 구매 관리 -->
                    <div class="col-xl-3 col-md-6">
                        <div class="card card-animate border">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1">
                                        <h5 class="fs-15 mb-3">구매 관리</h5>
                                        <p class="text-muted mb-3">지급품 구매 및 입고 관리</p>
                                        <a href="/supply/purchases" class="btn btn-sm btn-info">
                                            <i class="ri-shopping-bag-line me-1"></i> 바로가기
                                        </a>
                                    </div>
                                    <div class="flex-shrink-0">
                                        <div class="avatar-sm">
                                            <span class="avatar-title bg-info-subtle rounded fs-3">
                                                <i class="ri-shopping-bag-line text-info"></i>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 지급 관리 -->
                    <div class="col-xl-3 col-md-6">
                        <div class="card card-animate border">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1">
                                        <h5 class="fs-15 mb-3">지급 관리</h5>
                                        <p class="text-muted mb-3">직원별 지급품 지급 관리</p>
                                        <a href="/supply/distributions" class="btn btn-sm btn-warning">
                                            <i class="ri-gift-line me-1"></i> 바로가기
                                        </a>
                                    </div>
                                    <div class="flex-shrink-0">
                                        <div class="avatar-sm">
                                            <span class="avatar-title bg-warning-subtle rounded fs-3">
                                                <i class="ri-gift-line text-warning"></i>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <h5 class="card-title mb-4 mt-4">보고서</h5>
                <div class="row g-4">
                    <!-- 재고 현황 -->
                    <div class="col-xl-3 col-md-6">
                        <div class="card card-animate border">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1">
                                        <h5 class="fs-15 mb-3">재고 현황</h5>
                                        <p class="text-muted mb-3">품목별 재고 현황 조회</p>
                                        <a href="/supply/reports/stock" class="btn btn-sm btn-outline-primary">
                                            <i class="ri-bar-chart-line me-1"></i> 보기
                                        </a>
                                    </div>
                                    <div class="flex-shrink-0">
                                        <div class="avatar-sm">
                                            <span class="avatar-title bg-light rounded fs-3">
                                                <i class="ri-bar-chart-line text-primary"></i>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 지급 현황 -->
                    <div class="col-xl-3 col-md-6">
                        <div class="card card-animate border">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1">
                                        <h5 class="fs-15 mb-3">지급 현황</h5>
                                        <p class="text-muted mb-3">연도별 지급 현황 조회</p>
                                        <a href="/supply/reports/distribution" class="btn btn-sm btn-outline-success">
                                            <i class="ri-line-chart-line me-1"></i> 보기
                                        </a>
                                    </div>
                                    <div class="flex-shrink-0">
                                        <div class="avatar-sm">
                                            <span class="avatar-title bg-light rounded fs-3">
                                                <i class="ri-line-chart-line text-success"></i>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 예산 집행률 -->
                    <div class="col-xl-3 col-md-6">
                        <div class="card card-animate border">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1">
                                        <h5 class="fs-15 mb-3">예산 집행률</h5>
                                        <p class="text-muted mb-3">연도별 예산 집행 현황</p>
                                        <a href="/supply/reports/budget" class="btn btn-sm btn-outline-info">
                                            <i class="ri-pie-chart-line me-1"></i> 보기
                                        </a>
                                    </div>
                                    <div class="flex-shrink-0">
                                        <div class="avatar-sm">
                                            <span class="avatar-title bg-light rounded fs-3">
                                                <i class="ri-pie-chart-line text-info"></i>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 부서별 사용 현황 -->
                    <div class="col-xl-3 col-md-6">
                        <div class="card card-animate border">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1">
                                        <h5 class="fs-15 mb-3">부서별 사용</h5>
                                        <p class="text-muted mb-3">부서별 사용 현황 조회</p>
                                        <a href="/supply/reports/department" class="btn btn-sm btn-outline-warning">
                                            <i class="ri-building-line me-1"></i> 보기
                                        </a>
                                    </div>
                                    <div class="flex-shrink-0">
                                        <div class="avatar-sm">
                                            <span class="avatar-title bg-light rounded fs-3">
                                                <i class="ri-building-line text-warning"></i>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php \App\Core\View::getInstance()->endSection(); ?>
