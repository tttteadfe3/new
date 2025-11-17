<?php \App\Core\View::getInstance()->startSection('content'); ?>
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0"><?= e($pageTitle) ?></h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="/">홈</a></li>
                    <li class="breadcrumb-item"><a href="/supply">지급품 관리</a></li>
                    <li class="breadcrumb-item"><a href="/supply/distributions">지급 관리</a></li>
                    <li class="breadcrumb-item active">지급 수정</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">지급 정보 수정</h5>
            </div>
            <div class="card-body">
                <div id="loading-container" class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">로딩 중...</span>
                    </div>
                </div>

                <form id="distribution-edit-form" style="display: none;">
                    <input type="hidden" id="distribution-id" value="<?= $distributionId ?>">

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">지급 품목</label>
                                <input type="text" class="form-control" id="item-name" readonly>
                                <div class="form-text">품목은 수정할 수 없습니다.</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="quantity" class="form-label">지급 수량 <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" class="form-control" id="quantity" name="quantity" min="1" required>
                                    <span class="input-group-text" id="unit-display">개</span>
                                </div>
                                <div class="form-text" id="stock-info">
                                    로딩 중...
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">부서</label>
                                <input type="text" class="form-control" id="department-name" readonly>
                                <div class="form-text">부서는 수정할 수 없습니다.</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">직원</label>
                                <input type="text" class="form-control" id="employee-name" readonly>
                                <div class="form-text">직원은 수정할 수 없습니다.</div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="distribution-date" class="form-label">지급일 <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="distribution-date" name="distribution_date" 
                                       max="<?= date('Y-m-d') ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="notes" class="form-label">비고</label>
                                <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <div class="alert alert-warning" role="alert">
                                <i class="ri-alert-line me-2"></i>
                                <strong>주의:</strong> 수량을 변경하면 재고가 자동으로 조정됩니다.
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
                                    <i class="ri-save-line me-1"></i> 수정 저장
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
