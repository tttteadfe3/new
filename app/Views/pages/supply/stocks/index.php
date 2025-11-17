<?php \App\Core\View::getInstance()->startSection('content'); ?>
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <div class="d-flex align-items-center">
                    <h5 class="card-title mb-0 flex-grow-1">재고 현황</h5>
                    <button class="btn btn-primary" id="refresh-btn">
                        <i class="ri-refresh-line align-bottom me-1"></i> 새로고침
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="row g-3 mb-3">
                    <div class="col-md-3">
                        <label for="filter-category" class="form-label">분류</label>
                        <select class="form-select" id="filter-category">
                            <option value="">전체</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="filter-stock-status" class="form-label">재고 상태</label>
                        <select class="form-select" id="filter-stock-status">
                            <option value="">전체</option>
                            <option value="sufficient">충분</option>
                            <option value="low">부족</option>
                            <option value="out">품절</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="search-input" class="form-label">검색</label>
                        <div class="search-box">
                            <input type="text" class="form-control" id="search-input" placeholder="품목명 또는 코드 검색...">
                            <i class="ri-search-line search-icon"></i>
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table id="stocks-table" class="table table-bordered dt-responsive nowrap table-striped align-middle" style="width:100%">
                        <thead>
                            <tr>
                                <th>품목 코드</th>
                                <th>품목명</th>
                                <th>분류</th>
                                <th>단위</th>
                                <th>현재 재고</th>
                                <th>안전 재고</th>
                                <th>재고 상태</th>
                                <th>최근 입고일</th>
                                <th>작업</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- DataTables will populate this -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Stock Detail Modal -->
<div class="modal fade" id="stockDetailModal" tabindex="-1" aria-labelledby="stockDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="stockDetailModalLabel">재고 상세 내역</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="stock-detail-content">
                    <!-- Content will be loaded dynamically -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">닫기</button>
            </div>
        </div>
    </div>
</div>

<?php \App\Core\View::getInstance()->endSection(); ?>
