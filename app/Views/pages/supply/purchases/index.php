<?php \App\Core\View::getInstance()->startSection('content'); ?>
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0"><?= e($pageTitle) ?></h4>
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

<div class="row">
    <div class="col-12">
        <!-- Statistics Cards -->
        <div class="card">
            <div class="card-body">
                <div class="row">
                    <div class="col-xl-3 col-md-6">
                        <div class="card card-animate">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1 overflow-hidden">
                                        <p class="text-uppercase fw-medium text-muted text-truncate mb-0">총 구매 건수</p>
                                        <div class="d-flex align-items-end justify-content-between">
                                            <div>
                                                <h4 class="fs-22 fw-semibold ff-secondary mb-4">
                                                    <span class="counter-value" data-target="<?= $stats['total_purchases'] ?>">0</span>건
                                                </h4>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex-shrink-0">
                                        <div class="avatar-sm flex-shrink-0">
                                            <span class="avatar-title bg-success-subtle rounded fs-3">
                                                <i class="bx bx-shopping-bag text-success"></i>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <div class="card card-animate">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1 overflow-hidden">
                                        <p class="text-uppercase fw-medium text-muted text-truncate mb-0">총 구매 수량</p>
                                        <div class="d-flex align-items-end justify-content-between">
                                            <div>
                                                <h4 class="fs-22 fw-semibold ff-secondary mb-4">
                                                    <span class="counter-value" data-target="<?= $stats['total_quantity'] ?>">0</span>개
                                                </h4>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex-shrink-0">
                                        <div class="avatar-sm flex-shrink-0">
                                            <span class="avatar-title bg-info-subtle rounded fs-3">
                                                <i class="bx bx-cube text-info"></i>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <div class="card card-animate">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1 overflow-hidden">
                                        <p class="text-uppercase fw-medium text-muted text-truncate mb-0">총 구매 금액</p>
                                        <div class="d-flex align-items-end justify-content-between">
                                            <div>
                                                <h4 class="fs-22 fw-semibold ff-secondary mb-4">
                                                    ₩<span class="counter-value" data-target="<?= number_format($stats['total_amount']) ?>">0</span>
                                                </h4>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex-shrink-0">
                                        <div class="avatar-sm flex-shrink-0">
                                            <span class="avatar-title bg-warning-subtle rounded fs-3">
                                                <i class="bx bx-won text-warning"></i>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <div class="card card-animate">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1 overflow-hidden">
                                        <p class="text-uppercase fw-medium text-muted text-truncate mb-0">미입고 건수</p>
                                        <div class="d-flex align-items-end justify-content-between">
                                            <div>
                                                <h4 class="fs-22 fw-semibold ff-secondary mb-4">
                                                    <span class="counter-value text-danger" data-target="<?= $stats['pending_purchases'] ?>">0</span>건
                                                </h4>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex-shrink-0">
                                        <div class="avatar-sm flex-shrink-0">
                                            <span class="avatar-title bg-danger-subtle rounded fs-3">
                                                <i class="bx bx-time text-danger"></i>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($pendingPurchases)): ?>
<div class="row">
    <div class="col-12">
        <!-- Pending Purchases Alert -->
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <i class="ri-alert-line me-2"></i>
            <strong>입고 대기 중인 구매가 <?= count($pendingPurchases) ?>건 있습니다.</strong>
            <a href="/supply/purchases/receive" class="alert-link ms-2">입고 처리하기</a>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    </div>
</div>
<?php endif; ?>

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
                <div class="row mb-3">
                    <div class="col-md-8">
                        <div class="search-box">
                            <input type="text" class="form-control" id="search-purchases" placeholder="품목명, 코드, 공급업체 검색...">
                            <i class="ri-search-line search-icon"></i>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <select class="form-select" id="filter-status">
                            <option value="">모든 상태</option>
                            <option value="received">입고 완료</option>
                            <option value="pending">입고 대기</option>
                        </select>
                    </div>
                </div>

                <?php if (empty($purchases)): ?>
                    <div class="text-center py-5">
                        <i class="ri-shopping-bag-3-line fs-1 text-muted"></i>
                        <p class="mt-3 text-muted">등록된 구매가 없습니다.</p>
                        <a href="/supply/purchases/create" class="btn btn-success">
                            <i class="ri-add-line me-1"></i> 첫 번째 구매 등록하기
                        </a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-nowrap table-striped-columns mb-0" id="purchases-table">
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
                                <?php foreach ($purchases as $purchase): ?>
                                    <tr>
                                        <td><?= date('Y-m-d', strtotime($purchase['purchase_date'])) ?></td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="flex-grow-1">
                                                    <h6 class="fs-14 mb-0"><?= e($purchase['item_name']) ?></h6>
                                                    <p class="text-muted mb-0 fs-12"><?= e($purchase['item_code']) ?></p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-end"><?= number_format($purchase['quantity']) ?> <?= e($purchase['unit']) ?></td>
                                        <td class="text-end">₩<?= number_format($purchase['unit_price']) ?></td>
                                        <td class="text-end">
                                            <strong>₩<?= number_format($purchase['quantity'] * $purchase['unit_price']) ?></strong>
                                        </td>
                                        <td><?= e($purchase['supplier'] ?? '-') ?></td>
                                        <td>
                                            <?php if ($purchase['is_received']): ?>
                                                <span class="badge badge-soft-success">
                                                    <i class="ri-checkbox-circle-line me-1"></i>입고 완료
                                                </span>
                                                <br>
                                                <small class="text-muted"><?= date('Y-m-d', strtotime($purchase['received_date'])) ?></small>
                                            <?php else: ?>
                                                <span class="badge badge-soft-warning">
                                                    <i class="ri-time-line me-1"></i>입고 대기
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="dropdown">
                                                <button class="btn btn-soft-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="ri-more-fill align-middle"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <?php if (!$purchase['is_received']): ?>
                                                        <li>
                                                            <button class="dropdown-item receive-purchase-btn" data-id="<?= $purchase['id'] ?>" data-name="<?= e($purchase['item_name']) ?>">
                                                                <i class="ri-inbox-fill align-bottom me-2 text-muted"></i> 입고 처리
                                                            </button>
                                                        </li>
                                                        <li>
                                                            <a class="dropdown-item" href="/supply/purchases/edit?id=<?= $purchase['id'] ?>">
                                                                <i class="ri-pencil-fill align-bottom me-2 text-muted"></i> 수정
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <button class="dropdown-item delete-purchase-btn" data-id="<?= $purchase['id'] ?>" data-name="<?= e($purchase['item_name']) ?>">
                                                                <i class="ri-delete-bin-fill align-bottom me-2 text-muted"></i> 삭제
                                                            </button>
                                                        </li>
                                                    <?php else: ?>
                                                        <li>
                                                            <a class="dropdown-item" href="/supply/purchases/show?id=<?= $purchase['id'] ?>">
                                                                <i class="ri-eye-fill align-bottom me-2 text-muted"></i> 상세보기
                                                            </a>
                                                        </li>
                                                    <?php endif; ?>
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
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