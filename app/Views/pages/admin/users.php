<?php \App\Core\View::startSection('content'); ?>
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header align-items-center d-flex">
                <h4 class="card-title mb-0 flex-grow-1">사용자 관리</h4>
            </div><!-- end card header -->

            <div class="card-body">
                <form id="filter-form" class="row g-3 mb-4">
                    <div class="col-md-2">
                        <label for="status-filter" class="form-label">상태</label>
                        <select id="status-filter" class="form-select">
                            <option value="">-- 전체 --</option>
                            <option value="pending">pending</option>
                            <option value="active">active</option>
                            <option value="blocked">blocked</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="nickname-filter" class="form-label">닉네임</label>
                        <input type="text" id="nickname-filter" class="form-control" placeholder="닉네임으로 검색">
                    </div>
                    <div class="col-md-2">
                        <label for="staff-filter" class="form-label">직원 연결</label>
                        <select id="staff-filter" class="form-select">
                            <option value="">-- 전체 --</option>
                            <option value="linked">연결됨</option>
                            <option value="unlinked">연결 안됨</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="role-filter" class="form-label">역할</label>
                        <select id="role-filter" class="form-select">
                            <option value="">-- 전체 --</option>
                            <!-- Roles will be populated by JS -->
                        </select>
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button type="button" id="filter-search-btn" class="btn btn-primary">검색</button>
                        <button type="button" id="filter-reset-btn" class="btn btn-light ms-2">초기화</button>
                    </div>
                </form>
                <div class="table-responsive table-card">
                    <table class="table align-middle table-nowrap table-striped mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>닉네임</th>
                                <th>연결된 직원</th>
                                <th>역할</th>
                                <th>상태</th>
                                <th>관리</th>
                            </tr>
                        </thead>
                        <tbody id="user-table-body">
                            <tr>
                                <td colspan="6" class="text-center">사용자 목록을 불러오는 중...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="user-modal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title" id="user-modal-title">사용자 정보 수정</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <form id="user-form">
                <div class="modal-body">
                    <input type="hidden" name="user_id">
                    <div class="mb-3"><label for="status" class="form-label">계정 상태</label><select name="status" id="status" class="form-select"><option value="pending">pending</option><option value="active">active</option><option value="blocked">blocked</option></select></div>
                    <div class="mb-3">
                        <label class="form-label">역할 할당</label>
                        <div class="form-control" style="height: auto;">
                            <div id="roles-container"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">닫기</button><button type="submit" class="btn btn-primary">저장하기</button></div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="mapping-modal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="mapping-form">
                <div class="modal-header"><h5 class="modal-title" id="mapping-modal-title">직원 연결</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <input type="hidden" name="user_id">
                    <div class="mb-3">
                        <label for="department_filter" class="form-label">부서 필터</label>
                        <select id="department_filter" class="form-select">
                            <option value="">-- 전체 --</option>
                        </select>
                    </div>
                    <label for="employee_id_select" class="form-label">연결할 직원 선택</label>
                    <select id="employee_id_select" name="employee_id" class="form-select" required></select>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">닫기</button><button type="submit" class="btn btn-primary">연결하기</button></div>
            </form>
        </div>
    </div>
</div>
<?php \App\Core\View::endSection(); ?>