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
                    <li class="breadcrumb-item active">예산 요약</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <!-- Year Selection -->
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
                            <a href="/supply/plans?year=<?= $currentYear ?>" class="btn btn-outline-primary">
                                <i class="ri-list-check-2 me-1"></i> 계획 목록
                            </a>
                            <button type="button" class="btn btn-primary" id="export-summary-btn">
                                <i class="ri-download-2-line me-1"></i> 요약 다운로드
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- 전체 요약 카드 -->
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
                                <?php if ($previousBudgetSummary): ?>
                                    <?php 
                                    $budgetChange = $budgetSummary['total_budget'] - $previousBudgetSummary['total_budget'];
                                    $budgetChangePercent = $previousBudgetSummary['total_budget'] > 0 ? 
                                        ($budgetChange / $previousBudgetSummary['total_budget']) * 100 : 0;
                                    ?>
                                    <p class="text-muted mb-0">
                                        <span class="<?= $budgetChange >= 0 ? 'text-success' : 'text-danger' ?> fw-medium">
                                            <?= $budgetChange >= 0 ? '+' : '' ?><?= number_format($budgetChangePercent, 1) ?>%
                                        </span> 전년 대비
                                    </p>
                                <?php endif; ?>
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

<div class="row">
    <div class="col-xl-8">
        <!-- 분류별 예산 차트 -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">분류별 예산 분포</h5>
            </div>
            <div class="card-body">
                <?php if (empty($budgetSummary['category_budgets'])): ?>
                    <div class="text-center py-5">
                        <i class="ri-pie-chart-line fs-1 text-muted"></i>
                        <p class="mt-3 text-muted">표시할 데이터가 없습니다.</p>
                    </div>
                <?php else: ?>
                    <canvas id="categoryBudgetChart" height="300"></canvas>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <!-- 분류별 상세 정보 -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">분류별 상세</h5>
            </div>
            <div class="card-body">
                <?php if (empty($budgetSummary['category_budgets'])): ?>
                    <div class="text-center py-3">
                        <p class="text-muted mb-0">표시할 데이터가 없습니다.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>분류</th>
                                    <th class="text-end">품목수</th>
                                    <th class="text-end">예산</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($budgetSummary['category_budgets'] as $category): ?>
                                    <tr>
                                        <td>
                                            <span class="badge badge-soft-primary"><?= e($category['category_name']) ?></span>
                                        </td>
                                        <td class="text-end"><?= number_format($category['item_count']) ?>개</td>
                                        <td class="text-end">₩<?= number_format($category['total_budget']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <th>합계</th>
                                    <th class="text-end"><?= number_format($budgetSummary['total_items']) ?>개</th>
                                    <th class="text-end">₩<?= number_format($budgetSummary['total_budget']) ?></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php if ($previousBudgetSummary): ?>
<div class="row">
    <div class="col-12">
        <!-- 전년 대비 비교 -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><?= $currentYear ?>년 vs <?= $previousYear ?>년 비교</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>구분</th>
                                <th class="text-center"><?= $previousYear ?>년</th>
                                <th class="text-center"><?= $currentYear ?>년</th>
                                <th class="text-center">증감</th>
                                <th class="text-center">증감률</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><strong>총 품목수</strong></td>
                                <td class="text-end"><?= number_format($previousBudgetSummary['total_items']) ?>개</td>
                                <td class="text-end"><?= number_format($budgetSummary['total_items']) ?>개</td>
                                <td class="text-end">
                                    <?php 
                                    $itemChange = $budgetSummary['total_items'] - $previousBudgetSummary['total_items'];
                                    $itemChangeClass = $itemChange >= 0 ? 'text-success' : 'text-danger';
                                    ?>
                                    <span class="<?= $itemChangeClass ?>">
                                        <?= $itemChange >= 0 ? '+' : '' ?><?= number_format($itemChange) ?>개
                                    </span>
                                </td>
                                <td class="text-end">
                                    <?php 
                                    $itemChangePercent = $previousBudgetSummary['total_items'] > 0 ? 
                                        ($itemChange / $previousBudgetSummary['total_items']) * 100 : 0;
                                    ?>
                                    <span class="<?= $itemChangeClass ?>">
                                        <?= $itemChange >= 0 ? '+' : '' ?><?= number_format($itemChangePercent, 1) ?>%
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>총 수량</strong></td>
                                <td class="text-end"><?= number_format($previousBudgetSummary['total_quantity']) ?>개</td>
                                <td class="text-end"><?= number_format($budgetSummary['total_quantity']) ?>개</td>
                                <td class="text-end">
                                    <?php 
                                    $quantityChange = $budgetSummary['total_quantity'] - $previousBudgetSummary['total_quantity'];
                                    $quantityChangeClass = $quantityChange >= 0 ? 'text-success' : 'text-danger';
                                    ?>
                                    <span class="<?= $quantityChangeClass ?>">
                                        <?= $quantityChange >= 0 ? '+' : '' ?><?= number_format($quantityChange) ?>개
                                    </span>
                                </td>
                                <td class="text-end">
                                    <?php 
                                    $quantityChangePercent = $previousBudgetSummary['total_quantity'] > 0 ? 
                                        ($quantityChange / $previousBudgetSummary['total_quantity']) * 100 : 0;
                                    ?>
                                    <span class="<?= $quantityChangeClass ?>">
                                        <?= $quantityChange >= 0 ? '+' : '' ?><?= number_format($quantityChangePercent, 1) ?>%
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>총 예산</strong></td>
                                <td class="text-end">₩<?= number_format($previousBudgetSummary['total_budget']) ?></td>
                                <td class="text-end">₩<?= number_format($budgetSummary['total_budget']) ?></td>
                                <td class="text-end">
                                    <?php 
                                    $budgetChange = $budgetSummary['total_budget'] - $previousBudgetSummary['total_budget'];
                                    $budgetChangeClass = $budgetChange >= 0 ? 'text-success' : 'text-danger';
                                    ?>
                                    <span class="<?= $budgetChangeClass ?>">
                                        <?= $budgetChange >= 0 ? '+' : '' ?>₩<?= number_format(abs($budgetChange)) ?>
                                    </span>
                                </td>
                                <td class="text-end">
                                    <?php 
                                    $budgetChangePercent = $previousBudgetSummary['total_budget'] > 0 ? 
                                        ($budgetChange / $previousBudgetSummary['total_budget']) * 100 : 0;
                                    ?>
                                    <span class="<?= $budgetChangeClass ?>">
                                        <?= $budgetChange >= 0 ? '+' : '' ?><?= number_format($budgetChangePercent, 1) ?>%
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>
<?php \App\Core\View::getInstance()->endSection(); ?>
