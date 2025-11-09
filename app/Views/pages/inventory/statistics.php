<?php \App\Core\View::getInstance()->startSection('content'); ?>
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">통계 및 현황</h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="/dashboard">대시보드</a></li>
                    <li class="breadcrumb-item">지급품 관리</li>
                    <li class="breadcrumb-item active">통계</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
             <div class="card-header">
                <div class="d-flex flex-wrap align-items-center">
                    <h5 class="card-title mb-0 flex-grow-1">연간 현황</h5>
                    <div class="d-flex gap-2 flex-wrap">
                        <select class="form-select" id="year-filter" style="width: 120px;"></select>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="statistics-content" style="visibility: hidden;">
    <div class="row">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">예산 대비 집행률</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="flex-grow-1">
                            <p class="text-muted mb-1">총 예산</p>
                            <h4 id="total-budget" class="mb-0">0 원</h4>
                        </div>
                        <div class="flex-shrink-0">
                            <p class="text-muted mb-1">총 집행</p>
                            <h4 id="total-executed" class="mb-0">0 원</h4>
                        </div>
                    </div>
                    <canvas id="budget-chart" height="300"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header"><h5 class="card-title mb-0">재고 현황 Top 10</h5></div>
                <div class="card-body">
                    <table class="table table-sm table-borderless">
                        <tbody id="stock-status-body"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6">
             <div class="card">
                <div class="card-header"><h5 class="card-title mb-0">품목별 지급 현황</h5></div>
                <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                    <table class="table table-sm table-striped">
                        <thead class="table-light"><tr><th>분류</th><th>품목명</th><th>총 지급수량</th></tr></thead>
                        <tbody id="item-give-stats-body"></tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
             <div class="card">
                <div class="card-header"><h5 class="card-title mb-0">부서별 지급 현황</h5></div>
                <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                    <div id="department-give-stats-accordion"></div>
                </div>
            </div>
        </div>
    </div>
</div>
<div id="loading-placeholder" class="text-center p-5">
    <p class="text-muted">데이터를 불러오는 중입니다...</p>
</div>

<?php \App\Core\View::getInstance()->endSection(); ?>
