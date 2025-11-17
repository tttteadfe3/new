<?php \App\Core\View::getInstance()->startSection('content'); ?>
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0"><?= e($pageTitle) ?></h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="/">홈</a></li>
                    <li class="breadcrumb-item"><a href="/supply">지급품 관리</a></li>
                    <li class="breadcrumb-item active">재고 현황 보고서</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <!-- Filters -->
        <div class="card">
            <div class="card-header">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <h5 class="card-title mb-0">필터 및 검색</h5>
                    </div>
                    <div class="col-md-6 text-end">
                        <button type="button" class="btn btn-success" id="export-report-btn">
                            <i class="ri-download-2-line align-bottom me-1"></i> 엑셀 다운로드
                        </button>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <form id="filter-form">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">분류</label>
                            <select class="form-select" name="category_id" id="category-filter">
                                <option value="">전체</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?= $category->getAttribute('id') ?>" 
                                        <?= isset($filters['category_id']) && $filters['category_id'] == $category->getAttribute('id') ? 'selected' : '' ?>>
                                        <?= e($category->getAttribute('category_name')) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">재고 상태</label>
                            <select class="form-select" name="stock_status" id="stock-status-filter">
                                <option value="">전체</option>
                                <option value="low_stock" <?= isset($filters['low_stock']) && $filters['low_stock'] ? 'selected' : '' ?>>재고 부족</option>
                                <option value="out_of_stock" <?= isset($filters['out_of_stock']) && $filters['out_of_stock'] ? 'selected' : '' ?>>재고 없음</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">재고 부족 기준</label>
                            <input type="number" class="form-control" name="threshold" id="threshold-filter" 
                                value="<?= $filters['threshold'] ?? 10 ?>" min="1">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="ri-search-line me-1"></i> 조회
                            </button>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <button type="button" class="btn btn-secondary w-100" id="reset-filter-btn">
                                <i class="ri-refresh-line me-1"></i> 초기화
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <!-- Stock Report Table -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">재고 현황 목록 <span id="total-count">(총 0개 품목)</span></h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-nowrap table-striped-columns mb-0" id="stock-report-table">
                        <thead class="table-light">
                            <tr>
                                <th scope="col">품목코드</th>
                                <th scope="col">품목명</th>
                                <th scope="col">분류</th>
                                <th scope="col">단위</th>
                                <th scope="col">총구매량</th>
                                <th scope="col">총지급량</th>
                                <th scope="col">현재고</th>
                                <th scope="col">재고상태</th>
                                <th scope="col">최종업데이트</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- DataTables will populate this -->
                                    <?php
                                    $threshold = $filters['threshold'] ?? 10;
                                    $stockStatus = 'normal';
                                    $stockBadge = 'success';
                                    $stockText = '정상';
                                    
                                    if ($stock['current_stock'] == 0) {
                                        $stockStatus = 'out';
                                        $stockBadge = 'danger';
                                        $stockText = '재고없음';
                                    } elseif ($stock['current_stock'] <= $threshold) {
                                        $stockStatus = 'low';
                                        $stockBadge = 'warning';
                                        $stockText = '재고부족';
                                    }
                                    ?>
                                    <tr>
                                        <td><?= e($stock['item_code']) ?></td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="flex-grow-1">
                                                    <h6 class="fs-14 mb-0"><?= e($stock['item_name']) ?></h6>
                                                </div>
                                            </div>
                                        </td>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php \App\Core\View::getInstance()->endSection(); ?>