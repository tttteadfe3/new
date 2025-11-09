<?php \App\Core\View::getInstance()->startSection('content'); ?>
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">지급품 구입 및 입고 관리</h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="/dashboard">대시보드</a></li>
                    <li class="breadcrumb-item">지급품 관리</li>
                    <li class="breadcrumb-item active">구입/입고</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <div class="d-flex flex-wrap align-items-center">
                    <h5 class="card-title mb-0 flex-grow-1">구입 내역</h5>
                    <div class="d-flex gap-2 flex-wrap">
                        <select class="form-select" id="year-filter" style="width: 120px;"></select>
                        <select class="form-select" id="stocked-filter" style="width: 150px;">
                            <option value="">전체</option>
                            <option value="0">미입고</option>
                            <option value="1">입고완료</option>
                        </select>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#purchase-modal">
                            <i class="ri-add-line align-bottom me-1"></i> 신규 구입 등록
                        </button>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>구입일자</th>
                                <th>분류</th>
                                <th>품목명</th>
                                <th>수량</th>
                                <th>단가</th>
                                <th>공급업체</th>
                                <th>상태</th>
                                <th>작업</th>
                            </tr>
                        </thead>
                        <tbody id="purchases-table-body">
                            <!-- JS로 데이터 렌더링 -->
                        </tbody>
                    </table>
                </div>
                <div id="table-placeholder" class="text-center p-5"><p class="text-muted">조회 조건에 맞는 내역을 불러오는 중입니다...</p></div>
            </div>
        </div>
    </div>
</div>

<!-- Purchase Modal -->
<div class="modal fade" id="purchase-modal" tabindex="-1" aria-labelledby="purchase-modal-label" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="purchase-form">
                <div class="modal-header">
                    <h5 class="modal-title" id="purchase-modal-label">구입 내역 등록/수정</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="purchase-id" name="id">

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="purchase-date" class="form-label">구입일자</label>
                            <input type="date" class="form-control" id="purchase-date" name="purchase_date" required>
                        </div>
                        <div class="col-md-6 mb-3">
                             <label for="purchase-plan" class="form-label">관련 계획 (선택)</label>
                            <select class="form-select" id="purchase-plan" name="plan_id">
                                <option value="">선택 안함</option>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="purchase-category" class="form-label">분류</label>
                            <select class="form-select" id="purchase-category" name="category_id" required>
                                <option value="">분류 선택</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="purchase-item" class="form-label">품목</label>
                            <select class="form-select" id="purchase-item" name="item_id" required>
                                <option value="">분류를 먼저 선택하세요</option>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                         <div class="col-md-6 mb-3">
                            <label for="purchase-unit-price" class="form-label">단가</label>
                            <input type="number" class="form-control" id="purchase-unit-price" name="unit_price" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="purchase-quantity" class="form-label">수량</label>
                            <input type="number" class="form-control" id="purchase-quantity" name="quantity" required>
                        </div>
                    </div>

                     <div class="mb-3">
                        <label for="purchase-supplier" class="form-label">공급업체</label>
                        <input type="text" class="form-control" id="purchase-supplier" name="supplier">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">닫기</button>
                    <button type="submit" class="btn btn-primary">저장</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php \App\Core\View::getInstance()->endSection(); ?>
