<?php \App\Core\View::getInstance()->startSection('content'); ?>
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0"><?= e($pageTitle ?? '지급품 구매 관리') ?></h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="/">홈</a></li>
                    <li class="breadcrumb-item"><a href="/supply">지급품 관리</a></li>
                    <li class="breadcrumb-item active">구매 관리</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="row" id="stats-container">
    <!-- Statistics Cards will be loaded here -->
</div>

<div id="pending-purchases-alert-container">
    <!-- Pending purchases alert will be loaded here -->
</div>

<div class="row">
    <div class="col-12">
        <!-- Purchases Table -->
        <div class="card">
            <div class="card-header">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <h5 class="card-title mb-0">구매 목록</h5>
                    </div>
                    <div class="col-md-6 text-end">
                        <div class="btn-group" role="group">
                            <a href="/supply/purchases/create" class="btn btn-success">
                                <i class="ri-add-line align-bottom me-1"></i> 구매 등록
                            </a>
                            <a href="/supply/purchases/receive" class="btn btn-info">
                                <i class="ri-inbox-line align-bottom me-1"></i> 입고 처리
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body">
                 <div class="table-responsive">
                    <table class="table table-nowrap table-striped-columns mb-0" id="purchases-table" style="width:100%">
                        <thead class="table-light">
                            <tr>
                                <th scope="col">구매일</th>
                                <th scope="col">품목</th>
                                <th scope="col">수량</th>
                                <th scope="col">단가</th>
                                <th scope="col">총액</th>
                                <th scope="col">공급업체</th>
                                <th scope="col">입고상태</th>
                                <th scope="col">작업</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Data will be populated by DataTables -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Receive Purchase Modal -->
<div class="modal fade" id="receivePurchaseModal" tabindex="-1" aria-labelledby="receivePurchaseModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="receivePurchaseModalLabel">입고 처리</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="receive-purchase-info"></div>
                <div class="mb-3">
                    <label for="received-date" class="form-label">입고일</label>
                    <input type="date" class="form-control" id="received-date" value="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d') ?>">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
                <button type="button" class="btn btn-success" id="confirm-receive-purchase-btn">입고 처리</button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deletePurchaseModal" tabindex="-1" aria-labelledby="deletePurchaseModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deletePurchaseModalLabel">구매 삭제 확인</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>정말로 이 구매를 삭제하시겠습니까?</p>
                <p class="text-danger small">
                    <i class="ri-alert-line me-1"></i>
                    이미 입고된 구매는 삭제할 수 없습니다.
                </p>
                <div id="delete-purchase-info"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
                <button type="button" class="btn btn-danger" id="confirm-delete-purchase-btn">삭제</button>
            </div>
        </div>
    </div>
</div>
<?php \App\Core\View::getInstance()->endSection(); ?>
