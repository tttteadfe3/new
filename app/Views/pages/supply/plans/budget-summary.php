<?php \App\Core\View::getInstance()->startSection('content'); ?>
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0"><?= e($pageTitle) ?></h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="/">홈</a></li>
                    <li class="breadcrumb-item"><a href="/supply">지급품 관리</a></li>
                    <li class="breadcrumb-item"><a href="/supply/plans">연간 계획</a></li>
                    <li class="breadcrumb-item active">예산 요약</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <!-- Year Selection -->
        <div class="card">
            <div class="card-header">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <div class="d-flex align-items-center">
                            <h5 class="card-title mb-0 me-3">연도 선택</h5>
                            <select class="form-select" id="year-selector" style="width: auto;">
                                <!-- Options will be populated by JavaScript -->
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6 text-end">
                        <div class="btn-group" role="group">
                            <a href="/supply/plans?year=<?= $currentYear ?>" class="btn btn-outline-primary">
                                <i class="ri-list-check-2 me-1"></i> 계획 목록
                            </a>
                            <button type="button" class="btn btn-primary" id="export-summary-btn">
                                <i class="ri-download-2-line me-1"></i> 요약 다운로드
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row" id="summary-cards">
    <!-- Summary cards will be populated by JavaScript -->
</div>

<div class="row">
    <div class="col-xl-8">
        <!-- 분류별 예산 차트 -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">분류별 예산 분포</h5>
            </div>
            <div class="card-body" id="category-budget-chart-container">
                <canvas id="categoryBudgetChart" height="300"></canvas>
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <!-- 분류별 상세 정보 -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">분류별 상세</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>분류</th>
                                <th class="text-end">품목수</th>
                                <th class="text-end">예산</th>
                            </tr>
                        </thead>
                        <tbody id="category-details-tbody">
                            <!-- Data will be populated by JavaScript -->
                        </tbody>
                        <tfoot class="table-light">
                            <tr id="category-details-tfoot">
                                <!-- Footer will be populated by JavaScript -->
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row" id="comparison-section" style="display: none;">
    <div class="col-12">
        <!-- 전년 대비 비교 -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <span id="comparison-current-year"><?= $currentYear ?></span>년 vs <span id="comparison-previous-year"><?= $currentYear - 1 ?></span>년 비교
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>구분</th>
                                <th class="text-center"><span class="comparison-previous-year-text"><?= $currentYear - 1 ?></span>년</th>
                                <th class="text-center"><span class="comparison-current-year-text"><?= $currentYear ?></span>년</th>
                                <th class="text-center">증감</th>
                                <th class="text-center">증감률</th>
                            </tr>
                        </thead>
                        <tbody id="comparison-tbody">
                            <!-- Comparison data will be populated by JavaScript -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php \App\Core\View::getInstance()->endSection(); ?>
