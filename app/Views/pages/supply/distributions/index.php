<?php \App\Core\View::getInstance()->startSection('content'); ?>
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0"><?= e($pageTitle ?? '지급품 지급 관리') ?></h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="/">홈</a></li>
                    <li class="breadcrumb-item"><a href="/supply">지급품 관리</a></li>
                    <li class="breadcrumb-item active">지급 관리</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <!-- Statistics Cards -->
        <div class="card">
            <div class="card-body">
                <div class="row" id="stats-container">
                    <!-- Stats will be loaded here by JS -->
                     <div class="col-xl-3 col-md-6">
                        <div class="card card-animate">
                            <div class="card-body"><div class="d-flex align-items-center"><div class="flex-grow-1"><p class="text-uppercase fw-medium text-muted mb-0">총 지급 건수</p></div><div class="flex-shrink-0"><span class="avatar-title bg-success-subtle rounded fs-3"><i class="bx bx-package text-success"></i></span></div></div><div class="d-flex align-items-end justify-content-between mt-4"><div><h4 class="fs-22 fw-semibold ff-secondary mb-0"><span class="counter-value">...</span>건</h4></div></div></div>
                        </div>
                    </div>
                     <div class="col-xl-3 col-md-6">
                        <div class="card card-animate">
                            <div class="card-body"><div class="d-flex align-items-center"><div class="flex-grow-1"><p class="text-uppercase fw-medium text-muted mb-0">총 지급 수량</p></div><div class="flex-shrink-0"><span class="avatar-title bg-info-subtle rounded fs-3"><i class="bx bx-cube text-info"></i></span></div></div><div class="d-flex align-items-end justify-content-between mt-4"><div><h4 class="fs-22 fw-semibold ff-secondary mb-0"><span class="counter-value">...</span>개</h4></div></div></div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <div class="card card-animate">
                            <div class="card-body"><div class="d-flex align-items-center"><div class="flex-grow-1"><p class="text-uppercase fw-medium text-muted mb-0">지급 직원 수</p></div><div class="flex-shrink-0"><span class="avatar-title bg-warning-subtle rounded fs-3"><i class="bx bx-user text-warning"></i></span></div></div><div class="d-flex align-items-end justify-content-between mt-4"><div><h4 class="fs-22 fw-semibold ff-secondary mb-0"><span class="counter-value">...</span>명</h4></div></div></div>
                        </div>
                    </div>
                   <div class="col-xl-3 col-md-6">
                        <div class="card card-animate">
                            <div class="card-body"><div class="d-flex align-items-center"><div class="flex-grow-1"><p class="text-uppercase fw-medium text-muted mb-0">지급 부서 수</p></div><div class="flex-shrink-0"><span class="avatar-title bg-primary-subtle rounded fs-3"><i class="bx bx-buildings text-primary"></i></span></div></div><div class="d-flex align-items-end justify-content-between mt-4"><div><h4 class="fs-22 fw-semibold ff-secondary mb-0"><span class="counter-value">...</span>개</h4></div></div></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <!-- Distributions Table -->
        <div class="card">
            <div class="card-header">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <h5 class="card-title mb-0">지급 목록</h5>
                    </div>
                    <div class="col-md-6 text-end">
                        <a href="/supply/distributions/create" class="btn btn-success">
                            <i class="ri-add-line align-bottom me-1"></i> 지급 등록
                        </a>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-nowrap table-striped-columns mb-0" id="distributions-table" style="width:100%">
                        <thead class="table-light">
                            <tr>
                                <th scope="col">지급일</th>
                                <th scope="col">품목</th>
                                <th scope="col">수량</th>
                                <th scope="col">직원</th>
                                <th scope="col">부서</th>
                                <th scope="col">상태</th>
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

<!-- Cancel Distribution Modal -->
<div class="modal fade" id="cancelDistributionModal" tabindex="-1" aria-labelledby="cancelDistributionModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="cancelDistributionModalLabel">지급 취소</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="cancel-distribution-info"></div>
                <div class="mb-3">
                    <label for="cancel-reason" class="form-label">취소 사유 <span class="text-danger">*</span></label>
                    <textarea class="form-control" id="cancel-reason" rows="3" placeholder="취소 사유를 입력하세요" required></textarea>
                </div>
                <div class="alert alert-warning" role="alert">
                    <i class="ri-alert-line me-2"></i>
                    지급을 취소하면 재고가 복원됩니다.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">닫기</button>
                <button type="button" class="btn btn-danger" id="confirm-cancel-distribution-btn">취소 처리</button>
            </div>
        </div>
    </div>
</div>
<?php \App\Core\View::getInstance()->endSection(); ?>
