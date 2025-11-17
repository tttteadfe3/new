<?php \App\Core\View::getInstance()->startSection('content'); ?>
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">지급품 분류 생성</h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="/supply/categories">지급품 분류</a></li>
                    <li class="breadcrumb-item active">생성</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">분류 정보</h5>
            </div>
            <div class="card-body">
                <form id="createCategoryForm">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="category-level" class="form-label">분류 레벨 <span class="text-danger">*</span></label>
                            <select class="form-select" id="category-level" name="level" required>
                                <option value="">선택하세요</option>
                                <option value="1">대분류</option>
                                <option value="2">소분류</option>
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6" id="parent-category-container" style="display: none;">
                            <label for="parent-category" class="form-label">상위 분류 <span class="text-danger">*</span></label>
                            <select class="form-select" id="parent-category" name="parent_id">
                                <option value="">선택하세요</option>
                                <?php if (!empty($mainCategories)): ?>
                                    <?php foreach ($mainCategories as $category): ?>
                                        <option value="<?= $category->getAttribute('id') ?>">
                                            <?= e($category->getAttribute('category_name')) ?> (<?= e($category->getAttribute('category_code')) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-8">
                            <label for="category-code" class="form-label">분류 코드 <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="category-code" name="category_code" required maxlength="20" placeholder="예: MC001, MC001SC001">
                            <div class="form-text">분류 코드는 고유해야 하며, 생성 후 수정할 수 없습니다.</div>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">&nbsp;</label>
                            <button type="button" class="btn btn-outline-primary w-100" id="generate-code-btn">
                                <i class="ri-refresh-line me-1"></i> 자동 생성
                            </button>
                        </div>
                        <div class="col-12">
                            <label for="category-name" class="form-label">분류명 <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="category-name" name="category_name" required maxlength="100" placeholder="분류명을 입력하세요">
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6">
                            <label for="display-order" class="form-label">표시 순서</label>
                            <input type="number" class="form-control" id="display-order" name="display_order" value="0" min="0">
                            <div class="form-text">숫자가 작을수록 먼저 표시됩니다.</div>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6">
                            <label for="is-active" class="form-label">상태</label>
                            <select class="form-select" id="is-active" name="is_active">
                                <option value="1" selected>활성</option>
                                <option value="0">비활성</option>
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary me-2" id="save-btn">
                            <i class="ri-save-line me-1"></i> 저장
                        </button>
                        <a href="/supply/categories" class="btn btn-secondary">
                            <i class="ri-arrow-left-line me-1"></i> 목록으로
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">도움말</h5>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <h6 class="alert-heading">분류 생성 가이드</h6>
                    <ul class="mb-0 small">
                        <li><strong>대분류:</strong> 최상위 분류로 상위 분류가 없습니다.</li>
                        <li><strong>소분류:</strong> 대분류 하위에 속하는 분류입니다.</li>
                        <li><strong>분류 코드:</strong> 시스템에서 자동 생성하거나 직접 입력할 수 있습니다.</li>
                        <li><strong>표시 순서:</strong> 목록에서 표시되는 순서를 결정합니다.</li>
                    </ul>
                </div>
                
                <div class="alert alert-warning">
                    <h6 class="alert-heading">주의사항</h6>
                    <ul class="mb-0 small">
                        <li>분류 코드는 생성 후 수정할 수 없습니다.</li>
                        <li>소분류는 반드시 상위 분류를 선택해야 합니다.</li>
                        <li>비활성 상태의 분류는 품목 등록 시 선택할 수 없습니다.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
<?php \App\Core\View::getInstance()->endSection(); ?>