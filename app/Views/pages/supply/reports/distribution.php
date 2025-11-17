<?php \App\Core\View::getInstance()->startSection('content'); ?>
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0"><?= e($pageTitle) ?></h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="/">홈</a></li>
                    <li class="breadcrumb-item"><a href="/supply">지급품 관리</a></li>
                    <li class="breadcrumb-item active">지급 현황 보고서</li>
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
                        <div class="col-md-2">
                            <label class="form-label">연도</label>
                            <select class="form-select" name="year" id="year-filter">
                                <option value="<?= $currentYear ?>" selected><?= $currentYear ?>년</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">분류</label>
                            <select class="form-select" name="category_id" id="category-filter">
                                <option value="">전체</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">부서</label>
                            <select class="form-select" name="department_id" id="department-filter">
                                <option value="">전체</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">상태</label>
                            <select class="form-select" name="is_cancelled" id="status-filter">
                                <option value="">전체</option>
                                <option value="0">정상</option>
                                <option value="1">취소</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="ri-search-line me-1"></i> 조회
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <!-- Distribution Report Table -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">지급 현황 목록 <span id="total-count">(총 0건)</span></h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-nowrap table-striped-columns mb-0" id="distribution-report-table">
                        <thead class="table-light">
                            <tr>
                                <th scope="col">지급일자</th>
                                <th scope="col">품목코드</th>
                                <th scope="col">품목명</th>
                                <th scope="col">분류</th>
                                <th scope="col">부서</th>
                                <th scope="col">직원</th>
                                <th scope="col">수량</th>
                                <th scope="col">지급자</th>
                                <th scope="col">상태</th>
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

<?php if (isset($error)): ?>
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <i class="ri-error-warning-line me-2"></i>
    <?= e($error) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php endif; ?>

<?php \App\Core\View::getInstance()->endSection(); ?>