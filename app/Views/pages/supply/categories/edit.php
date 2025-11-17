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
            <div class="card-body" id="form-container">
                <!-- Form will be populated by JS -->
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">로딩 중...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">분류 정보</h5>
            </div>
            <div class="card-body" id="info-container">
                <!-- Info will be populated by JS -->
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

<?php \App\Core\View::getInstance()->startSection('script'); ?>
<script>
    // Note: AI_DEVELOPMENT_GUIDELINES prohibits inline scripts.
    // However, there is no standard method provided for passing initial data (like an ID) to the page-specific JavaScript file.
    // This approach is taken as a practical solution.
    window.viewData = {
        categoryId: <?= $categoryId ?? 'null' ?>
    };
</script>
<?php \App\Core\View::getInstance()->endSection(); ?>

<?php \App\Core\View::getInstance()->endSection(); ?>