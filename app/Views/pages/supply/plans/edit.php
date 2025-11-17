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
                    <li class="breadcrumb-item active">수정</li>
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
                    <h5 class="card-title mb-0 flex-grow-1">지급품 계획 수정</h5>
                    <a href="/supply/plans?year=<?= $plan->getYear() ?>" class="btn btn-secondary">
                        <i class="ri-arrow-left-line me-1"></i> 목록으로
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div id="loading-container" class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">로딩 중...</span>
                    </div>
                </div>

                <!-- 품목 정보 표시 (수정 불가) -->
                <div class="card bg-light mb-4" id="item-info-card" style="display: none;">
                    <div class="card-body">
                        <h6 class="card-title">품목 정보 (수정 불가)</h6>
                        <div class="row">
                            <div class="col-md-3">
                                <strong>연도:</strong> <span id="plan-year">-</span>년
                            </div>
                            <div class="col-md-3">
                                <strong>품목코드:</strong> <span id="item-code">-</span>
                            </div>
                            <div class="col-md-3">
                                <strong>품목명:</strong> <span id="item-name">-</span>
                            </div>
                            <div class="col-md-3">
                                <strong>단위:</strong> <span id="item-unit">-</span>
                            </div>
                        </div>
                    </div>
                </div>

                <form id="planEditForm" class="needs-validation" novalidate style="display: none;">
                    <input type="hidden" name="id" id="plan-id" value="<?= $planId ?>">
                    
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="planned-quantity" class="form-label">계획 수량 <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="planned-quantity" name="planned_quantity" 
                                       required min="1" max="999999">
                                <span class="input-group-text" id="unit-display">개</span>
                            </div>
                            <div class="invalid-feedback">
                                1 이상 999,999 이하의 수량을 입력해주세요.
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label for="unit-price" class="form-label">단가 <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">₩</span>
                                <input type="number" class="form-control" id="unit-price" name="unit_price" 
                                       required min="0" max="9999999.99" step="0.01">
                            </div>
                            <div class="invalid-feedback">
                                0 이상 9,999,999.99 이하의 단가를 입력해주세요.
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label for="total-budget" class="form-label">총 예산</label>
                            <div class="input-group">
                                <span class="input-group-text">₩</span>
                                <input type="text" class="form-control" id="total-budget" readonly>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-3">
                        <div class="col-12">
                            <label for="notes" class="form-label">비고</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3" 
                                      placeholder="계획에 대한 추가 설명이나 특이사항을 입력하세요"></textarea>
                        </div>
                    </div>

                    <!-- 변경 사항 미리보기 -->
                    <div class="card mt-4" id="changes-preview" style="display: none;">
                        <div class="card-header">
                            <h6 class="card-title mb-0">변경 사항 미리보기</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>항목</th>
                                            <th>현재 값</th>
                                            <th>변경 후</th>
                                            <th>차이</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr id="quantity-change" style="display: none;">
                                            <td>계획 수량</td>
                                            <td id="original-quantity">-</td>
                                            <td id="new-quantity">-</td>
                                            <td id="quantity-diff">-</td>
                                        </tr>
                                        <tr id="price-change" style="display: none;">
                                            <td>단가</td>
                                            <td id="original-price">-</td>
                                            <td id="new-price">-</td>
                                            <td id="price-diff">-</td>
                                        </tr>
                                        <tr id="budget-change" style="display: none;">
                                            <td><strong>총 예산</strong></td>
                                            <td id="original-budget">-</td>
                                            <td id="new-budget">-</td>
                                            <td id="budget-diff">-</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="text-end mt-4">
                        <button type="button" class="btn btn-secondary me-2" onclick="history.back()">취소</button>
                        <button type="submit" class="btn btn-primary" id="save-plan-btn">
                            <i class="ri-save-line me-1"></i> 변경사항 저장
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php \App\Core\View::getInstance()->endSection(); ?>