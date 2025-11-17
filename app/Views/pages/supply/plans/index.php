<?php \App\Core\View::getInstance()->startSection('content'); ?>
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0"><?= e($pageTitle) ?></h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="/">홈</a></li>
                    <li class="breadcrumb-item"><a href="/supply">지급품 관리</a></li>
                    <li class="breadcrumb-item active">연간 계획</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <!-- Year Selection and Summary -->
        <div class="card">
            <div class="card-header">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <div class="d-flex align-items-center">
                            <h5 class="card-title mb-0 me-3">연도 선택</h5>
                            <select class="form-select" id="year-selector" style="width: auto;">
                                <?php foreach ($years as $year): ?>
                                    <option value="<?= $year ?>" <?= $year == $currentYear ? 'selected' : '' ?>>
                                        <?= $year ?>년
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6 text-end">
                        <div class="btn-group" role="group">
                            <a href="/supply/plans/create?year=<?= $currentYear ?>" class="btn btn-success">
                                <i class="ri-add-line align-bottom me-1"></i> 신규 계획
                            </a>
                            <a href="/supply/plans/import?year=<?= $currentYear ?>" class="btn btn-info">
                                <i class="ri-upload-2-line align-bottom me-1"></i> 엑셀 업로드
                            </a>
                            <button type="button" class="btn btn-primary" id="export-excel-btn">
                                <i class="ri-download-2-line align-bottom me-1"></i> 엑셀 다운로드
                            </button>
                            <a href="/supply/plans/budget-summary?year=<?= $currentYear ?>" class="btn btn-outline-primary">
                                <i class="ri-bar-chart-line align-bottom me-1"></i> 예산 요약
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <!-- Budget Summary Cards -->
                <div class="row">
                    <div class="col-xl-3 col-md-6">
                        <div class="card card-animate">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1 overflow-hidden">
                                        <p class="text-uppercase fw-medium text-muted text-truncate mb-0">총 계획 품목</p>
                                        <div class="d-flex align-items-end justify-content-between">
                                            <div>
                                                <h4 class="fs-22 fw-semibold ff-secondary mb-4">
                                                    <span class="counter-value" data-target="<?= $budgetSummary['total_items'] ?>">0</span>개
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
                                        <p class="text-uppercase fw-medium text-muted text-truncate mb-0">총 계획 수량</p>
                                        <div class="d-flex align-items-end justify-content-between">
                                            <div>
                                                <h4 class="fs-22 fw-semibold ff-secondary mb-4">
                                                    <span class="counter-value" data-target="<?= $budgetSummary['total_quantity'] ?>">0</span>개
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
                                        <p class="text-uppercase fw-medium text-muted text-truncate mb-0">총 예산</p>
                                        <div class="d-flex align-items-end justify-content-between">
                                            <div>
                                                <h4 class="fs-22 fw-semibold ff-secondary mb-4">
                                                    ₩<span class="counter-value" data-target="<?= number_format($budgetSummary['total_budget']) ?>">0</span>
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
                                        <p class="text-uppercase fw-medium text-muted text-truncate mb-0">평균 단가</p>
                                        <div class="d-flex align-items-end justify-content-between">
                                            <div>
                                                <h4 class="fs-22 fw-semibold ff-secondary mb-4">
                                                    ₩<span class="counter-value" data-target="<?= number_format($budgetSummary['avg_unit_price']) ?>">0</span>
                                                </h4>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex-shrink-0">
                                        <div class="avatar-sm flex-shrink-0">
                                            <span class="avatar-title bg-primary-subtle rounded fs-3">
                                                <i class="bx bx-calculator text-primary"></i>
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
        <!-- Plans Table -->
        <div class="card">
            <div class="card-header">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <h5 class="card-title mb-0"><?= $currentYear ?>년 지급품 계획 목록</h5>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex justify-content-end">
                            <div class="search-box me-2">
                                <input type="text" class="form-control" id="search-plans" placeholder="품목명, 코드, 분류 검색...">
                                <i class="ri-search-line search-icon"></i>
                            </div>
                            <select class="form-select" id="filter-category" style="width: auto;">
                                <option value="">모든 분류</option>
                                <?php foreach ($budgetSummary['category_budgets'] as $category): ?>
                                    <option value="<?= e($category['category_name']) ?>">
                                        <?= e($category['category_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <?php if (empty($plans)): ?>
                    <div class="text-center py-5">
                        <i class="ri-file-list-3-line fs-1 text-muted"></i>
                        <p class="mt-3 text-muted"><?= $currentYear ?>년도에 등록된 계획이 없습니다.</p>
                        <a href="/supply/plans/create?year=<?= $currentYear ?>" class="btn btn-success">
                            <i class="ri-add-line me-1"></i> 첫 번째 계획 등록하기
                        </a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-nowrap table-striped-columns mb-0" id="plans-table">
                            <thead class="table-light">
                                <tr>
                                    <th scope="col">품목코드</th>
                                    <th scope="col">품목명</th>
                                    <th scope="col">분류</th>
                                    <th scope="col">단위</th>
                                    <th scope="col">계획수량</th>
                                    <th scope="col">단가</th>
                                    <th scope="col">총예산</th>
                                    <th scope="col">등록일</th>
                                    <th scope="col">작업</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($plans as $plan): ?>
                                    <tr>
                                        <td><?= e($plan['item_code']) ?></td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="flex-grow-1">
                                                    <h6 class="fs-14 mb-0"><?= e($plan['item_name']) ?></h6>
                                                    <?php if (!empty($plan['notes'])): ?>
                                                        <p class="text-muted mb-0 fs-12"><?= e($plan['notes']) ?></p>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge badge-soft-primary"><?= e($plan['category_name']) ?></span>
                                        </td>
                                        <td><?= e($plan['unit']) ?></td>
                                        <td class="text-end"><?= number_format($plan['planned_quantity']) ?></td>
                                        <td class="text-end">₩<?= number_format($plan['unit_price']) ?></td>
                                        <td class="text-end">
                                            <strong>₩<?= number_format($plan['planned_quantity'] * $plan['unit_price']) ?></strong>
                                        </td>
                                        <td><?= date('Y-m-d', strtotime($plan['created_at'])) ?></td>
                                        <td>
                                            <div class="dropdown">
                                                <button class="btn btn-soft-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="ri-more-fill align-middle"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li>
                                                        <a class="dropdown-item" href="/supply/plans/edit?id=<?= $plan['id'] ?>">
                                                            <i class="ri-pencil-fill align-bottom me-2 text-muted"></i> 수정
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <button class="dropdown-item delete-plan-btn" data-id="<?= $plan['id'] ?>" data-name="<?= e($plan['item_name']) ?>">
                                                            <i class="ri-delete-bin-fill align-bottom me-2 text-muted"></i> 삭제
                                                        </button>
                                                    </li>
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

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deletePlanModal" tabindex="-1" aria-labelledby="deletePlanModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deletePlanModalLabel">계획 삭제 확인</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>정말로 이 계획을 삭제하시겠습니까?</p>
                <p class="text-danger small">
                    <i class="ri-alert-line me-1"></i>
                    이미 구매나 지급 기록이 있는 계획은 삭제할 수 없습니다.
                </p>
                <div id="delete-plan-info"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
                <button type="button" class="btn btn-danger" id="confirm-delete-plan-btn">삭제</button>
            </div>
        </div>
    </div>
</div>
<?php \App\Core\View::getInstance()->endSection(); ?>