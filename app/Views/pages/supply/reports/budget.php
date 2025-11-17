<?php \App\Core\View::getInstance()->startSection('content'); ?>
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0"><?= e($pageTitle) ?></h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="/">홈</a></li>
                    <li class="breadcrumb-item"><a href="/supply">지급품 관리</a></li>
                    <li class="breadcrumb-item active">예산 집행률 보고서</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <!-- Year Selection and Summary -->
        <div class="card">
            <div class="card-header">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <div class="d-flex align-items-center">
                            <h5 class="card-title mb-0 me-3">연도 선택</h5>
                            <select class="form-select" id="year-selector" style="width: auto;">
                                <!-- 연도 목록은 JS에서 동적으로 생성 -->
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6 text-end">
                        <button type="button" class="btn btn-success" id="export-report-btn">
                            <i class="ri-download-2-line align-bottom me-1"></i> 엑셀 다운로드
                        </button>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <!-- Budget Summary Cards -->
                <div class="row">
                    <div class="col-xl-3 col-md-6">
                        <div class="card card-animate">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1 overflow-hidden">
                                        <p class="text-uppercase fw-medium text-muted text-truncate mb-0">계획 예산</p>
                                        <div class="d-flex align-items-end justify-content-between">
                                            <div>
                                                <h4 class="fs-22 fw-semibold ff-secondary mb-4">
                                                    ₩<span class="counter-value" data-target="0">0</span>
                                                </h4>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex-shrink-0">
                                        <div class="avatar-sm flex-shrink-0">
                                            <span class="avatar-title bg-info-subtle rounded fs-3">
                                                <i class="bx bx-wallet text-info"></i>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <div class="card card-animate">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1 overflow-hidden">
                                        <p class="text-uppercase fw-medium text-muted text-truncate mb-0">집행 금액</p>
                                        <div class="d-flex align-items-end justify-content-between">
                                            <div>
                                                <h4 class="fs-22 fw-semibold ff-secondary mb-4">
                                                    ₩<span class="counter-value" data-target="0">0</span>
                                                </h4>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex-shrink-0">
                                        <div class="avatar-sm flex-shrink-0">
                                            <span class="avatar-title bg-success-subtle rounded fs-3">
                                                <i class="bx bx-check-circle text-success"></i>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <div class="card card-animate">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1 overflow-hidden">
                                        <p class="text-uppercase fw-medium text-muted text-truncate mb-0">집행률</p>
                                        <div class="d-flex align-items-end justify-content-between">
                                            <div>
                                                <h4 class="fs-22 fw-semibold ff-secondary mb-4">
                                                    <span class="counter-value" data-target="0">0</span>%
                                                </h4>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex-shrink-0">
                                        <div class="avatar-sm flex-shrink-0">
                                            <span class="avatar-title bg-warning-subtle rounded fs-3">
                                                <i class="bx bx-bar-chart text-warning"></i>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <div class="card card-animate">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1 overflow-hidden">
                                        <p class="text-uppercase fw-medium text-muted text-truncate mb-0">잔여 예산</p>
                                        <div class="d-flex align-items-end justify-content-between">
                                            <div>
                                                <h4 class="fs-22 fw-semibold ff-secondary mb-4">
                                                    ₩<span class="counter-value" data-target="0">0</span>
                                                </h4>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex-shrink-0">
                                        <div class="avatar-sm flex-shrink-0">
                                            <span class="avatar-title bg-primary-subtle rounded fs-3">
                                                <i class="bx bx-money text-primary"></i>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Budget Execution Chart -->
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">예산 집행률 차트</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="budget-execution-chart" height="80"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <!-- Budget Execution Table -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">품목별 예산 집행 현황 <span id="item-count">(총 0개 품목)</span></h5>
            </div>
            <div class="card-body">
                <div id="loading-container" class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">로딩 중...</span>
                    </div>
                </div>

                <div id="no-data-container" class="text-center py-5" style="display: none;">
                    <i class="ri-file-list-3-line fs-1 text-muted"></i>
                    <p class="mt-3 text-muted"><span id="current-year-display"></span>년도에 등록된 계획이 없습니다.</p>
                </div>

                <div id="budget-table-container" style="display: none;">
                    <div class="table-responsive">
                        <table class="table table-nowrap table-striped-columns mb-0" id="budget-report-table">
                            <thead class="table-light">
                                <tr>
                                    <th scope="col">품목코드</th>
                                    <th scope="col">품목명</th>
                                    <th scope="col">분류</th>
                                    <th scope="col">계획수량</th>
                                    <th scope="col">단가</th>
                                    <th scope="col">계획예산</th>
                                    <th scope="col">구매수량</th>
                                    <th scope="col">구매금액</th>
                                    <th scope="col">지급수량</th>
                                    <th scope="col">집행률</th>
                                </tr>
                            </thead>
                            <tbody id="budget-table-body">
                                <!-- 데이터는 JS에서 동적으로 생성 -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if (isset($error)): ?>
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <i class="ri-error-warning-line me-2"></i>
    <?= e($error) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php endif; ?>

<?php \App\Core\View::getInstance()->endSection(); ?>