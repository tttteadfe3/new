<?php \App\Core\View::getInstance()->startSection('content'); ?>
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">차량 소모품 관리</h5>
                <button type="button" class="btn btn-primary" id="btn-add-consumable">
                    <i class="ri-add-line"></i> 소모품 등록
                </button>
            </div>
            <div class="card-body">
                <!-- 필터 -->
                <div class="row mb-3">
                    <div class="col-md-3">
                        <select class="form-select" id="filter-category">
                            <option value="">전체 카테고리</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <input type="text" class="form-control" id="filter-search" placeholder="소모품명/부품번호 검색">
                    </div>
                    <div class="col-md-3">
                        <div class="form-check mt-2">
                            <input class="form-check-input" type="checkbox" id="filter-low-stock">
                            <label class="form-check-label" for="filter-low-stock">
                                재고 부족 항목만 보기
                            </label>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <button type="button" class="btn btn-secondary" id="btn-reset-filter">
                            <i class="ri-refresh-line"></i> 초기화
                        </button>
                    </div>
                </div>

                <!-- 테이블 -->
                <table id="consumables-table" class="table table-bordered">
                    <thead>
                        <tr>
                            <th>카테고리</th>
                            <th>소모품명</th>
                            <th>부품번호</th>
                            <th>단위</th>
                            <th>단가</th>
                            <th>현재재고</th>
                            <th>최소재고</th>
                            <th>보관위치</th>
                            <th>작업</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- 소모품 등록/수정 모달 -->
<div class="modal fade" id="consumableModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="consumable-modal-title">소모품 등록</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="consumableForm">
                    <input type="hidden" id="consumable_id">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">소모품명 <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">카테고리</label>
                            <input type="text" class="form-control" id="category" list="category-list">
                            <datalist id="category-list"></datalist>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">부품번호</label>
                            <input type="text" class="form-control" id="part_number">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">보관위치</label>
                            <input type="text" class="form-control" id="location">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">단위 <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="unit" value="개" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">단가 (원)</label>
                            <input type="number" class="form-control" id="unit_price" min="0" step="0.01">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">최소재고</label>
                            <input type="number" class="form-control" id="minimum_stock" min="0" value="0">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">비고</label>
                            <textarea class="form-control" id="note" rows="2"></textarea>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
                <button type="button" class="btn btn-primary" id="btn-save-consumable">저장</button>
            </div>
        </div>
    </div>
</div>

<!-- 입고 모달 -->
<div class="modal fade" id="stockInModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">입고 처리</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="stockInForm">
                    <input type="hidden" id="stock_in_consumable_id">
                    <div class="mb-3">
                        <label class="form-label">소모품명</label>
                        <input type="text" class="form-control" id="stock_in_name" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">입고 수량 <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="stock_in_quantity" min="1" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">입고 단가 (원)</label>
                        <input type="number" class="form-control" id="stock_in_unit_price" min="0" step="0.01">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">공급업체</label>
                        <input type="text" class="form-control" id="stock_in_supplier">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">구매일</label>
                        <input type="date" class="form-control" id="stock_in_purchase_date">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">비고</label>
                        <textarea class="form-control" id="stock_in_note" rows="2"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
                <button type="button" class="btn btn-primary" id="btn-save-stock-in">입고</button>
            </div>
        </div>
    </div>
</div>

<!-- 출고/사용 모달 -->
<div class="modal fade" id="useModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">출고/사용 처리</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="useForm">
                    <input type="hidden" id="use_consumable_id">
                    <div class="mb-3">
                        <label class="form-label">소모품명</label>
                        <input type="text" class="form-control" id="use_name" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">현재 재고</label>
                        <input type="text" class="form-control" id="use_current_stock" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">사용 수량 <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="use_quantity" min="1" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">사용 차량</label>
                        <select class="form-select" id="use_vehicle_id">
                            <option value="">선택</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">비고</label>
                        <textarea class="form-control" id="use_note" rows="2"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
                <button type="button" class="btn btn-primary" id="btn-save-use">출고</button>
            </div>
        </div>
    </div>
</div>

<!-- 이력 조회 모달 -->
<div class="modal fade" id="historyModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">이력 조회</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <ul class="nav nav-tabs mb-3" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" data-bs-toggle="tab" href="#tab-usage">사용 이력</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#tab-stock-in">입고 이력</a>
                    </li>
                </ul>
                <div class="tab-content">
                    <div class="tab-pane fade show active" id="tab-usage">
                        <div id="usage-history-content"></div>
                    </div>
                    <div class="tab-pane fade" id="tab-stock-in">
                        <div id="stock-in-history-content"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php \App\Core\View::getInstance()->endSection(); ?>
