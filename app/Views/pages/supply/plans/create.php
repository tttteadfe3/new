<?php \App\Core\View::getInstance()->startSection('content'); ?>
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0"><?= e($pageTitle) ?></h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="/">홈</a></li>
                    <li class="breadcrumb-item"><a href="/supply">지급품 관리</a></li>
                    <li class="breadcrumb-item"><a href="/supply/plans">연간 계획</a></li>
                    <li class="breadcrumb-item active">신규 등록</li>
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
                    <h5 class="card-title mb-0 flex-grow-1"><?= $year ?>년 지급품 계획 등록</h5>
                    <a href="/supply/plans?year=<?= $year ?>" class="btn btn-secondary">
                        <i class="ri-arrow-left-line me-1"></i> 목록으로
                    </a>
                </div>
            </div>
            <div class="card-body">
                <?php if (empty($availableItems)): ?>
                    <div class="text-center py-5">
                        <i class="ri-information-line fs-1 text-muted"></i>
                        <p class="mt-3 text-muted"><?= $year ?>년도에 계획을 등록할 수 있는 품목이 없습니다.</p>
                        <p class="text-muted">모든 품목에 대한 계획이 이미 등록되었거나, 등록된 품목이 없습니다.</p>
                        <div class="mt-4">
                            <a href="/supply/items/create" class="btn btn-success me-2">
                                <i class="ri-add-line me-1"></i> 새 품목 등록
                            </a>
                            <a href="/supply/plans?year=<?= $year ?>" class="btn btn-outline-primary">
                                <i class="ri-arrow-left-line me-1"></i> 계획 목록으로
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <form id="planForm" class="needs-validation" novalidate>
                        <input type="hidden" name="year" value="<?= $year ?>">
                        
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="category-filter" class="form-label">분류 필터</label>
                                <select class="form-select" id="category-filter">
                                    <option value="">모든 분류</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?= $category->getAttribute('id') ?>">
                                            <?= e($category->getAttribute('category_name')) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="item-search" class="form-label">품목 검색</label>
                                <div class="search-box">
                                    <input type="text" class="form-control" id="item-search" placeholder="품목명 또는 코드 검색...">
                                    <i class="ri-search-line search-icon"></i>
                                </div>
                            </div>
                        </div>

                        <div class="row mt-4">
                            <div class="col-md-6">
                                <label for="item-id" class="form-label">품목 선택 <span class="text-danger">*</span></label>
                                <select class="form-select" id="item-id" name="item_id" required>
                                    <option value="">품목을 선택하세요</option>
                                    <?php foreach ($availableItems as $item): ?>
                                        <option value="<?= $item->getAttribute('id') ?>" 
                                                data-category="<?= $item->getAttribute('category_id') ?>"
                                                data-unit="<?= e($item->getAttribute('unit')) ?>"
                                                data-code="<?= e($item->getAttribute('item_code')) ?>">
                                            [<?= e($item->getAttribute('item_code')) ?>] <?= e($item->getAttribute('item_name')) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="invalid-feedback">
                                    품목을 선택해주세요.
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="item-unit" class="form-label">단위</label>
                                <input type="text" class="form-control" id="item-unit" readonly placeholder="품목 선택 시 자동 입력">
                            </div>
                        </div>

                        <div class="row mt-3">
                            <div class="col-md-4">
                                <label for="planned-quantity" class="form-label">계획 수량 <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="planned-quantity" name="planned_quantity" 
                                       required min="1" max="999999" placeholder="수량 입력">
                                <div class="invalid-feedback">
                                    1 이상 999,999 이하의 수량을 입력해주세요.
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label for="unit-price" class="form-label">단가 (원) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="unit-price" name="unit_price" 
                                       required min="0" max="9999999.99" step="0.01" placeholder="단가 입력">
                                <div class="invalid-feedback">
                                    0 이상 9,999,999.99 이하의 단가를 입력해주세요.
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label for="total-budget" class="form-label">총 예산 (원)</label>
                                <input type="text" class="form-control" id="total-budget" readonly placeholder="자동 계산">
                            </div>
                        </div>

                        <div class="row mt-3">
                            <div class="col-12">
                                <label for="notes" class="form-label">비고</label>
                                <textarea class="form-control" id="notes" name="notes" rows="3" 
                                          placeholder="계획에 대한 추가 설명이나 특이사항을 입력하세요"></textarea>
                            </div>
                        </div>

                        <!-- Preview Card -->
                        <div class="card mt-4" id="plan-preview" style="display: none;">
                            <div class="card-header">
                                <h6 class="card-title mb-0">계획 미리보기</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <table class="table table-borderless table-sm">
                                            <tr>
                                                <td class="fw-medium">연도:</td>
                                                <td><?= $year ?>년</td>
                                            </tr>
                                            <tr>
                                                <td class="fw-medium">품목:</td>
                                                <td id="preview-item-name">-</td>
                                            </tr>
                                            <tr>
                                                <td class="fw-medium">품목코드:</td>
                                                <td id="preview-item-code">-</td>
                                            </tr>
                                        </table>
                                    </div>
                                    <div class="col-md-6">
                                        <table class="table table-borderless table-sm">
                                            <tr>
                                                <td class="fw-medium">계획수량:</td>
                                                <td id="preview-quantity">-</td>
                                            </tr>
                                            <tr>
                                                <td class="fw-medium">단가:</td>
                                                <td id="preview-unit-price">-</td>
                                            </tr>
                                            <tr>
                                                <td class="fw-medium">총예산:</td>
                                                <td class="fw-bold text-primary" id="preview-total-budget">-</td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="text-end mt-4">
                            <button type="button" class="btn btn-secondary me-2" onclick="history.back()">취소</button>
                            <button type="submit" class="btn btn-success" id="save-plan-btn">
                                <i class="ri-save-line me-1"></i> 계획 저장
                            </button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php \App\Core\View::getInstance()->endSection(); ?>