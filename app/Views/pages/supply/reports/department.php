<?php \App\Core\View::getInstance()->startSection('content'); ?>
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0"><?= e($pageTitle) ?></h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="/">홈</a></li>
                    <li class="breadcrumb-item"><a href="/supply">지급품 관리</a></li>
                    <li class="breadcrumb-item active">부서별 사용 현황</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <!-- Filters -->
        <div class="card">
            <div class="card-header">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <h5 class="card-title mb-0">필터 및 검색</h5>
                    </div>
                    <div class="col-md-6 text-end">
                        <button type="button" class="btn btn-success" id="export-report-btn">
                            <i class="ri-download-2-line align-bottom me-1"></i> 엑셀 다운로드
                        </button>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <form id="filter-form">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">연도</label>
                            <select class="form-select" name="year" id="year-filter">
                                <option value="<?= $currentYear ?>" selected><?= $currentYear ?>년</option>
                            </select>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label">부서</label>
                            <select class="form-select" name="department_id" id="department-filter">
                                <option value="">전체</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="ri-search-line me-1"></i> 조회
                            </button>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <button type="button" class="btn btn-secondary w-100" id="reset-filter-btn">
                                <i class="ri-refresh-line me-1"></i> 초기화
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Department Summary -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">부서별 사용 현황 요약 <span id="total-count">(총 0개 부서)</span></h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-nowrap table-striped-columns mb-0" id="department-summary-table">
                        <thead class="table-light">
                            <tr>
                                <th scope="col">부서명</th>
                                <th scope="col">품목 종류</th>
                                <th scope="col">지급 횟수</th>
                                <th scope="col">총 지급 수량</th>
                                <th scope="col">직원 수</th>
                                <th scope="col">작업</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- DataTables will populate this -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Department Usage Chart -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">부서별 지급 수량 비교</h5>
            </div>
            <div class="card-body">
                <canvas id="department-usage-chart" height="80"></canvas>
            </div>
        </div>
    </div>
</div>

<?php \App\Core\View::getInstance()->endSection(); ?>
