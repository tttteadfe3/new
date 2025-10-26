<?php use App\Core\View; ?>
<?php View::getInstance()->startSection('content'); ?>
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">부서 & 직급 관리</h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="/admin">관리자</a></li>
                    <li class="breadcrumb-item active">부서 & 직급 관리</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Department Management Column -->
    <div class="col-xl-6">
        <div class="card">
            <div class="card-header align-items-center d-flex">
                <h4 class="card-title mb-0 flex-grow-1">부서 관리</h4>
                <div class="flex-shrink-0">
                    <button class="btn btn-primary btn-sm" id="add-department-btn">
                        새 부서 추가
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div id="departments-list-container" class="list-group">
                    <div class="list-group-item">로딩 중...</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Position Management Column -->
    <div class="col-xl-6">
        <div class="card">
            <div class="card-header align-items-center d-flex">
                <h4 class="card-title mb-0 flex-grow-1">직급 관리</h4>
                <div class="flex-shrink-0">
                     <button class="btn btn-primary btn-sm" id="add-position-btn">
                        새 직급 추가
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover table-striped align-middle" id="positions-table">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>직급명</th>
                                <th>레벨</th>
                                <th style="width: 150px;">작업</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Position data will be loaded dynamically by JavaScript -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Department Modal -->
<div class="modal fade" id="department-modal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="department-modal-title">부서 정보</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="department-form">
                <div class="modal-body">
                    <input type="hidden" id="department-id" name="id">
                    <div class="mb-3">
                        <label for="department-name" class="form-label">부서 이름</label>
                        <input type="text" class="form-control" id="department-name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="parent-id" class="form-label">상위 부서</label>
                        <select class="form-select" id="parent-id" name="parent_id">
                            <option value="">(없음)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="viewer-employee-ids" class="form-label">조회 권한 직원</label>
                        <select id="viewer-employee-ids" name="viewer_employee_ids[]" multiple></select>
                    </div>
                    <div class="mb-3">
                        <label for="viewer-department-ids" class="form-label">조회 권한 부서</label>
                        <select id="viewer-department-ids" name="viewer_department_ids[]" multiple></select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">닫기</button>
                    <button type="submit" class="btn btn-primary">저장</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Position Modal -->
<div class="modal fade" id="position-modal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="position-modal-title">직급 정보</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="position-form">
                <div class="modal-body">
                    <input type="hidden" id="position-id" name="id">
                    <div class="mb-3">
                        <label for="position-name" class="form-label">직급명</label>
                        <input type="text" class="form-control" id="position-name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="position-level" class="form-label">레벨</label>
                        <input type="number" class="form-control" id="position-level" name="level" required>
                        <div class="form-text">레벨이 낮을수록 높은 직급입니다. (예: 대표 1, 사원 10)</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">닫기</button>
                    <button type="submit" class="btn btn-primary">저장</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php View::getInstance()->endSection(); ?>
