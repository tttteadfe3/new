<?php \App\Core\View::getInstance()->startSection('content'); ?>
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0"><?= e($pageTitle) ?></h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="/">홈</a></li>
                    <li class="breadcrumb-item"><a href="/supply">지급품 관리</a></li>
                    <li class="breadcrumb-item active">지급 관리</li>
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
                                        <p class="text-uppercase fw-medium text-muted text-truncate mb-0">총 지급 건수</p>
                                        <div class="d-flex align-items-end justify-content-between">
                                            <div>
                                                <h4 class="fs-22 fw-semibold ff-secondary mb-4">
                                                    <span class="counter-value" data-target="<?= $stats['total_distributions'] ?>">0</span>건
                                                </h4>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex-shrink-0">
                                        <div class="avatar-sm flex-shrink-0">
                                            <span class="avatar-title bg-success-subtle rounded fs-3">
                                                <i class="bx bx-package text-success"></i>
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
                                        <p class="text-uppercase fw-medium text-muted text-truncate mb-0">총 지급 수량</p>
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
                                        <p class="text-uppercase fw-medium text-muted text-truncate mb-0">지급 직원 수</p>
                                        <div class="d-flex align-items-end justify-content-between">
                                            <div>
                                                <h4 class="fs-22 fw-semibold ff-secondary mb-4">
                                                    <span class="counter-value" data-target="<?= $stats['unique_employees'] ?>">0</span>명
                                                </h4>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex-shrink-0">
                                        <div class="avatar-sm flex-shrink-0">
                                            <span class="avatar-title bg-warning-subtle rounded fs-3">
                                                <i class="bx bx-user text-warning"></i>
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
                                        <p class="text-uppercase fw-medium text-muted text-truncate mb-0">지급 부서 수</p>
                                        <div class="d-flex align-items-end justify-content-between">
                                            <div>
                                                <h4 class="fs-22 fw-semibold ff-secondary mb-4">
                                                    <span class="counter-value" data-target="<?= $stats['unique_departments'] ?>">0</span>개
                                                </h4>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex-shrink-0">
                                        <div class="avatar-sm flex-shrink-0">
                                            <span class="avatar-title bg-primary-subtle rounded fs-3">
                                                <i class="bx bx-buildings text-primary"></i>
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

<div class="row">
    <div class="col-12">
        <!-- Distributions Table -->
        <div class="card">
            <div class="card-header">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <h5 class="card-title mb-0">지급 목록</h5>
                    </div>
                    <div class="col-md-6 text-end">
                        <a href="/supply/distributions/create" class="btn btn-success">
                            <i class="ri-add-line align-bottom me-1"></i> 지급 등록
                        </a>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="search-box">
                            <input type="text" class="form-control" id="search-distributions" placeholder="품목명, 직원명, 부서명 검색...">
                            <i class="ri-search-line search-icon"></i>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <input type="date" class="form-control" id="filter-start-date" placeholder="시작일">
                    </div>
                    <div class="col-md-3">
                        <input type="date" class="form-control" id="filter-end-date" placeholder="종료일">
                    </div>
                </div>

                <?php if (empty($distributions)): ?>
                    <div class="text-center py-5">
                        <i class="ri-gift-line fs-1 text-muted"></i>
                        <p class="mt-3 text-muted">등록된 지급이 없습니다.</p>
                        <a href="/supply/distributions/create" class="btn btn-success">
                            <i class="ri-add-line me-1"></i> 첫 번째 지급 등록하기
                        </a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-nowrap table-striped-columns mb-0" id="distributions-table">
                            <thead class="table-light">
                                <tr>
                                    <th scope="col">지급일</th>
                                    <th scope="col">품목</th>
                                    <th scope="col">수량</th>
                                    <th scope="col">직원</th>
                                    <th scope="col">부서</th>
                                    <th scope="col">상태</th>
                                    <th scope="col">작업</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($distributions as $distribution): ?>
                                    <tr>
                                        <td><?= date('Y-m-d', strtotime($distribution['distribution_date'])) ?></td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="flex-grow-1">
                                                    <h6 class="fs-14 mb-0"><?= e($distribution['item_name']) ?></h6>
                                                    <p class="text-muted mb-0 fs-12"><?= e($distribution['item_code']) ?></p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-end"><?= number_format($distribution['quantity']) ?></td>
                                        <td><?= e($distribution['employee_name']) ?></td>
                                        <td><?= e($distribution['department_name']) ?></td>
                                        <td>
                                            <?php if ($distribution['is_cancelled']): ?>
                                                <span class="badge badge-soft-danger">
                                                    <i class="ri-close-circle-line me-1"></i>취소됨
                                                </span>
                                                <br>
                                                <small class="text-muted"><?= date('Y-m-d', strtotime($distribution['cancelled_at'])) ?></small>
                                            <?php else: ?>
                                                <span class="badge badge-soft-success">
                                                    <i class="ri-checkbox-circle-line me-1"></i>지급 완료
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="dropdown">
                                                <button class="btn btn-soft-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="ri-more-fill align-middle"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li>
                                                        <a class="dropdown-item" href="/supply/distributions/show?id=<?= $distribution['id'] ?>">
                                                            <i class="ri-eye-fill align-bottom me-2 text-muted"></i> 상세보기
                                                        </a>
                                                    </li>
                                                    <?php if (!$distribution['is_cancelled']): ?>
                                                        <li>
                                                            <a class="dropdown-item" href="/supply/distributions/edit?id=<?= $distribution['id'] ?>">
                                                                <i class="ri-pencil-fill align-bottom me-2 text-muted"></i> 수정
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <button class="dropdown-item cancel-distribution-btn" data-id="<?= $distribution['id'] ?>" data-name="<?= e($distribution['item_name']) ?>" data-employee="<?= e($distribution['employee_name']) ?>">
                                                                <i class="ri-close-circle-fill align-bottom me-2 text-muted"></i> 취소
                                                            </button>
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

<!-- Cancel Distribution Modal -->
<div class="modal fade" id="cancelDistributionModal" tabindex="-1" aria-labelledby="cancelDistributionModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="cancelDistributionModalLabel">지급 취소</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="cancel-distribution-info"></div>
                <div class="mb-3">
                    <label for="cancel-reason" class="form-label">취소 사유 <span class="text-danger">*</span></label>
                    <textarea class="form-control" id="cancel-reason" rows="3" placeholder="취소 사유를 입력하세요" required></textarea>
                </div>
                <div class="alert alert-warning" role="alert">
                    <i class="ri-alert-line me-2"></i>
                    지급을 취소하면 재고가 복원됩니다.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">닫기</button>
                <button type="button" class="btn btn-danger" id="confirm-cancel-distribution-btn">취소 처리</button>
            </div>
        </div>
    </div>
</div>
<?php \App\Core\View::getInstance()->endSection(); ?>
