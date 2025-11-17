<?php \App\Core\View::getInstance()->startSection('content'); ?>
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">지급품 분류 상세</h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="/supply/categories">지급품 분류</a></li>
                    <li class="breadcrumb-item active">상세</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <div class="d-flex align-items-center">
                    <h5 class="card-title mb-0 flex-grow-1">분류 정보</h5>
                    <div class="flex-shrink-0">
                        <a href="/supply/categories/<?= $category->getAttribute('id') ?>/edit" class="btn btn-primary btn-sm">
                            <i class="ri-edit-line me-1"></i> 수정
                        </a>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-medium">분류 코드</label>
                        <div class="form-control-plaintext">
                            <code class="fs-6"><?= e($category->getAttribute('category_code')) ?></code>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-medium">분류 레벨</label>
                        <div class="form-control-plaintext">
                            <span class="badge bg-<?= $category->getAttribute('level') == 1 ? 'primary' : 'info' ?> fs-6">
                                <?= $category->getAttribute('level') == 1 ? '대분류' : '소분류' ?>
                            </span>
                        </div>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-medium">분류명</label>
                        <div class="form-control-plaintext fs-5 fw-medium">
                            <?= e($category->getAttribute('category_name')) ?>
                        </div>
                    </div>
                    <?php if ($parentCategory): ?>
                    <div class="col-12">
                        <label class="form-label fw-medium">상위 분류</label>
                        <div class="form-control-plaintext">
                            <a href="/supply/categories/<?= $parentCategory->getAttribute('id') ?>" class="text-decoration-none">
                                <?= e($parentCategory->getAttribute('category_name')) ?> 
                                <small class="text-muted">(<?= e($parentCategory->getAttribute('category_code')) ?>)</small>
                            </a>
                        </div>
                    </div>
                    <?php endif; ?>
                    <div class="col-md-6">
                        <label class="form-label fw-medium">표시 순서</label>
                        <div class="form-control-plaintext">
                            <?= $category->getAttribute('display_order') ?>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-medium">상태</label>
                        <div class="form-control-plaintext">
                            <span class="badge bg-<?= $category->getAttribute('is_active') ? 'success' : 'secondary' ?> fs-6">
                                <?= $category->getAttribute('is_active') ? '활성' : '비활성' ?>
                            </span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-medium">생성일</label>
                        <div class="form-control-plaintext">
                            <?= date('Y년 m월 d일 H:i', strtotime($category->getAttribute('created_at'))) ?>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-medium">최종 수정일</label>
                        <div class="form-control-plaintext">
                            <?= date('Y년 m월 d일 H:i', strtotime($category->getAttribute('updated_at'))) ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php if (!empty($subCategories)): ?>
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">하위 분류 목록</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>분류 코드</th>
                                <th>분류명</th>
                                <th>표시 순서</th>
                                <th>상태</th>
                                <th>생성일</th>
                                <th>작업</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($subCategories as $subCategory): ?>
                            <tr>
                                <td><code><?= e($subCategory->getAttribute('category_code')) ?></code></td>
                                <td><?= e($subCategory->getAttribute('category_name')) ?></td>
                                <td><?= $subCategory->getAttribute('display_order') ?></td>
                                <td>
                                    <span class="badge bg-<?= $subCategory->getAttribute('is_active') ? 'success' : 'secondary' ?>">
                                        <?= $subCategory->getAttribute('is_active') ? '활성' : '비활성' ?>
                                    </span>
                                </td>
                                <td><?= date('Y-m-d', strtotime($subCategory->getAttribute('created_at'))) ?></td>
                                <td>
                                    <div class="dropdown">
                                        <button class="btn btn-soft-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                            <i class="ri-more-fill"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li><a class="dropdown-item" href="/supply/categories/<?= $subCategory->getAttribute('id') ?>"><i class="ri-eye-line align-bottom me-2 text-muted"></i> 상세보기</a></li>
                                            <li><a class="dropdown-item" href="/supply/categories/<?= $subCategory->getAttribute('id') ?>/edit"><i class="ri-pencil-fill align-bottom me-2 text-muted"></i> 수정</a></li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">빠른 작업</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="/supply/categories/<?= $category->getAttribute('id') ?>/edit" class="btn btn-primary">
                        <i class="ri-edit-line me-1"></i> 분류 수정
                    </a>
                    <?php if ($category->getAttribute('level') == 1): ?>
                    <a href="/supply/categories/create?parent_id=<?= $category->getAttribute('id') ?>" class="btn btn-success">
                        <i class="ri-add-line me-1"></i> 하위 분류 추가
                    </a>
                    <?php endif; ?>
                    <a href="/supply/items?category_id=<?= $category->getAttribute('id') ?>" class="btn btn-info">
                        <i class="ri-list-check me-1"></i> 연관 품목 보기
                    </a>
                    <button type="button" class="btn btn-outline-secondary" id="toggle-status-btn">
                        <i class="ri-toggle-line me-1"></i> 
                        상태 변경 (<?= $category->getAttribute('is_active') ? '비활성화' : '활성화' ?>)
                    </button>
                </div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">분류 통계</h5>
            </div>
            <div class="card-body">
                <div class="row g-3 text-center">
                    <?php if ($category->getAttribute('level') == 1): ?>
                    <div class="col-6">
                        <div class="p-2 bg-light rounded">
                            <h4 class="mb-1 text-primary"><?= count($subCategories) ?></h4>
                            <p class="text-muted mb-0 small">하위 분류</p>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-2 bg-light rounded">
                            <h4 class="mb-1 text-success">0</h4>
                            <p class="text-muted mb-0 small">연관 품목</p>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="col-12">
                        <div class="p-2 bg-light rounded">
                            <h4 class="mb-1 text-success">0</h4>
                            <p class="text-muted mb-0 small">연관 품목</p>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">분류 경로</h5>
            </div>
            <div class="card-body">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0">
                        <?php if ($parentCategory): ?>
                        <li class="breadcrumb-item">
                            <a href="/supply/categories/<?= $parentCategory->getAttribute('id') ?>">
                                <?= e($parentCategory->getAttribute('category_name')) ?>
                            </a>
                        </li>
                        <?php endif; ?>
                        <li class="breadcrumb-item active" aria-current="page">
                            <?= e($category->getAttribute('category_name')) ?>
                        </li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
</div>

<?php \App\Core\View::getInstance()->endSection(); ?>