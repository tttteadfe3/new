<?php \App\Core\View::getInstance()->startSection('content'); ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0 text-gray-800">부서 및 직급 관리</h1>
</div>

<div class="row">
    <!-- Department Management Column -->
    <div class="col-lg-6">
        <div class="card shadow mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="m-0">부서 관리</h6>
                <button class="btn btn-primary btn-sm" id="add-department-btn">
                    <i class="bi bi-plus-circle"></i> 새 부서 추가
                </button>
            </div>
            <div class="list-group list-group-flush" id="departments-list-container">
                <div class="list-group-item">로딩 중...</div>
            </div>
        </div>
    </div>

    <!-- Position Management Column -->
    <div class="col-lg-6">
        <div class="card shadow mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="m-0">직급 관리</h6>
                <button class="btn btn-primary btn-sm" id="add-position-btn">
                    <i class="bi bi-plus-circle"></i> 새 직급 추가
                </button>
            </div>
            <div class="list-group list-group-flush" id="positions-list-container">
                <div class="list-group-item">로딩 중...</div>
            </div>
        </div>
    </div>
</div>

<!-- Unified Modal for Department/Position -->
<div class="modal fade" id="org-modal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="org-modal-title">정보</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="org-form">
                <div class="modal-body">
                    <input type="hidden" id="org-id" name="id">
                    <input type="hidden" id="org-type" name="type">
                    <div class="mb-3">
                        <label for="org-name" class="form-label" id="org-name-label">이름</label>
                        <input type="text" class="form-control" id="org-name" name="name" required>
                    </div>
                    <!-- Fields specific to Department -->
                    <div id="department-fields" style="display: none;">
                        <div class="mb-3">
                            <label for="parent-id" class="form-label">상위 부서</label>
                            <select class="form-select" id="parent-id" name="parent_id">
                                <option value="">(없음)</option>
                                <!-- Department options will be populated by JS -->
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="manager-ids" class="form-label">부서장</label>
                            <select class="form-select" id="manager-ids" name="manager_ids[]" multiple>
                                <!-- Employee options will be populated by JS -->
                            </select>
                        </div>
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="can-view-all-employees" name="can_view_all_employees">
                            <label class="form-check-label" for="can-view-all-employees">전체 직원 조회 권한</label>
                            <small class="form-text text-muted d-block">이 부서에 속한 모든 사용자가 다른 부서의 직원을 포함한 모든 직원 정보를 조회할 수 있습니다. (인사팀, 경영진 등)</small>
                        </div>
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
<?php \App\Core\View::getInstance()->endSection(); ?>
