<?php \App\Core\View::getInstance()->startSection('content'); ?>
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0"><?= e($pageTitle ?? '지급품 지급 등록') ?></h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="/">홈</a></li>
                    <li class="breadcrumb-item"><a href="/supply">지급품 관리</a></li>
                    <li class="breadcrumb-item"><a href="/supply/distributions">지급 관리</a></li>
                    <li class="breadcrumb-item active">지급 등록</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">지급 정보 입력</h5>
            </div>
            <div class="card-body">
                <form id="distribution-form">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="item-id" class="form-label">지급 품목 <span class="text-danger">*</span></label>
                                <select class="form-select" id="item-id" name="item_id" required>
                                    <option value="">불러오는 중...</option>
                                </select>
                                <div class="form-text">재고가 있는 품목만 표시됩니다.</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="quantity" class="form-label">지급 수량 <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" class="form-control" id="quantity" name="quantity" min="1" required>
                                    <span class="input-group-text" id="unit-display">개</span>
                                </div>
                                <div class="form-text">
                                    <span id="stock-info">품목을 선택하면 재고 정보가 표시됩니다.</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="department-id" class="form-label">부서 <span class="text-danger">*</span></label>
                                <select class="form-select" id="department-id" name="department_id" required>
                                    <option value="">불러오는 중...</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="employee-id" class="form-label">직원 <span class="text-danger">*</span></label>
                                <select class="form-select" id="employee-id" name="employee_id" required disabled>
                                    <option value="">먼저 부서를 선택하세요</option>
                                </select>
                                <div class="form-text">부서를 선택하면 해당 부서의 직원 목록이 표시됩니다.</div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <div class="mb-3">
                                <label for="notes" class="form-label">비고</label>
                                <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="추가 정보를 입력하세요"></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="distribution_date" class="form-label">지급일 <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="distribution_date" name="distribution_date" required>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <div class="alert alert-info" role="alert">
                                <i class="ri-information-line me-2"></i>
                                <strong>안내:</strong> 지급 처리 시 재고가 자동으로 차감됩니다.
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <div class="text-end">
                                <a href="/supply/distributions" class="btn btn-secondary">
                                    <i class="ri-arrow-left-line me-1"></i> 취소
                                </a>
                                <button type="submit" class="btn btn-success" id="submit-btn">
                                    <i class="ri-save-line me-1"></i> 지급 등록
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php \App\Core\View::getInstance()->endSection(); ?>
