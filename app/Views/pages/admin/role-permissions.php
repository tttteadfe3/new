<?php \App\Core\\App\Core\View::getInstance()->startSection('content'); ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0 text-gray-800">역할 및 권한 관리 (비동기)</h1>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="card shadow">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="m-0">역할 목록</h6>
                <button class="btn btn-primary btn-sm" id="add-role-btn">
                    <i class="bi bi-plus-circle"></i> 새 역할 추가
                </button>
            </div>
            <div class="list-group list-group-flush" id="roles-list-container">
                <div class="list-group-item">로딩 중...</div>
            </div>
        </div>
    </div>

    <div class="col-md-8" id="role-details-container">
        <div class="alert alert-info">좌측에서 역할을 선택해주세요.</div>
    </div>
</div>


<div class="modal fade" id="role-modal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="role-modal-title">역할 정보</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="role-form">
                <div class="modal-body">
                    <input type="hidden" id="role-id" name="id">
                    <div class="mb-3">
                        <label for="role-name" class="form-label">역할 이름</label>
                        <input type="text" class="form-control" id="role-name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="role-description" class="form-label">설명</label>
                        <textarea class="form-control" id="role-description" name="description" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">닫기</button>
                    <button type="submit" class="btn btn-primary">저장하기</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php \App\Core\\App\Core\View::getInstance()->endSection(); ?>