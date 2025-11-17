<?php \App\Core\View::getInstance()->startSection('content'); ?>
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0"><?= e($pageTitle) ?></h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="/">홈</a></li>
                    <li class="breadcrumb-item"><a href="/supply">지급품 관리</a></li>
                    <li class="breadcrumb-item"><a href="/supply/purchases">구매 관리</a></li>
                    <li class="breadcrumb-item active">구매 등록</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-8 offset-lg-2">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">구매 정보 입력</h5>
            </div>
            <div class="card-body">
                <form id="purchase-form">
                    <div class="mb-3">
                        <label for="item-id" class="form-label">품목 <span class="text-danger">*</span></label>
                        <select class="form-select" id="item-id" name="item_id" required>
                            <option value="">품목을 선택하세요</option>
                        </select>
                        <div class="invalid-feedback">품목을 선택해주세요.</div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="purchase-date" class="form-label">구매일 <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="purchase-date" name="purchase_date" 
                                       value="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d') ?>" required>
                                <div class="invalid-feedback">구매일을 입력해주세요.</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="supplier" class="form-label">공급업체</label>
                                <input type="text" class="form-control" id="supplier" name="supplier" 
                                       placeholder="공급업체명을 입력하세요" maxlength="200">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="quantity" class="form-label">수량 <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="quantity" name="quantity" 
                                       min="1" max="999999" required>
                                <div class="invalid-feedback">수량을 입력해주세요 (1-999,999).</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="unit-price" class="form-label">단가 (원) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="unit-price" name="unit_price" 
                                       min="0" step="0.01" max="9999999.99" required>
                                <div class="invalid-feedback">단가를 입력해주세요.</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="total-amount" class="form-label">총액 (원)</label>
                                <input type="text" class="form-control" id="total-amount" readonly>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="is-received" name="is_received">
                            <label class="form-check-label" for="is-received">
                                즉시 입고 처리
                            </label>
                        </div>
                        <small class="text-muted">체크하면 구매와 동시에 입고 처리되어 재고가 즉시 반영됩니다.</small>
                    </div>

                    <div class="mb-3" id="received-date-group" style="display: none;">
                        <label for="received-date" class="form-label">입고일</label>
                        <input type="date" class="form-control" id="received-date" name="received_date" 
                               value="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d') ?>">
                    </div>

                    <div class="mb-3">
                        <label for="notes" class="form-label">비고</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3" 
                                  placeholder="추가 정보를 입력하세요"></textarea>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="/supply/purchases" class="btn btn-secondary">
                            <i class="ri-close-line me-1"></i> 취소
                        </a>
                        <button type="submit" class="btn btn-success" id="submit-btn">
                            <i class="ri-save-line me-1"></i> 등록
                        </button>
                    </div>
                </form>
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
</content>
</file>
</invoke>