<?php \App\Core\View::getInstance()->startSection('content'); ?>
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">지급품 보고서</h5>
            </div>
            <div class="card-body">
                <div class="row g-4">
                    <!-- 지급 현황 보고서 -->
                    <div class="col-md-6">
                        <div class="card border">
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="flex-shrink-0">
                                        <div class="avatar-sm">
                                            <div class="avatar-title bg-soft-primary text-primary rounded fs-3">
                                                <i class="ri-file-list-3-line"></i>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h5 class="card-title mb-1">지급 현황 보고서</h5>
                                        <p class="text-muted mb-0">품목별 지급 현황 및 통계</p>
                                    </div>
                                </div>
                                <a href="/supply/reports/distribution" class="btn btn-primary w-100">
                                    <i class="ri-arrow-right-line align-bottom me-1"></i> 보고서 보기
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- 재고 현황 보고서 -->
                    <div class="col-md-6">
                        <div class="card border">
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="flex-shrink-0">
                                        <div class="avatar-sm">
                                            <div class="avatar-title bg-soft-success text-success rounded fs-3">
                                                <i class="ri-stack-line"></i>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h5 class="card-title mb-1">재고 현황 보고서</h5>
                                        <p class="text-muted mb-0">품목별 재고 현황 및 분석</p>
                                    </div>
                                </div>
                                <a href="/supply/reports/stock" class="btn btn-success w-100">
                                    <i class="ri-arrow-right-line align-bottom me-1"></i> 보고서 보기
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- 예산 집행률 보고서 -->
                    <div class="col-md-6">
                        <div class="card border">
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="flex-shrink-0">
                                        <div class="avatar-sm">
                                            <div class="avatar-title bg-soft-warning text-warning rounded fs-3">
                                                <i class="ri-pie-chart-line"></i>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h5 class="card-title mb-1">예산 집행률 보고서</h5>
                                        <p class="text-muted mb-0">연간 예산 대비 집행 현황</p>
                                    </div>
                                </div>
                                <a href="/supply/reports/budget" class="btn btn-warning w-100">
                                    <i class="ri-arrow-right-line align-bottom me-1"></i> 보고서 보기
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- 부서별 사용 현황 보고서 -->
                    <div class="col-md-6">
                        <div class="card border">
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="flex-shrink-0">
                                        <div class="avatar-sm">
                                            <div class="avatar-title bg-soft-info text-info rounded fs-3">
                                                <i class="ri-building-line"></i>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h5 class="card-title mb-1">부서별 사용 현황</h5>
                                        <p class="text-muted mb-0">부서별 지급품 사용 통계</p>
                                    </div>
                                </div>
                                <a href="/supply/reports/department" class="btn btn-info w-100">
                                    <i class="ri-arrow-right-line align-bottom me-1"></i> 보고서 보기
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php \App\Core\View::getInstance()->endSection(); ?>
