<?php \App\Core\View::getInstance()->startSection('content'); ?>
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0"><?= e($pageTitle ?? '연간 지급품 계획') ?></h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="/">홈</a></li>
                    <li class="breadcrumb-item"><a href="/supply">지급품 관리</a></li>
                    <li class="breadcrumb-item active">연간 계획</li>
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
                                <!-- Options will be populated by JS -->
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6 text-end">
                        <div class="btn-group" role="group">
                            <a href="/supply/plans/create?year=<?= e($currentYear) ?>" class="btn btn-success">
                                <i class="ri-add-line align-bottom me-1"></i> 신규 계획
                            </a>
                            <a href="/supply/plans/import?year=<?= e($currentYear) ?>" class="btn btn-info">
                                <i class="ri-upload-2-line align-bottom me-1"></i> 엑셀 업로드
                            </a>
                            <button type="button" class="btn btn-primary" id="export-excel-btn">
                                <i class="ri-download-2-line align-bottom me-1"></i> 엑셀 다운로드
                            </button>
                            <a href="/supply/plans/budget-summary?year=<?= e($currentYear) ?>" class="btn btn-outline-primary">
                                <i class="ri-bar-chart-line align-bottom me-1"></i> 예산 요약
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <!-- Budget Summary Cards -->
                <div class="row" id="budget-summary-container">
                    <!-- Summary cards will be populated by JS -->
                    <div class="col-xl-3 col-md-6"><div class="card card-animate"><div class="card-body"><div class="d-flex align-items-center"><div class="flex-grow-1"><p class="text-uppercase fw-medium text-muted mb-0">총 계획 품목</p></div><div class="flex-shrink-0"><span class="avatar-title bg-success-subtle rounded fs-3"><i class="bx bx-package text-success"></i></span></div></div><div class="d-flex align-items-end justify-content-between mt-4"><div><h4 class="fs-22 fw-semibold ff-secondary mb-0"><span class="counter-value">...</span>개</h4></div></div></div></div></div>
                    <div class="col-xl-3 col-md-6"><div class="card card-animate"><div class="card-body"><div class="d-flex align-items-center"><div class="flex-grow-1"><p class="text-uppercase fw-medium text-muted mb-0">총 계획 수량</p></div><div class="flex-shrink-0"><span class="avatar-title bg-info-subtle rounded fs-3"><i class="bx bx-cube text-info"></i></span></div></div><div class="d-flex align-items-end justify-content-between mt-4"><div><h4 class="fs-22 fw-semibold ff-secondary mb-0"><span class="counter-value">...</span>개</h4></div></div></div></div></div>
                    <div class="col-xl-3 col-md-6"><div class="card card-animate"><div class="card-body"><div class="d-flex align-items-center"><div class="flex-grow-1"><p class="text-uppercase fw-medium text-muted mb-0">총 예산</p></div><div class="flex-shrink-0"><span class="avatar-title bg-warning-subtle rounded fs-3"><i class="bx bx-won text-warning"></i></span></div></div><div class="d-flex align-items-end justify-content-between mt-4"><div><h4 class="fs-22 fw-semibold ff-secondary mb-0">₩<span class="counter-value">...</span></h4></div></div></div></div></div>
                    <div class="col-xl-3 col-md-6"><div class="card card-animate"><div class="card-body"><div class="d-flex align-items-center"><div class="flex-grow-1"><p class="text-uppercase fw-medium text-muted mb-0">평균 단가</p></div><div class="flex-shrink-0"><span class="avatar-title bg-primary-subtle rounded fs-3"><i class="bx bx-calculator text-primary"></i></span></div></div><div class="d-flex align-items-end justify-content-between mt-4"><div><h4 class="fs-22 fw-semibold ff-secondary mb-0">₩<span class="counter-value">...</span></h4></div></div></div></div></div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <!-- Plans Table -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0" id="plans-table-title"><?= e($currentYear) ?>년 지급품 계획 목록</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-nowrap table-striped-columns mb-0" id="plans-table" style="width:100%">
                        <thead class="table-light">
                            <tr>
                                <th scope="col">품목코드</th>
                                <th scope="col">품목명</th>
                                <th scope="col">분류</th>
                                <th scope="col">단위</th>
                                <th scope="col">계획수량</th>
                                <th scope="col">단가</th>
                                <th scope="col">총예산</th>
                                <th scope="col">등록일</th>
                                <th scope="col">작업</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Data will be populated by DataTables -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deletePlanModal" tabindex="-1" aria-labelledby="deletePlanModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deletePlanModalLabel">계획 삭제 확인</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>정말로 이 계획을 삭제하시겠습니까?</p>
                <p class="text-danger small">
                    <i class="ri-alert-line me-1"></i>
                    이미 구매나 지급 기록이 있는 계획은 삭제할 수 없습니다.
                </p>
                <div id="delete-plan-info"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-secondary" data-bs-dismiss="modal">취소</button>
                <button type="button" class="btn btn-danger" id="confirm-delete-plan-btn">삭제</button>
            </div>
        </div>
    </div>
</div>
<?php \App\Core\View::getInstance()->endSection(); ?>
