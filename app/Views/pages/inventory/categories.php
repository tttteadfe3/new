<?php \App\Core\View::getInstance()->startSection('content'); ?>
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">지급품 분류 관리</h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="/dashboard">대시보드</a></li>
                    <li class="breadcrumb-item">지급품 관리</li>
                    <li class="breadcrumb-item active">분류 관리</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header">
                <div class="d-flex align-items-center">
                    <h5 class="card-title mb-0 flex-grow-1">분류 목록</h5>
                    <button type="button" class="btn btn-primary" id="add-category-btn">
                        <i class="ri-add-line align-bottom me-1"></i> 신규 분류 추가
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div id="category-tree-container">
                    <!-- JavaScript로 동적 트리 생성 -->
                    <div class="text-center p-3">
                        <p class="text-muted">분류를 불러오는 중입니다...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card">
             <div class="card-header">
                <h5 class="card-title mb-0" id="category-form-title">분류 정보</h5>
            </div>
            <div class="card-body">
                <form id="category-form" class="d-none">
                    <input type="hidden" id="category-id" name="id">

                    <div class="mb-3">
                        <label for="parent-id" class="form-label">상위 분류</label>
                        <select class="form-select" id="parent-id" name="parent_id">
                            <option value="">최상위 분류</option>
                            <!-- JavaScript로 동적 옵션 추가 -->
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="category-name" class="form-label">분류명</label>
                        <input type="text" class="form-control" id="category-name" name="name" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">사용 여부</label>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch" id="is-active" name="is_active" value="1" checked>
                            <label class="form-check-label" for="is-active">사용</label>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end">
                        <button type="button" class="btn btn-light me-2" id="cancel-btn">취소</button>
                        <button type="submit" class="btn btn-success" id="save-btn">저장</button>
                        <button type="button" class="btn btn-danger" id="delete-btn" style="display: none;">삭제</button>
                    </div>
                </form>
                 <div id="form-placeholder" class="text-center p-5">
                    <i class="bi bi-folder2-open fs-1 text-muted"></i>
                    <p class="mt-3 text-muted">왼쪽 목록에서 분류를 선택하거나 '신규 분류 추가' 버튼을 클릭하세요.</p>
                </div>
            </div>
        </div>
    </div>
</div>
<?php \App\Core\View::getInstance()->endSection(); ?>
