<?php \App\Core\View::getInstance()->startSection('content'); ?>
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0"><?= e($pageTitle) ?></h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="/">홈</a></li>
                    <li class="breadcrumb-item"><a href="/supply">지급품 관리</a></li>
                    <li class="breadcrumb-item"><a href="/supply/items">품목 관리</a></li>
                    <li class="breadcrumb-item active">품목 수정</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-8 offset-lg-2">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">품목 정보 수정</h5>
            </div>
            <div class="card-body">
                <form id="item-form">
                    <input type="hidden" id="item-id" value="<?= e($itemId) ?>">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="category-id" class="form-label">분류 <span class="text-danger">*</span></label>
                                <select class="form-select" id="category-id" name="category_id" required>
                                    <option value="">분류를 선택하세요</option>
                                </select>
                                <div class="invalid-feedback">분류를 선택해주세요.</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="item-code" class="form-label">품목 코드 <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="item-code" name="item_code" required maxlength="30">
                                <div class="invalid-feedback">품목 코드를 입력해주세요.</div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="item-name" class="form-label">품목명 <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="item-name" name="item_name" required maxlength="200">
                        <div class="invalid-feedback">품목명을 입력해주세요.</div>
                    </div>

                    <div class="mb-3">
                        <label for="unit" class="form-label">단위 <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="unit" name="unit" required maxlength="20" placeholder="예: 개, 박스, 세트">
                        <div class="invalid-feedback">단위를 입력해주세요.</div>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">설명</label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                    </div>

                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="is-active" name="is_active">
                            <label class="form-check-label" for="is-active">
                                활성 상태
                            </label>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="/supply/items" class="btn btn-secondary">
                            <i class="ri-close-line me-1"></i> 취소
                        </a>
                        <button type="submit" class="btn btn-primary" id="submit-btn">
                            <i class="ri-save-line me-1"></i> 저장
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php \App\Core\View::getInstance()->endSection(); ?>
