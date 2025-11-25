<?php \App\Core\View::getInstance()->startSection('content'); ?>
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">차량 소모품 관리</h5>
                <div>
                    <button type="button" class="btn btn-secondary me-2" id="btn-manage-category">
                        <i class="ri-list-settings-line"></i> 카테고리 관리
                    </button>
                    <button type="button" class="btn btn-primary" id="btn-stock-in">
                        <i class="ri-add-line"></i> 입고 등록
                    </button>
                </div>
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
                        <input type="text" class="form-control" id="filter-search" placeholder="카테고리명 검색">
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
                            <th>단위</th>
                            <th>현재재고</th>
                            <th>작업</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- 카테고리 관리 모달 -->
<div class="modal fade" id="categoryModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">카테고리 관리</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <!-- 카테고리 추가 폼 -->
                <form id="categoryForm" class="mb-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">부모 카테고리</label>
                            <select class="form-select" id="parent_category_id">
                                <option value="">최상위 카테고리</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">카테고리명 <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="category_name" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">단위 <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="category_unit" placeholder="예: 리터, 개" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">정렬 순서</label>
                            <input type="number" class="form-control" id="category_sort_order" value="0">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">비고</label>
                            <textarea class="form-control" id="category_note" rows="2"></textarea>
                        </div>
                        <div class="col-md-12">
                            <button type="button" class="btn btn-primary" id="btn-add-category">
                                <i class="ri-add-line"></i> 카테고리 추가
                            </button>
                        </div>
                    </div>
                </form>

                <hr>

                <!-- 카테고리 트리 -->
                <div>
                    <label class="form-label fw-bold">카테고리 목록</label>
                    <div id="category-tree-container" class="border rounded p-3" style="max-height: 400px; overflow-y: auto;">
                        <div class="text-muted">로딩 중...</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 입고 등록 모달 -->
<div class="modal fade" id="stockInModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">입고 등록</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="stockInForm">
                    <div class="mb-3">
                        <label class="form-label">카테고리 <span class="text-danger">*</span></label>
                        <select class="form-select" id="stock_in_category_id" required>
                            <option value="">카테고리 선택</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">품명 <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="stock_in_item_name" required placeholder="예: 현대순정 5W-30">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">수량 <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="stock_in_quantity" min="1" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">단가 (원)</label>
                        <input type="number" class="form-control" id="stock_in_unit_price" min="0" step="0.01">
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
                <button type="button" class="btn btn-primary" id="btn-save-stock-in">등록</button>
            </div>
        </div>
    </div>
</div>

<!-- 사용 등록 모달 -->
<div class="modal fade" id="useModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">사용 등록</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="useForm">
                    <input type="hidden" id="use_category_id">
                    <div class="mb-3">
                        <label class="form-label">카테고리</label>
                        <input type="text" class="form-control" id="use_category_name" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">현재 재고</label>
                        <input type="text" class="form-control" id="use_current_stock" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">품명 (선택)</label>
                        <select class="form-select" id="use_item_name">
                            <option value="">선택</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">수량 <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="use_quantity" min="1" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">차량</label>
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
                <button type="button" class="btn btn-primary" id="btn-save-use">등록</button>
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
                        <a class="nav-link active" data-bs-toggle="tab" href="#tab-stock-by-item">품명별 재고</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#tab-stock-in">입고 이력</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#tab-usage">사용 이력</a>
                    </li>
                </ul>
                <div class="tab-content">
                    <div class="tab-pane fade show active" id="tab-stock-by-item">
                        <div id="stock-by-item-content"></div>
                    </div>
                    <div class="tab-pane fade" id="tab-stock-in">
                        <div id="stock-in-history-content"></div>
                    </div>
                    <div class="tab-pane fade" id="tab-usage">
                        <div id="usage-history-content"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php \App\Core\View::getInstance()->endSection(); ?>
