<?php \App\Core\View::getInstance()->startSection('content'); ?>
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">지급품 계획 관리</h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="/dashboard">대시보드</a></li>
                    <li class="breadcrumb-item">지급품 관리</li>
                    <li class="breadcrumb-item active">계획 관리</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <div class="d-flex align-items-center">
                    <h5 class="card-title mb-0 flex-grow-1">연간 지급 계획</h5>
                    <div class="d-flex gap-2">
                        <select class="form-select" id="year-filter" style="width: 120px;">
                            <!-- JS로 연도 옵션 생성 -->
                        </select>
                        <button type="button" class="btn btn-primary" id="add-plan-btn" data-bs-toggle="modal" data-bs-target="#plan-modal">
                            <i class="ri-add-line align-bottom me-1"></i> 신규 계획 등록
                        </button>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>분류</th>
                                <th>품목명</th>
                                <th>단가</th>
                                <th>예정 수량</th>
                                <th>예산 금액</th>
                                <th>비고</th>
                                <th>등록자</th>
                                <th>작업</th>
                            </tr>
                        </thead>
                        <tbody id="plans-table-body">
                            <!-- JS로 데이터 렌더링 -->
                        </tbody>
                    </table>
                </div>
                 <div id="table-placeholder" class="text-center p-5">
                    <p class="text-muted">선택한 연도의 계획을 불러오는 중입니다...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Plan Modal -->
<div class="modal fade" id="plan-modal" tabindex="-1" aria-labelledby="plan-modal-label" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="plan-form">
                <div class="modal-header">
                    <h5 class="modal-title" id="plan-modal-label">지급 계획 등록/수정</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="plan-id" name="id">

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="plan-year" class="form-label">계획 연도</label>
                            <input type="number" class="form-control" id="plan-year" name="year" required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="plan-category" class="form-label">분류</label>
                            <select class="form-select" id="plan-category" name="category_id" required>
                                <option value="">대분류 선택</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="plan-item" class="form-label">품목</label>
                            <select class="form-select" id="plan-item" name="item_id" required>
                                <option value="">분류를 먼저 선택하세요</option>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                         <div class="col-md-6 mb-3">
                            <label for="plan-unit-price" class="form-label">단가</label>
                            <input type="number" class="form-control" id="plan-unit-price" name="unit_price" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="plan-quantity" class="form-label">예정 수량</label>
                            <input type="number" class="form-control" id="plan-quantity" name="quantity" required>
                        </div>
                    </div>

                    <div class="mb-3">
                         <label for="plan-budget" class="form-label">예산 금액</label>
                         <input type="text" class="form-control" id="plan-budget" disabled readonly>
                    </div>

                    <div class="mb-3">
                        <label for="plan-note" class="form-label">비고</label>
                        <textarea class="form-control" id="plan-note" name="note" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">닫기</button>
                    <button type="submit" class="btn btn-primary">저장</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php \App\Core\View::getInstance()->endSection(); ?>
