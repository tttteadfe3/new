<?php \App\Core\View::getInstance()->startSection('content'); ?>
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">지급품 분류 수정</h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="/supply/categories">지급품 분류</a></li>
                    <li class="breadcrumb-item active">수정</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">분류 정보 수정</h5>
            </div>
            <div class="card-body">
                <form id="editCategoryForm">
                    <input type="hidden" id="category-id" name="id" value="<?= $category->getAttribute('id') ?>">
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="category-level" class="form-label">분류 레벨</label>
                            <input type="text" class="form-control" value="<?= $category->getAttribute('level') == 1 ? '대분류' : '소분류' ?>" readonly>
                            <div class="form-text">분류 레벨은 수정할 수 없습니다.</div>
                        </div>
                        <?php if ($category->getAttribute('level') == 2): ?>
                        <div class="col-md-6">
                            <label for="parent-category" class="form-label">상위 분류</label>
                            <?php 
                            $parentCategory = null;
                            foreach ($mainCategories as $cat) {
                                if ($cat->getAttribute('id') == $category->getAttribute('parent_id')) {
                                    $parentCategory = $cat;
                                    break;
                                }
                            }
                            ?>
                            <input type="text" class="form-control" value="<?= $parentCategory ? e($parentCategory->getAttribute('category_name')) . ' (' . e($parentCategory->getAttribute('category_code')) . ')' : '없음' ?>" readonly>
                            <div class="form-text">상위 분류는 수정할 수 없습니다.</div>
                        </div>
                        <?php endif; ?>
                        <div class="col-12">
                            <label for="category-code" class="form-label">분류 코드</label>
                            <input type="text" class="form-control" id="category-code" value="<?= e($category->getAttribute('category_code')) ?>" readonly>
                            <div class="form-text">분류 코드는 수정할 수 없습니다.</div>
                        </div>
                        <div class="col-12">
                            <label for="category-name" class="form-label">분류명 <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="category-name" name="category_name" required maxlength="100" value="<?= e($category->getAttribute('category_name')) ?>">
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6">
                            <label for="display-order" class="form-label">표시 순서</label>
                            <input type="number" class="form-control" id="display-order" name="display_order" value="<?= $category->getAttribute('display_order') ?>" min="0">
                            <div class="form-text">숫자가 작을수록 먼저 표시됩니다.</div>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6">
                            <label for="is-active" class="form-label">상태</label>
                            <select class="form-select" id="is-active" name="is_active">
                                <option value="1" <?= $category->getAttribute('is_active') ? 'selected' : '' ?>>활성</option>
                                <option value="0" <?= !$category->getAttribute('is_active') ? 'selected' : '' ?>>비활성</option>
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary me-2" id="save-btn">
                            <i class="ri-save-line me-1"></i> 저장
                        </button>
                        <a href="/supply/categories" class="btn btn-secondary">
                            <i class="ri-arrow-left-line me-1"></i> 목록으로
                        </a>
                        <button type="button" class="btn btn-danger ms-2" id="delete-btn">
                            <i class="ri-delete-bin-line me-1"></i> 삭제
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">분류 정보</h5>
            </div>
            <div class="card-body">
                <table class="table table-borderless table-sm">
                    <tbody>
                        <tr>
                            <td class="fw-medium">분류 ID:</td>
                            <td><?= $category->getAttribute('id') ?></td>
                        </tr>
                        <tr>
                            <td class="fw-medium">분류 코드:</td>
                            <td><code><?= e($category->getAttribute('category_code')) ?></code></td>
                        </tr>
                        <tr>
                            <td class="fw-medium">분류 레벨:</td>
                            <td>
                                <span class="badge bg-<?= $category->getAttribute('level') == 1 ? 'primary' : 'info' ?>">
                                    <?= $category->getAttribute('level') == 1 ? '대분류' : '소분류' ?>
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td class="fw-medium">상태:</td>
                            <td>
                                <span class="badge bg-<?= $category->getAttribute('is_active') ? 'success' : 'secondary' ?>">
                                    <?= $category->getAttribute('is_active') ? '활성' : '비활성' ?>
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td class="fw-medium">생성일:</td>
                            <td><?= date('Y-m-d H:i', strtotime($category->getAttribute('created_at'))) ?></td>
                        </tr>
                        <tr>
                            <td class="fw-medium">수정일:</td>
                            <td><?= date('Y-m-d H:i', strtotime($category->getAttribute('updated_at'))) ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">수정 가이드</h5>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <h6 class="alert-heading">수정 가능한 항목</h6>
                    <ul class="mb-0 small">
                        <li>분류명</li>
                        <li>표시 순서</li>
                        <li>상태 (활성/비활성)</li>
                    </ul>
                </div>
                
                <div class="alert alert-warning">
                    <h6 class="alert-heading">수정 불가능한 항목</h6>
                    <ul class="mb-0 small">
                        <li>분류 코드</li>
                        <li>분류 레벨</li>
                        <li>상위 분류</li>
                    </ul>
                </div>
                
                <div class="alert alert-danger">
                    <h6 class="alert-heading">삭제 주의사항</h6>
                    <ul class="mb-0 small">
                        <li>연관된 품목이 있으면 삭제할 수 없습니다.</li>
                        <li>하위 분류가 있으면 삭제할 수 없습니다.</li>
                        <li>삭제된 분류는 복구할 수 없습니다.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteCategoryModal" tabindex="-1" aria-labelledby="deleteCategoryModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteCategoryModalLabel">분류 삭제 확인</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>정말로 다음 분류를 삭제하시겠습니까?</p>
                <div class="alert alert-light">
                    <strong><?= e($category->getAttribute('category_name')) ?></strong><br>
                    <small class="text-muted">코드: <?= e($category->getAttribute('category_code')) ?></small>
                </div>
                <p class="text-danger small">
                    <i class="ri-alert-line me-1"></i>
                    연관된 품목이나 하위 분류가 있는 경우 삭제할 수 없습니다.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
                <button type="button" class="btn btn-danger" id="confirm-delete-btn">삭제</button>
            </div>
        </div>
    </div>
</div>

<?php \App\Core\View::getInstance()->endSection(); ?>